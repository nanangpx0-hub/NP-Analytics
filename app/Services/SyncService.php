<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DeviceCredential;
use App\Models\Indicator;
use App\Models\Phenomenon;
use App\Models\SyncConflict;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use NpAnalytics\Core\Support\DeterministicUuid;
use NpAnalytics\Core\Support\SyncRecordMapper;
use RuntimeException;
use Throwable;

class SyncService
{
    public const PUSH_LIMIT = 200;

    private const EMPTY_COUNTS = [
        'categories' => 0,
        'indicators' => 0,
        'phenomena' => 0,
    ];

    public function sync(): array
    {
        $lock = Cache::lock('np-analytics-sync', 300);

        if (! $lock->get()) {
            throw SyncException::alreadyRunning();
        }

        try {
            return $this->performSync();
        } finally {
            try {
                $lock->release();
            } catch (Throwable) {
                // Lock sudah kedaluwarsa atau dilepas proses lain.
            }
        }
    }

    private function performSync(): array
    {
        $credential = DeviceCredential::active();

        if (! $credential) {
            throw SyncException::notConfigured();
        }

        $baseUrl = rtrim($credential->server_url, '/');
        $token = (string) $credential->token;
        $lastVersion = $this->getLastServerVersion();

        $pulled = self::EMPTY_COUNTS;
        $pushed = self::EMPTY_COUNTS;
        $conflicts = [];
        $errors = [];

        try {
            // Bootstrap: tarik seluruh data server lebih dulu agar data seeder
            // lokal bertemu UUID yang sama sebelum ada push.
            if ($lastVersion === null) {
                $result = $this->pull($baseUrl, $token, null);
                $this->addCounts($pulled, $result['counts']);
                $errors = array_merge($errors, $result['errors']);
            }

            $result = $this->pushDirty($baseUrl, $token, (string) $credential->device_uuid);
            $this->addCounts($pushed, $result['counts']);
            $conflicts = $result['conflicts'];
            $errors = array_merge($errors, $result['errors']);

            $since = $this->getLastServerVersion() ?? 0;
            $result = $this->pull($baseUrl, $token, $since);
            $this->addCounts($pulled, $result['counts']);
            $errors = array_merge($errors, $result['errors']);
        } catch (ConnectionException $exception) {
            throw SyncException::offline($exception);
        }

        $this->setState('last_sync_at', now()->toIso8601String());

        return [
            'max_version' => $this->getLastServerVersion() ?? 0,
            'pushed' => $pushed,
            'pulled' => $pulled,
            'conflicts' => $conflicts,
            'errors' => $errors,
            'server_time' => now()->toIso8601String(),
        ];
    }

    public function getLastServerVersion(): ?int
    {
        if (! Schema::hasTable('sync_states')) {
            return null;
        }

        $value = DB::table('sync_states')->where('key', 'last_server_version')->value('value');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function getLastSyncAt(): ?Carbon
    {
        if (! Schema::hasTable('sync_states')) {
            return null;
        }

        $value = DB::table('sync_states')->where('key', 'last_sync_at')->value('value');

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function setLastServerVersion(int $version): void
    {
        $this->setState('last_server_version', (string) $version);
    }

    private function setState(string $key, string $value): void
    {
        if (! Schema::hasTable('sync_states')) {
            return;
        }

        DB::table('sync_states')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    private function pull(string $baseUrl, string $token, ?int $since): array
    {
        $cursor = 0;
        $maxVersion = $since ?? 0;
        $counts = self::EMPTY_COUNTS;
        $errors = [];

        do {
            $query = [];

            if ($since !== null) {
                $query['since_version'] = $since;
            }

            if ($cursor > 0) {
                $query['cursor'] = $cursor;
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($baseUrl.'/api/sync/pull', $query);

            $this->guardResponse($response);

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new SyncException(
                    'invalid_response',
                    'Respons server sinkronisasi tidak valid. Coba lagi.',
                );
            }

            $applied = $this->applyPullRecords((array) ($payload['records'] ?? []));
            $this->addCounts($counts, $applied['counts']);
            $errors = array_merge($errors, $applied['errors']);

            $maxVersion = (int) ($payload['max_version'] ?? $maxVersion);
            $next = $payload['next_cursor'] ?? null;
            $cursor = $next !== null ? (int) $next : 0;
        } while ($cursor > 0);

        $this->setLastServerVersion($maxVersion);

        return [
            'counts' => $counts,
            'errors' => $errors,
            'max_version' => $maxVersion,
        ];
    }

    private function applyPullRecords(array $records): array
    {
        $counts = self::EMPTY_COUNTS;
        $errors = [];

        DB::transaction(function () use ($records, &$counts, &$errors): void {
            foreach ($records as $record) {
                if (! is_array($record)) {
                    continue;
                }

                $type = (string) ($record['type'] ?? '');
                $data = $record['data'] ?? null;

                if (! is_array($data)) {
                    $errors[] = [
                        'type' => $type,
                        'uuid' => null,
                        'code' => 'invalid_record',
                        'message' => 'Server mengirim record tanpa data yang valid.',
                    ];

                    continue;
                }

                try {
                    if ($this->applyServerRecord($type, $data)) {
                        $counts[$this->groupForType($type)]++;
                    }
                } catch (Throwable $exception) {
                    Log::warning('Sync pull record failed', [
                        'type' => $type,
                        'uuid' => $data['uuid'] ?? null,
                        'error' => $exception->getMessage(),
                    ]);

                    $errors[] = [
                        'type' => $type,
                        'uuid' => $data['uuid'] ?? null,
                        'code' => 'apply_failed',
                        'message' => 'Data dari server gagal diterapkan di perangkat.',
                    ];
                }
            }
        });

        return [
            'counts' => $counts,
            'errors' => $errors,
        ];
    }

    public function resolveConflictWithServer(SyncConflict $conflict): void
    {
        $server = $conflict->server_payload;

        if ($server === null) {
            $local = $this->findLocalRecord($conflict->record_type, $conflict->record_uuid);

            if ($local) {
                $local->forceDelete();
            }
        } else {
            $this->applyServerRecord($conflict->record_type, $server, true);
        }

        $conflict->delete();
    }

    public function resolveConflictWithLocal(SyncConflict $conflict): void
    {
        $local = $conflict->local_payload;

        if ($local === null) {
            $this->resolveConflictWithServer($conflict);

            return;
        }

        $type = $conflict->record_type;
        $uuid = $conflict->record_uuid;
        $model = $this->findLocalRecord($type, $uuid);

        if (! $model) {
            $model = match ($type) {
                'category' => new Category,
                'indicator' => new Indicator,
                'phenomenon' => new Phenomenon,
                default => throw new SyncException(
                    'conflict_unresolved',
                    'Jenis data konflik tidak dikenali sehingga tidak dapat dikirim ulang.',
                ),
            };

            $model->uuid = $uuid;
        }

        if ($type === 'indicator') {
            $category = Category::withTrashed()
                ->where('uuid', (string) ($local['category_uuid'] ?? ''))
                ->first();

            if (! $category) {
                throw new SyncException(
                    'conflict_unresolved',
                    'Kategori induk tidak ditemukan. Pulihkan kategori terlebih dahulu.',
                );
            }

            $model->forceFill(['category_id' => $category->id]);
        }

        if ($type === 'phenomenon') {
            $indicator = Indicator::withTrashed()
                ->where('uuid', (string) ($local['indicator_uuid'] ?? ''))
                ->first();

            if (! $indicator) {
                throw new SyncException(
                    'conflict_unresolved',
                    'Indikator induk tidak ditemukan. Pulihkan indikator terlebih dahulu.',
                );
            }

            $model->forceFill(['indicator_id' => $indicator->id]);
        }

        $model->forceFill($this->pick($local, $this->fieldsForType($type)));
        $model->forceFill([
            'server_version' => (int) $conflict->server_version,
            'base_version' => (int) $conflict->server_version,
            'dirty' => true,
            'deleted_at' => $this->parseDate($local['deleted_at'] ?? null),
        ]);
        $model->timestamps = false;

        try {
            $model->saveQuietly();
        } finally {
            $model->timestamps = true;
        }

        $conflict->delete();
    }

    private function fieldsForType(string $type): array
    {
        return match ($type) {
            'category' => ['name', 'icon'],
            'indicator' => [
                'series_key',
                'title',
                'value',
                'unit',
                'year',
                'trend',
                'is_higher_better',
                'description',
                'image_path',
                'region_level',
                'frequency',
                'period_start',
                'period_end',
                'published_at',
                'source_document',
                'source_table',
                'source_page',
                'revision',
                'quality_note',
            ],
            'phenomenon' => [
                'title',
                'description',
                'impact',
                'source',
                'order',
                'insight_type',
                'source_document',
                'source_page',
                'published_at',
                'author',
                'quality_note',
            ],
            default => [],
        };
    }

    private function applyServerRecord(string $type, array $data, bool $force = false): bool
    {
        return match ($type) {
            'category' => $this->applyCategory($data, $force),
            'indicator' => $this->applyIndicator($data, $force),
            'phenomenon' => $this->applyPhenomenon($data, $force),
            default => throw new RuntimeException('Unknown sync record type'),
        };
    }

    private function applyCategory(array $data, bool $force): bool
    {
        $uuid = $this->requireUuid($data);
        $category = Category::withTrashed()->where('uuid', $uuid)->first();

        if (! $category && isset($data['name']) && $data['name'] !== '') {
            $category = $this->cleanCategoryByName((string) $data['name']);

            if ($category) {
                $category->uuid = $uuid;
            }
        }

        if (! $category) {
            if (! isset($data['name']) || $data['name'] === '') {
                throw new RuntimeException('Category name missing');
            }

            $category = new Category;
            $category->uuid = $uuid;
        }

        if ($category->exists && $category->dirty && ! $force) {
            return false;
        }

        $category->forceFill($this->pick($data, ['name', 'icon']));
        $this->applyServerState($category, $data);

        return true;
    }

    private function applyIndicator(array $data, bool $force): bool
    {
        $uuid = $this->requireUuid($data);
        $indicator = Indicator::withTrashed()->where('uuid', $uuid)->first();

        if (! $indicator && isset($data['series_key'], $data['year'])) {
            $indicator = Indicator::withTrashed()
                ->where('series_key', (string) $data['series_key'])
                ->where('year', (int) $data['year'])
                ->where('dirty', false)
                ->first();

            if ($indicator) {
                $indicator->uuid = $uuid;
            }
        }

        if (! $indicator) {
            $indicator = new Indicator;
            $indicator->uuid = $uuid;
        }

        if ($indicator->exists && $indicator->dirty && ! $force) {
            return false;
        }

        $categoryUuid = $data['category_uuid'] ?? null;
        $category = $categoryUuid
            ? Category::withTrashed()->where('uuid', (string) $categoryUuid)->first()
            : null;

        if (! $category) {
            throw new RuntimeException('Indicator category missing');
        }

        $indicator->forceFill($this->pick($data, [
            'series_key',
            'title',
            'value',
            'unit',
            'year',
            'trend',
            'is_higher_better',
            'description',
            'image_path',
            'region_level',
            'frequency',
            'period_start',
            'period_end',
            'published_at',
            'source_document',
            'source_table',
            'source_page',
            'revision',
            'quality_note',
        ]));

        $indicator->category_id = $category->id;

        $this->applyServerState($indicator, $data);

        return true;
    }

    private function applyPhenomenon(array $data, bool $force): bool
    {
        $uuid = $this->requireUuid($data);
        $phenomenon = Phenomenon::withTrashed()->where('uuid', $uuid)->first();

        $indicatorUuid = $data['indicator_uuid'] ?? null;
        $indicator = $indicatorUuid
            ? Indicator::withTrashed()->where('uuid', (string) $indicatorUuid)->first()
            : null;

        if (! $phenomenon && $indicator && isset($data['order'])) {
            $phenomenon = Phenomenon::withTrashed()
                ->where('indicator_id', $indicator->id)
                ->where('order', (int) $data['order'])
                ->where('dirty', false)
                ->first();

            if ($phenomenon) {
                $phenomenon->uuid = $uuid;
            }
        }

        if (! $phenomenon) {
            $phenomenon = new Phenomenon;
            $phenomenon->uuid = $uuid;
        }

        if ($phenomenon->exists && $phenomenon->dirty && ! $force) {
            return false;
        }

        if (! $indicator) {
            throw new RuntimeException('Phenomenon indicator missing');
        }

        $phenomenon->forceFill($this->pick($data, [
            'title',
            'description',
            'impact',
            'source',
            'order',
            'insight_type',
            'source_document',
            'source_page',
            'published_at',
            'author',
            'quality_note',
        ]));

        $phenomenon->indicator_id = $indicator->id;
        $this->applyServerState($phenomenon, $data);

        return true;
    }

    private function applyServerState(Model $model, array $data): void
    {
        $version = (int) ($data['server_version'] ?? 0);
        $attributes = [
            'server_version' => $version,
            'base_version' => $version,
            'dirty' => false,
        ];

        if (array_key_exists('deleted_at', $data)) {
            $attributes['deleted_at'] = $this->parseDate($data['deleted_at']);
        }

        if (array_key_exists('updated_at', $data)) {
            $attributes['updated_at'] = $this->parseDate($data['updated_at']) ?? now();
        }

        $model->forceFill($attributes);
        $model->timestamps = false;

        try {
            $model->saveQuietly();
        } finally {
            $model->timestamps = true;
        }
    }

    private function cleanCategoryByName(string $name): ?Category
    {
        $normalized = DeterministicUuid::normalizeName($name);

        return Category::withTrashed()
            ->where('dirty', false)
            ->orderBy('id')
            ->get()
            ->first(function (Category $category) use ($normalized): bool {
                return DeterministicUuid::normalizeName((string) $category->name) === $normalized;
            });
    }

    private function pushDirty(string $baseUrl, string $token, string $deviceUuid): array
    {
        $collected = $this->collectDirtyRecords();
        $records = $collected['records'];
        $errors = $collected['errors'];
        $conflicts = [];
        $counts = self::EMPTY_COUNTS;

        foreach (array_chunk($records, self::PUSH_LIMIT) as $chunk) {
            $payload = [
                'categories' => [],
                'indicators' => [],
                'phenomena' => [],
            ];

            foreach ($chunk as $record) {
                $payload[$this->groupForType($record['type'])][] = $record['payload'];
            }

            $payload['idempotency_key'] = $this->idempotencyKey($deviceUuid, $payload);

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl.'/api/sync/push', $payload);

            $this->guardResponse($response);

            $result = $response->json();

            if (! is_array($result) || ! is_array($result['results'] ?? null)) {
                throw new SyncException(
                    'invalid_response',
                    'Respons server sinkronisasi tidak valid. Coba lagi.',
                );
            }

            foreach ($result['results'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $type = (string) ($item['type'] ?? '');
                $uuid = (string) ($item['uuid'] ?? '');
                $status = (string) ($item['status'] ?? '');

                if (! in_array($type, ['category', 'indicator', 'phenomenon'], true) || $uuid === '') {
                    continue;
                }

                if ($status === 'applied') {
                    $this->applyPushResult($type, $uuid, $item['server'] ?? null);
                    $counts[$this->groupForType($type)]++;

                    continue;
                }

                if ($status === 'conflict') {
                    $conflicts[] = $this->storeConflict(
                        $type,
                        $uuid,
                        is_array($item['server'] ?? null) ? $item['server'] : null,
                        (int) ($item['server_version'] ?? 0),
                    );

                    continue;
                }

                if ($status === 'error') {
                    $errors[] = [
                        'type' => $type,
                        'uuid' => $uuid,
                        'code' => (string) ($item['code'] ?? 'sync_error'),
                        'message' => (string) ($item['message'] ?? 'Record gagal diproses di server.'),
                    ];
                }
            }
        }

        return [
            'counts' => $counts,
            'conflicts' => $conflicts,
            'errors' => $errors,
        ];
    }

    private function collectDirtyRecords(): array
    {
        $active = [];
        $deleted = [];
        $errors = [];

        $categories = Category::withTrashed()
            ->where('dirty', true)
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $record = [
                'type' => 'category',
                'payload' => $this->buildCategoryPayload($category),
            ];

            if ($category->trashed()) {
                $deleted[] = $record;
            } else {
                $active[] = $record;
            }
        }

        $indicators = Indicator::withTrashed()
            ->with(['category' => function (BelongsTo $relation): void {
                $relation->getQuery()
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->select('id', 'uuid');
            }])
            ->where('dirty', true)
            ->orderBy('id')
            ->get();

        foreach ($indicators as $indicator) {
            $categoryUuid = $indicator->category?->uuid;

            if (! $categoryUuid) {
                $errors[] = [
                    'type' => 'indicator',
                    'uuid' => $indicator->uuid,
                    'code' => 'parent_missing',
                    'message' => 'Indikator tanpa kategori tidak dapat dikirim.',
                ];

                continue;
            }

            $record = [
                'type' => 'indicator',
                'payload' => $this->buildIndicatorPayload($indicator, (string) $categoryUuid),
            ];

            if ($indicator->trashed()) {
                $deleted[] = $record;
            } else {
                $active[] = $record;
            }
        }

        $phenomena = Phenomenon::withTrashed()
            ->with(['indicator' => function (BelongsTo $relation): void {
                $relation->getQuery()
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->select('id', 'uuid');
            }])
            ->where('dirty', true)
            ->orderBy('id')
            ->get();

        foreach ($phenomena as $phenomenon) {
            $indicatorUuid = $phenomenon->indicator?->uuid;

            if (! $indicatorUuid) {
                $errors[] = [
                    'type' => 'phenomenon',
                    'uuid' => $phenomenon->uuid,
                    'code' => 'parent_missing',
                    'message' => 'Fenomena tanpa indikator tidak dapat dikirim.',
                ];

                continue;
            }

            $record = [
                'type' => 'phenomenon',
                'payload' => $this->buildPhenomenonPayload($phenomenon, (string) $indicatorUuid),
            ];

            if ($phenomenon->trashed()) {
                $deleted[] = $record;
            } else {
                $active[] = $record;
            }
        }

        // Penghapusan dikirim dari anak ke induk agar cascade server tidak
        // membatalkan base_version record yang menyusul di batch berikutnya.
        $deleted = array_reverse($deleted);

        return [
            'records' => array_merge($active, $deleted),
            'errors' => $errors,
        ];
    }

    private function buildCategoryPayload(Category $category): array
    {
        return SyncRecordMapper::pushCategory($category);
    }

    private function buildIndicatorPayload(Indicator $indicator, string $categoryUuid): array
    {
        return SyncRecordMapper::pushIndicator($indicator, $categoryUuid);
    }

    private function buildPhenomenonPayload(Phenomenon $phenomenon, string $indicatorUuid): array
    {
        return SyncRecordMapper::pushPhenomenon($phenomenon, $indicatorUuid);
    }

    private function applyPushResult(string $type, string $uuid, mixed $server): void
    {
        $server = is_array($server) ? $server : null;

        if ($server === null) {
            $model = $this->findLocalRecord($type, $uuid);

            if ($model && method_exists($model, 'trashed') && $model->trashed()) {
                $model->forceDelete();
            }

            return;
        }

        try {
            $this->applyServerRecord($type, $server, true);
        } catch (Throwable $exception) {
            Log::warning('Sync push result apply failed', [
                'type' => $type,
                'uuid' => $uuid,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function storeConflict(
        string $type,
        string $uuid,
        ?array $server,
        int $serverVersion,
    ): array {
        $local = $this->findLocalRecord($type, $uuid);
        $localPayload = $local ? $this->localPayload($local) : null;

        SyncConflict::updateOrCreate(
            ['record_type' => $type, 'record_uuid' => $uuid],
            [
                'local_payload' => $localPayload,
                'server_payload' => $server,
                'server_version' => $serverVersion,
                'resolved_at' => null,
            ],
        );

        return [
            'type' => $type,
            'uuid' => $uuid,
            'title' => (string) ($localPayload['title'] ?? $localPayload['name'] ?? $uuid),
        ];
    }

    private function localPayload(Model $model): array
    {
        if ($model instanceof Category) {
            return $this->buildCategoryPayload($model);
        }

        if ($model instanceof Indicator) {
            return $this->buildIndicatorPayload(
                $model,
                (string) ($model->category()
                    ->getQuery()
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->value('uuid') ?? ''),
            );
        }

        if ($model instanceof Phenomenon) {
            return $this->buildPhenomenonPayload(
                $model,
                (string) ($model->indicator()
                    ->getQuery()
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->value('uuid') ?? ''),
            );
        }

        throw new RuntimeException('Unknown sync record type');
    }

    private function findLocalRecord(string $type, string $uuid): ?Model
    {
        return match ($type) {
            'category' => Category::withTrashed()->where('uuid', $uuid)->first(),
            'indicator' => Indicator::withTrashed()->where('uuid', $uuid)->first(),
            'phenomenon' => Phenomenon::withTrashed()->where('uuid', $uuid)->first(),
            default => null,
        };
    }

    private function idempotencyKey(string $deviceUuid, array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 'np-'.substr(hash('sha256', $deviceUuid.'|'.$encoded), 0, 40);
    }

    private function requireUuid(array $data): string
    {
        $uuid = $data['uuid'] ?? null;

        if (! is_string($uuid) || $uuid === '') {
            throw new RuntimeException('Missing uuid');
        }

        return $uuid;
    }

    private function groupForType(string $type): string
    {
        return match ($type) {
            'category' => 'categories',
            'indicator' => 'indicators',
            'phenomenon' => 'phenomena',
            default => throw new RuntimeException('Unknown sync record type'),
        };
    }

    private function pick(array $data, array $fields): array
    {
        $picked = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $picked[$field] = $data[$field];
            }
        }

        return $picked;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function addCounts(array &$target, array $source): void
    {
        foreach (self::EMPTY_COUNTS as $key => $value) {
            $target[$key] += (int) ($source[$key] ?? $value);
        }
    }

    private function guardResponse(Response $response): void
    {
        $status = $response->status();

        if ($status === 401 || $status === 403) {
            throw SyncException::unauthorized();
        }

        if ($status >= 500) {
            throw SyncException::serverError();
        }

        if ($status === 429) {
            throw SyncException::rateLimited();
        }

        if ($status === 422) {
            throw new SyncException(
                'request_rejected',
                'Server menolak data sinkronisasi. Periksa data lalu coba lagi.',
            );
        }

        if ($status >= 400) {
            throw new SyncException(
                'request_rejected',
                'Permintaan sinkronisasi ditolak server. Periksa data lalu coba lagi.',
            );
        }
    }
}

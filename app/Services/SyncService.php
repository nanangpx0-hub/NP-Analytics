<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SyncService
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('sync.base_url'), '/');
        $apiKey = (string) config('sync.api_key');

        if ($baseUrl === '') {
            throw new \RuntimeException('SYNC_BASE_URL belum diatur.');
        }

        if ($apiKey === '') {
            throw new \RuntimeException('SYNC_API_KEY belum diatur.');
        }

        $lastSync = $this->getLastSyncTime();

        $push = $this->buildPushPayload($lastSync);
        $pushResponse = null;

        if ($push['has_changes']) {
            $pushResponse = Http::withHeaders($this->headers($apiKey))
                ->timeout(20)
                ->post($baseUrl . '/api/sync/push', $push['payload'])
                ->throw()
                ->json();
        }

        $pullResponse = Http::withHeaders($this->headers($apiKey))
            ->timeout(20)
            ->get($baseUrl . '/api/sync/pull', $lastSync ? ['since' => $lastSync->toIso8601String()] : [])
            ->throw()
            ->json();

        $applyResult = $this->applyPull($pullResponse);

        $serverTime = $pullResponse['server_time'] ?? ($pushResponse['server_time'] ?? now()->toIso8601String());
        $errors = array_merge($push['errors'], $pushResponse['errors'] ?? [], $applyResult['errors']);

        if (empty($errors) && empty($pushResponse['conflicts'] ?? [])) {
            $this->setLastSyncTime($serverTime);
        }

        return [
            'server_time' => $serverTime,
            'pushed' => $pushResponse['applied'] ?? ['categories' => 0, 'indicators' => 0, 'phenomena' => 0],
            'pulled' => $applyResult['pulled'],
            'conflicts' => $pushResponse['conflicts'] ?? [],
            'errors' => $errors,
        ];
    }

    public function getLastSyncTime(): ?Carbon
    {
        if (!Schema::hasTable('sync_states')) {
            return null;
        }

        $value = DB::table('sync_states')->where('key', 'last_server_time')->value('value');
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function setLastSyncTime(string $value): void
    {
        if (!Schema::hasTable('sync_states')) {
            return;
        }

        DB::table('sync_states')->updateOrInsert(
            ['key' => 'last_server_time'],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function headers(string $apiKey): array
    {
        return [
            'X-SYNC-KEY' => $apiKey,
            'Accept' => 'application/json',
        ];
    }

    private function buildPushPayload(?Carbon $since): array
    {
        $errors = [];

        $categories = $this->applySince(Category::withTrashed(), $since)
            ->get()
            ->map(fn (Category $category) => [
                'uuid' => $category->uuid,
                'name' => $category->name,
                'icon' => $category->icon,
                'updated_at' => $category->updated_at?->toIso8601String(),
                'deleted_at' => $category->deleted_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $indicators = [];
        $indicatorQuery = $this->applySince(
            Indicator::withTrashed()->with(['category' => function ($query) {
                $query->withTrashed()->select('id', 'uuid');
            }]),
            $since
        )->get();

        foreach ($indicatorQuery as $indicator) {
            $categoryUuid = $indicator->category?->uuid;
            if (!$categoryUuid) {
                $errors[] = [
                    'type' => 'indicator',
                    'uuid' => $indicator->uuid,
                    'message' => 'Missing category_uuid on local data',
                ];
                continue;
            }

            $indicators[] = [
                'uuid' => $indicator->uuid,
                'category_uuid' => $categoryUuid,
                'title' => $indicator->title,
                'value' => $indicator->value,
                'unit' => $indicator->unit,
                'year' => $indicator->year,
                'trend' => $indicator->trend,
                'is_higher_better' => $indicator->is_higher_better,
                'description' => $indicator->description,
                'image_path' => $indicator->image_path,
                'region_level' => $indicator->region_level,
                'frequency' => $indicator->frequency,
                'period_start' => $indicator->period_start?->toDateString(),
                'period_end' => $indicator->period_end?->toDateString(),
                'published_at' => $indicator->published_at?->toDateString(),
                'source_document' => $indicator->source_document,
                'source_table' => $indicator->source_table,
                'source_page' => $indicator->source_page,
                'revision' => $indicator->revision,
                'quality_note' => $indicator->quality_note,
                'preferred_direction' => $indicator->preferred_direction,
                'updated_at' => $indicator->updated_at?->toIso8601String(),
                'deleted_at' => $indicator->deleted_at?->toIso8601String(),
            ];
        }

        $phenomena = [];
        $phenomenonQuery = $this->applySince(
            Phenomenon::withTrashed()->with(['indicator' => function ($query) {
                $query->withTrashed()->select('id', 'uuid');
            }]),
            $since
        )->get();

        foreach ($phenomenonQuery as $phenomenon) {
            $indicatorUuid = $phenomenon->indicator?->uuid;
            if (!$indicatorUuid) {
                $errors[] = [
                    'type' => 'phenomenon',
                    'uuid' => $phenomenon->uuid,
                    'message' => 'Missing indicator_uuid on local data',
                ];
                continue;
            }

            $phenomena[] = [
                'uuid' => $phenomenon->uuid,
                'indicator_uuid' => $indicatorUuid,
                'title' => $phenomenon->title,
                'description' => $phenomenon->description,
                'impact' => $phenomenon->impact,
                'source' => $phenomenon->source,
                'order' => $phenomenon->order,
                'insight_type' => $phenomenon->insight_type,
                'source_document' => $phenomenon->source_document,
                'source_page' => $phenomenon->source_page,
                'published_at' => $phenomenon->published_at?->toDateString(),
                'author' => $phenomenon->author,
                'quality_note' => $phenomenon->quality_note,
                'updated_at' => $phenomenon->updated_at?->toIso8601String(),
                'deleted_at' => $phenomenon->deleted_at?->toIso8601String(),
            ];
        }

        $payload = [
            'categories' => $categories,
            'indicators' => $indicators,
            'phenomena' => $phenomena,
        ];

        $hasChanges = !empty($categories) || !empty($indicators) || !empty($phenomena);

        return [
            'payload' => $payload,
            'has_changes' => $hasChanges,
            'errors' => $errors,
        ];
    }

    private function applyPull(array $payload): array
    {
        $pulled = [
            'categories' => 0,
            'indicators' => 0,
            'phenomena' => 0,
        ];
        $errors = [];

        try {
            DB::transaction(function () use ($payload, &$pulled, &$errors): void {
                $categoryMap = Category::withTrashed()->get()->keyBy('uuid');
                $indicatorMap = Indicator::withTrashed()->get()->keyBy('uuid');
                $phenomenonMap = Phenomenon::withTrashed()->get()->keyBy('uuid');

                foreach ($payload['categories'] ?? [] as $item) {
                    try {
                        $this->applyCategoryPayload($item, $categoryMap);
                        $pulled['categories']++;
                    } catch (\Throwable $exception) {
                        $errors[] = [
                            'type' => 'category',
                            'uuid' => $item['uuid'] ?? null,
                            'message' => $exception->getMessage(),
                        ];
                    }
                }

                foreach ($payload['indicators'] ?? [] as $item) {
                    try {
                        $this->applyIndicatorPayload($item, $indicatorMap, $categoryMap);
                        $pulled['indicators']++;
                    } catch (\Throwable $exception) {
                        $errors[] = [
                            'type' => 'indicator',
                            'uuid' => $item['uuid'] ?? null,
                            'message' => $exception->getMessage(),
                        ];
                    }
                }

                foreach ($payload['phenomena'] ?? [] as $item) {
                    try {
                        $this->applyPhenomenonPayload($item, $phenomenonMap, $indicatorMap);
                        $pulled['phenomena']++;
                    } catch (\Throwable $exception) {
                        $errors[] = [
                            'type' => 'phenomenon',
                            'uuid' => $item['uuid'] ?? null,
                            'message' => $exception->getMessage(),
                        ];
                    }
                }
            });
        } catch (\Throwable $exception) {
            // Transaksi di-rollback, sehingga hitungan perubahan tidak valid.
            $pulled = [
                'categories' => 0,
                'indicators' => 0,
                'phenomena' => 0,
            ];
            $errors[] = [
                'type' => 'sync',
                'uuid' => null,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'pulled' => $pulled,
            'errors' => $errors,
        ];
    }

    private function applyCategoryPayload(array $payload, $categoryMap): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $category = $categoryMap->get($uuid);
        if (!$category) {
            $category = new Category(['uuid' => $uuid]);
            $categoryMap->put($uuid, $category);
        }

        if ($deletedAt) {
            if (!$category->exists) {
                $category->name = $payload['name'] ?? '';
                $category->icon = $payload['icon'] ?? null;
                $category->save();
            }

            if (!$category->trashed()) {
                $category->delete();
            }

            $this->forceTimestamps($category, $updatedAt, $deletedAt);
            return;
        }

        if ($category->trashed()) {
            $category->restore();
        }

        $category->fill([
            'name' => $payload['name'] ?? $category->name,
            'icon' => $payload['icon'] ?? $category->icon,
        ]);

        $this->forceTimestamps($category, $updatedAt, null);
    }

    private function applyIndicatorPayload(array $payload, $indicatorMap, $categoryMap): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $indicator = $indicatorMap->get($uuid);
        if (!$indicator) {
            $indicator = new Indicator(['uuid' => $uuid]);
            $indicatorMap->put($uuid, $indicator);
        }

        if ($deletedAt) {
            if (!$indicator->exists) {
                $this->fillIndicatorFields($indicator, $payload, $categoryMap);
                $indicator->save();
            }

            if (!$indicator->trashed()) {
                $indicator->delete();
            }

            $this->forceTimestamps($indicator, $updatedAt, $deletedAt);
            return;
        }

        if ($indicator->trashed()) {
            $indicator->restore();
        }

        $this->fillIndicatorFields($indicator, $payload, $categoryMap);
        $this->forceTimestamps($indicator, $updatedAt, null);
    }

    private function fillIndicatorFields(Indicator $indicator, array $payload, $categoryMap): void
    {
        $categoryUuid = $payload['category_uuid'] ?? null;
        if (!is_string($categoryUuid) || $categoryUuid === '') {
            throw new \InvalidArgumentException('Missing category_uuid');
        }

        $category = $categoryMap->get($categoryUuid);
        if (!$category) {
            throw new \InvalidArgumentException('Category not found');
        }

        $indicator->fill([
            'category_id' => $category->id,
            'title' => $payload['title'] ?? $indicator->title,
            'value' => $payload['value'] ?? $indicator->value,
            'unit' => $payload['unit'] ?? $indicator->unit,
            'year' => $payload['year'] ?? $indicator->year,
            'trend' => $payload['trend'] ?? $indicator->trend,
            'is_higher_better' => $payload['is_higher_better'] ?? $indicator->is_higher_better,
            'description' => $payload['description'] ?? $indicator->description,
            'image_path' => $payload['image_path'] ?? $indicator->image_path,
            'region_level' => $payload['region_level'] ?? $indicator->region_level,
            'frequency' => $payload['frequency'] ?? $indicator->frequency,
            'period_start' => $payload['period_start'] ?? $indicator->period_start,
            'period_end' => $payload['period_end'] ?? $indicator->period_end,
            'published_at' => $payload['published_at'] ?? $indicator->published_at,
            'source_document' => $payload['source_document'] ?? $indicator->source_document,
            'source_table' => $payload['source_table'] ?? $indicator->source_table,
            'source_page' => $payload['source_page'] ?? $indicator->source_page,
            'revision' => $payload['revision'] ?? $indicator->revision,
            'quality_note' => $payload['quality_note'] ?? $indicator->quality_note,
            'preferred_direction' => $payload['preferred_direction'] ?? $indicator->preferred_direction,
        ]);
    }

    private function applyPhenomenonPayload(array $payload, $phenomenonMap, $indicatorMap): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $phenomenon = $phenomenonMap->get($uuid);
        if (!$phenomenon) {
            $phenomenon = new Phenomenon(['uuid' => $uuid]);
            $phenomenonMap->put($uuid, $phenomenon);
        }

        if ($deletedAt) {
            if (!$phenomenon->exists) {
                $this->fillPhenomenonFields($phenomenon, $payload, $indicatorMap);
                $phenomenon->save();
            }

            if (!$phenomenon->trashed()) {
                $phenomenon->delete();
            }

            $this->forceTimestamps($phenomenon, $updatedAt, $deletedAt);
            return;
        }

        if ($phenomenon->trashed()) {
            $phenomenon->restore();
        }

        $this->fillPhenomenonFields($phenomenon, $payload, $indicatorMap);
        $this->forceTimestamps($phenomenon, $updatedAt, null);
    }

    private function fillPhenomenonFields(Phenomenon $phenomenon, array $payload, $indicatorMap): void
    {
        $indicatorUuid = $payload['indicator_uuid'] ?? null;
        if (!is_string($indicatorUuid) || $indicatorUuid === '') {
            throw new \InvalidArgumentException('Missing indicator_uuid');
        }

        $indicator = $indicatorMap->get($indicatorUuid);
        if (!$indicator) {
            throw new \InvalidArgumentException('Indicator not found');
        }

        $phenomenon->fill([
            'indicator_id' => $indicator->id,
            'title' => $payload['title'] ?? $phenomenon->title,
            'description' => $payload['description'] ?? $phenomenon->description,
            'impact' => $payload['impact'] ?? $phenomenon->impact,
            'source' => $payload['source'] ?? $phenomenon->source,
            'order' => $payload['order'] ?? $phenomenon->order,
            'insight_type' => $payload['insight_type'] ?? $phenomenon->insight_type,
            'source_document' => $payload['source_document'] ?? $phenomenon->source_document,
            'source_page' => $payload['source_page'] ?? $phenomenon->source_page,
            'published_at' => $payload['published_at'] ?? $phenomenon->published_at,
            'author' => $payload['author'] ?? $phenomenon->author,
            'quality_note' => $payload['quality_note'] ?? $phenomenon->quality_note,
        ]);
    }

    private function forceTimestamps(Model $model, ?Carbon $updatedAt, ?Carbon $deletedAt): void
    {
        $model->timestamps = false;

        if ($updatedAt) {
            $model->updated_at = $updatedAt;
        }

        $model->deleted_at = $deletedAt;
        $model->save();
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function applySince($query, ?Carbon $since)
    {
        if (!$since) {
            return $query;
        }

        return $query->where(function ($inner) use ($since) {
            $inner->where('updated_at', '>', $since)
                ->orWhere('deleted_at', '>', $since);
        });
    }
}

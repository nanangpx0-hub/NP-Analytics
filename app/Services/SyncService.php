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
        $this->setLastSyncTime($serverTime);

        return [
            'server_time' => $serverTime,
            'pushed' => $pushResponse['applied'] ?? ['categories' => 0, 'indicators' => 0, 'phenomena' => 0],
            'pulled' => $applyResult['pulled'],
            'conflicts' => $pushResponse['conflicts'] ?? [],
            'errors' => array_merge($push['errors'], $pushResponse['errors'] ?? [], $applyResult['errors']),
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
        $result = [
            'pulled' => [
                'categories' => 0,
                'indicators' => 0,
                'phenomena' => 0,
            ],
            'errors' => [],
        ];

        foreach ($payload['categories'] ?? [] as $item) {
            try {
                $this->applyCategoryPayload($item);
                $result['pulled']['categories']++;
            } catch (\Throwable $exception) {
                $result['errors'][] = [
                    'type' => 'category',
                    'uuid' => $item['uuid'] ?? null,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        foreach ($payload['indicators'] ?? [] as $item) {
            try {
                $this->applyIndicatorPayload($item);
                $result['pulled']['indicators']++;
            } catch (\Throwable $exception) {
                $result['errors'][] = [
                    'type' => 'indicator',
                    'uuid' => $item['uuid'] ?? null,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        foreach ($payload['phenomena'] ?? [] as $item) {
            try {
                $this->applyPhenomenonPayload($item);
                $result['pulled']['phenomena']++;
            } catch (\Throwable $exception) {
                $result['errors'][] = [
                    'type' => 'phenomenon',
                    'uuid' => $item['uuid'] ?? null,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function applyCategoryPayload(array $payload): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $category = Category::withTrashed()->where('uuid', $uuid)->first();
        if (!$category) {
            $category = new Category(['uuid' => $uuid]);
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

    private function applyIndicatorPayload(array $payload): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $indicator = Indicator::withTrashed()->where('uuid', $uuid)->first();
        if (!$indicator) {
            $indicator = new Indicator(['uuid' => $uuid]);
        }

        if ($deletedAt) {
            if (!$indicator->exists) {
                $this->fillIndicatorFields($indicator, $payload);
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

        $this->fillIndicatorFields($indicator, $payload);
        $this->forceTimestamps($indicator, $updatedAt, null);
    }

    private function fillIndicatorFields(Indicator $indicator, array $payload): void
    {
        $categoryUuid = $payload['category_uuid'] ?? null;
        if (!is_string($categoryUuid) || $categoryUuid === '') {
            throw new \InvalidArgumentException('Missing category_uuid');
        }

        $category = Category::withTrashed()->where('uuid', $categoryUuid)->first();
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
        ]);
    }

    private function applyPhenomenonPayload(array $payload): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            throw new \InvalidArgumentException('Missing uuid');
        }

        $updatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $deletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $phenomenon = Phenomenon::withTrashed()->where('uuid', $uuid)->first();
        if (!$phenomenon) {
            $phenomenon = new Phenomenon(['uuid' => $uuid]);
        }

        if ($deletedAt) {
            if (!$phenomenon->exists) {
                $this->fillPhenomenonFields($phenomenon, $payload);
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

        $this->fillPhenomenonFields($phenomenon, $payload);
        $this->forceTimestamps($phenomenon, $updatedAt, null);
    }

    private function fillPhenomenonFields(Phenomenon $phenomenon, array $payload): void
    {
        $indicatorUuid = $payload['indicator_uuid'] ?? null;
        if (!is_string($indicatorUuid) || $indicatorUuid === '') {
            throw new \InvalidArgumentException('Missing indicator_uuid');
        }

        $indicator = Indicator::withTrashed()->where('uuid', $indicatorUuid)->first();
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

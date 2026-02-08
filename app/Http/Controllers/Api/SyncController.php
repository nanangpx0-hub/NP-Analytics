<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    public function pull(Request $request)
    {
        $since = $this->parseTimestamp($request->query('since'));

        $categories = $this->applySince(
            Category::withTrashed(),
            $since
        )->get()->map(fn (Category $category) => $this->mapCategory($category));

        $indicators = $this->applySince(
            Indicator::withTrashed()->with(['category' => function ($query) {
                $query->withTrashed()->select('id', 'uuid');
            }]),
            $since
        )->get()->map(fn (Indicator $indicator) => $this->mapIndicator($indicator));

        $phenomena = $this->applySince(
            Phenomenon::withTrashed()->with(['indicator' => function ($query) {
                $query->withTrashed()->select('id', 'uuid');
            }]),
            $since
        )->get()->map(fn (Phenomenon $phenomenon) => $this->mapPhenomenon($phenomenon));

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'categories' => $categories,
            'indicators' => $indicators,
            'phenomena' => $phenomena,
        ]);
    }

    public function push(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'sometimes|array',
            'categories.*.uuid' => 'required|string',
            'categories.*.updated_at' => 'nullable|string',
            'categories.*.deleted_at' => 'nullable|string',
            'indicators' => 'sometimes|array',
            'indicators.*.uuid' => 'required|string',
            'indicators.*.category_uuid' => 'nullable|string',
            'indicators.*.updated_at' => 'nullable|string',
            'indicators.*.deleted_at' => 'nullable|string',
            'phenomena' => 'sometimes|array',
            'phenomena.*.uuid' => 'required|string',
            'phenomena.*.indicator_uuid' => 'nullable|string',
            'phenomena.*.updated_at' => 'nullable|string',
            'phenomena.*.deleted_at' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = [
            'server_time' => now()->toIso8601String(),
            'applied' => [
                'categories' => 0,
                'indicators' => 0,
                'phenomena' => 0,
            ],
            'conflicts' => [],
            'errors' => [],
        ];

        foreach ($request->input('categories', []) as $payload) {
            $this->applyCategory($payload, $result);
        }

        foreach ($request->input('indicators', []) as $payload) {
            $this->applyIndicator($payload, $result);
        }

        foreach ($request->input('phenomena', []) as $payload) {
            $this->applyPhenomenon($payload, $result);
        }

        return response()->json($result);
    }

    private function applyCategory(array $payload, array &$result): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            $this->addError($result, 'category', $uuid, 'Missing uuid');
            return;
        }

        $clientUpdatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $clientDeletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $category = Category::withTrashed()->where('uuid', $uuid)->first();

        if ($category && $this->hasConflict($category, $clientUpdatedAt)) {
            $this->addConflict($result, 'category', $uuid, $this->resolveServerTimestamp($category), $clientUpdatedAt);
            return;
        }

        if ($clientDeletedAt) {
            if ($category && !$category->trashed()) {
                $category->delete();
                $result['applied']['categories']++;
            }
            return;
        }

        if (!$category) {
            if (empty($payload['name'])) {
                $this->addError($result, 'category', $uuid, 'Missing name for create');
                return;
            }
            $category = new Category(['uuid' => $uuid]);
        }

        if ($category->trashed()) {
            $category->restore();
        }

        $category->fill($this->extractFields($payload, [
            'name',
            'icon',
        ]));

        $category->save();
        $result['applied']['categories']++;
    }

    private function applyIndicator(array $payload, array &$result): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            $this->addError($result, 'indicator', $uuid, 'Missing uuid');
            return;
        }

        $clientUpdatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $clientDeletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $indicator = Indicator::withTrashed()->where('uuid', $uuid)->first();

        if ($indicator && $this->hasConflict($indicator, $clientUpdatedAt)) {
            $this->addConflict($result, 'indicator', $uuid, $this->resolveServerTimestamp($indicator), $clientUpdatedAt);
            return;
        }

        if ($clientDeletedAt) {
            if ($indicator && !$indicator->trashed()) {
                $indicator->delete();
                $result['applied']['indicators']++;
            }
            return;
        }

        $categoryUuid = $payload['category_uuid'] ?? null;
        if (!is_string($categoryUuid) || $categoryUuid === '') {
            $this->addError($result, 'indicator', $uuid, 'Missing category_uuid');
            return;
        }

        $category = Category::withTrashed()->where('uuid', $categoryUuid)->first();
        if (!$category || $category->trashed()) {
            $this->addError($result, 'indicator', $uuid, 'Category not found or deleted');
            return;
        }

        if (!$indicator) {
            $missing = $this->missingFields($payload, [
                'title',
                'value',
                'unit',
                'year',
                'is_higher_better',
            ]);

            if (!empty($missing)) {
                $this->addError($result, 'indicator', $uuid, 'Missing fields for create: ' . implode(', ', $missing));
                return;
            }

            $indicator = new Indicator(['uuid' => $uuid]);
        }

        if ($indicator->trashed()) {
            $indicator->restore();
        }

        $indicator->fill($this->extractFields($payload, [
            'title',
            'value',
            'unit',
            'year',
            'trend',
            'is_higher_better',
            'description',
            'image_path',
        ]));

        $indicator->category_id = $category->id;
        $indicator->save();
        $result['applied']['indicators']++;
    }

    private function applyPhenomenon(array $payload, array &$result): void
    {
        $uuid = $payload['uuid'] ?? null;
        if (!is_string($uuid) || $uuid === '') {
            $this->addError($result, 'phenomenon', $uuid, 'Missing uuid');
            return;
        }

        $clientUpdatedAt = $this->parseTimestamp($payload['updated_at'] ?? null);
        $clientDeletedAt = $this->parseTimestamp($payload['deleted_at'] ?? null);

        $phenomenon = Phenomenon::withTrashed()->where('uuid', $uuid)->first();

        if ($phenomenon && $this->hasConflict($phenomenon, $clientUpdatedAt)) {
            $this->addConflict($result, 'phenomenon', $uuid, $this->resolveServerTimestamp($phenomenon), $clientUpdatedAt);
            return;
        }

        if ($clientDeletedAt) {
            if ($phenomenon && !$phenomenon->trashed()) {
                $phenomenon->delete();
                $result['applied']['phenomena']++;
            }
            return;
        }

        $indicatorUuid = $payload['indicator_uuid'] ?? null;
        if (!is_string($indicatorUuid) || $indicatorUuid === '') {
            $this->addError($result, 'phenomenon', $uuid, 'Missing indicator_uuid');
            return;
        }

        $indicator = Indicator::withTrashed()->where('uuid', $indicatorUuid)->first();
        if (!$indicator || $indicator->trashed()) {
            $this->addError($result, 'phenomenon', $uuid, 'Indicator not found or deleted');
            return;
        }

        if (!$phenomenon) {
            $missing = $this->missingFields($payload, [
                'title',
                'description',
                'impact',
            ]);

            if (!empty($missing)) {
                $this->addError($result, 'phenomenon', $uuid, 'Missing fields for create: ' . implode(', ', $missing));
                return;
            }

            $phenomenon = new Phenomenon(['uuid' => $uuid]);
        }

        if (array_key_exists('impact', $payload) && !in_array($payload['impact'], ['positive', 'negative', 'neutral'], true)) {
            $this->addError($result, 'phenomenon', $uuid, 'Invalid impact value');
            return;
        }

        if ($phenomenon->trashed()) {
            $phenomenon->restore();
        }

        $phenomenon->fill($this->extractFields($payload, [
            'title',
            'description',
            'impact',
            'source',
            'order',
        ]));

        $phenomenon->indicator_id = $indicator->id;
        $phenomenon->save();
        $result['applied']['phenomena']++;
    }

    private function extractFields(array $payload, array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }

        return $data;
    }

    private function missingFields(array $payload, array $fields): array
    {
        $missing = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function hasConflict(Model $model, ?Carbon $clientUpdatedAt): bool
    {
        if (!$clientUpdatedAt) {
            return false;
        }

        $serverUpdatedAt = $this->resolveServerTimestamp($model);

        if (!$serverUpdatedAt) {
            return false;
        }

        return $serverUpdatedAt->gt($clientUpdatedAt);
    }

    private function addConflict(array &$result, string $type, ?string $uuid, ?Carbon $serverUpdatedAt, ?Carbon $clientUpdatedAt): void
    {
        $result['conflicts'][] = [
            'type' => $type,
            'uuid' => $uuid,
            'server_updated_at' => $serverUpdatedAt?->toIso8601String(),
            'client_updated_at' => $clientUpdatedAt?->toIso8601String(),
        ];
    }

    private function addError(array &$result, string $type, ?string $uuid, string $message): void
    {
        $result['errors'][] = [
            'type' => $type,
            'uuid' => $uuid,
            'message' => $message,
        ];
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

        return $query->where(function ($innerQuery) use ($since) {
            $innerQuery->where('updated_at', '>', $since)
                ->orWhere('deleted_at', '>', $since);
        });
    }

    private function mapCategory(Category $category): array
    {
        return [
            'uuid' => $category->uuid,
            'name' => $category->name,
            'icon' => $category->icon,
            'updated_at' => $category->updated_at?->toIso8601String(),
            'deleted_at' => $category->deleted_at?->toIso8601String(),
        ];
    }

    private function resolveServerTimestamp(Model $model): ?Carbon
    {
        $updatedAt = $model->updated_at instanceof Carbon ? $model->updated_at : null;
        $deletedAt = $model->deleted_at instanceof Carbon ? $model->deleted_at : null;

        if ($updatedAt && $deletedAt) {
            return $deletedAt->gt($updatedAt) ? $deletedAt : $updatedAt;
        }

        return $updatedAt ?? $deletedAt;
    }

    private function mapIndicator(Indicator $indicator): array
    {
        return [
            'uuid' => $indicator->uuid,
            'category_uuid' => $indicator->category?->uuid,
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

    private function mapPhenomenon(Phenomenon $phenomenon): array
    {
        return [
            'uuid' => $phenomenon->uuid,
            'indicator_uuid' => $phenomenon->indicator?->uuid,
            'title' => $phenomenon->title,
            'description' => $phenomenon->description,
            'impact' => $phenomenon->impact,
            'source' => $phenomenon->source,
            'order' => $phenomenon->order,
            'updated_at' => $phenomenon->updated_at?->toIso8601String(),
            'deleted_at' => $phenomenon->deleted_at?->toIso8601String(),
        ];
    }
}

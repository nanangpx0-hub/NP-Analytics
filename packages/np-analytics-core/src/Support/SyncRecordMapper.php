<?php

namespace NpAnalytics\Core\Support;

use NpAnalytics\Core\Models\Category;
use NpAnalytics\Core\Models\Indicator;
use NpAnalytics\Core\Models\Phenomenon;

/**
 * Satu-satunya tempat transformasi model domain menjadi payload sinkronisasi,
 * dipakai baik oleh server (record lengkap) maupun perangkat (payload push).
 */
final class SyncRecordMapper
{
    public static function category(Category $category): array
    {
        return [
            'uuid' => $category->uuid,
            'name' => $category->name,
            'icon' => $category->icon,
            'server_version' => (int) $category->server_version,
            'updated_at' => $category->updated_at?->toIso8601String(),
            'deleted_at' => $category->deleted_at?->toIso8601String(),
        ];
    }

    public static function indicator(Indicator $indicator): array
    {
        return array_merge(self::indicatorAttributes($indicator), [
            'uuid' => $indicator->uuid,
            'category_uuid' => $indicator->category?->uuid,
            'server_version' => (int) $indicator->server_version,
            'updated_at' => $indicator->updated_at?->toIso8601String(),
            'deleted_at' => $indicator->deleted_at?->toIso8601String(),
        ]);
    }

    public static function phenomenon(Phenomenon $phenomenon): array
    {
        return array_merge(self::phenomenonAttributes($phenomenon), [
            'uuid' => $phenomenon->uuid,
            'indicator_uuid' => $phenomenon->indicator?->uuid,
            'server_version' => (int) $phenomenon->server_version,
            'updated_at' => $phenomenon->updated_at?->toIso8601String(),
            'deleted_at' => $phenomenon->deleted_at?->toIso8601String(),
        ]);
    }

    public static function record(string $type, Category|Indicator|Phenomenon $model): array
    {
        return [
            'type' => $type,
            'data' => match ($type) {
                'category' => self::category($model),
                'indicator' => self::indicator($model),
                'phenomenon' => self::phenomenon($model),
            },
        ];
    }

    public static function pushCategory(Category $category): array
    {
        return [
            'uuid' => $category->uuid,
            'base_version' => (int) ($category->base_version ?? 0),
            'name' => $category->name,
            'icon' => $category->icon,
            'deleted_at' => $category->deleted_at?->toIso8601String(),
        ];
    }

    public static function pushIndicator(Indicator $indicator, string $categoryUuid): array
    {
        return array_merge(self::indicatorAttributes($indicator), [
            'uuid' => $indicator->uuid,
            'base_version' => (int) ($indicator->base_version ?? 0),
            'category_uuid' => $categoryUuid,
            'deleted_at' => $indicator->deleted_at?->toIso8601String(),
        ]);
    }

    public static function pushPhenomenon(Phenomenon $phenomenon, string $indicatorUuid): array
    {
        return array_merge(self::phenomenonAttributes($phenomenon), [
            'uuid' => $phenomenon->uuid,
            'base_version' => (int) ($phenomenon->base_version ?? 0),
            'indicator_uuid' => $indicatorUuid,
            'deleted_at' => $phenomenon->deleted_at?->toIso8601String(),
        ]);
    }

    private static function indicatorAttributes(Indicator $indicator): array
    {
        return [
            'series_key' => $indicator->series_key
                ?: DeterministicUuid::seriesKey((string) $indicator->title),
            'title' => $indicator->title,
            'value' => $indicator->value,
            'unit' => $indicator->unit,
            'year' => $indicator->year,
            'trend' => $indicator->trend,
            'is_higher_better' => (bool) $indicator->is_higher_better,
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
        ];
    }

    private static function phenomenonAttributes(Phenomenon $phenomenon): array
    {
        return [
            'title' => $phenomenon->title,
            'description' => $phenomenon->description,
            'impact' => $phenomenon->impact,
            'source' => $phenomenon->source,
            'order' => (int) $phenomenon->order,
            'insight_type' => $phenomenon->insight_type,
            'source_document' => $phenomenon->source_document,
            'source_page' => $phenomenon->source_page,
            'published_at' => $phenomenon->published_at?->toDateString(),
            'author' => $phenomenon->author,
            'quality_note' => $phenomenon->quality_note,
        ];
    }
}

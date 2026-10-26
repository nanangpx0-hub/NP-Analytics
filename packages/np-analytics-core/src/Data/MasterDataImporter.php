<?php

namespace NpAnalytics\Core\Data;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NpAnalytics\Core\Enums\Impact;
use NpAnalytics\Core\Support\DeterministicUuid;
use RuntimeException;

/**
 * Memuat data master strategis dari berkas JSON bersama dan melakukan upsert
 * berdasarkan UUID deterministik, sehingga seeder di kedua aplikasi selalu
 * menghasilkan data yang identik tanpa duplikasi.
 */
final class MasterDataImporter
{
    private const AGENCY = 'BPS Kabupaten Jember';

    private const QUALITY_NOTE = 'Disintesis dari data empiris resmi BPS Jember dan dokumen rencana aksi terpadu 2026-2030.';

    /**
     * @param  class-string<Model>  $categoryModel
     * @param  class-string<Model>  $indicatorModel
     * @param  class-string<Model>  $phenomenonModel
     */
    public function __construct(
        private readonly string $categoryModel,
        private readonly string $indicatorModel,
        private readonly string $phenomenonModel,
        private readonly ?string $dataPath = null,
    ) {}

    public static function defaultDataPath(): string
    {
        return dirname(__DIR__, 2).'/database/data/master-strategic-data.json';
    }

    /**
     * @param  bool  $updateExisting  Timpa record yang sudah ada dengan nilai master.
     * @param  array<string, mixed>  $newRecordState  Atribut tambahan khusus record baru
     *                                                (misalnya penanda dirty/server_version milik aplikasi mobile).
     * @return array<string, int>
     */
    public function import(bool $updateExisting = true, array $newRecordState = []): array
    {
        $data = $this->loadData();

        $stats = [
            'categories' => 0,
            'indicators' => 0,
            'phenomena' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        DB::transaction(function () use ($data, $updateExisting, $newRecordState, &$stats): void {
            $categoryIds = $this->importCategories($data['categories'], $updateExisting, $newRecordState, $stats);
            $this->importIndicators($data['indicators'], $categoryIds, $updateExisting, $newRecordState, $stats);
            $this->importPhenomena($data['phenomena'], $updateExisting, $newRecordState, $stats);
        });

        return $stats;
    }

    private function loadData(): array
    {
        $path = $this->dataPath ?? self::defaultDataPath();

        if (! is_file($path)) {
            throw new RuntimeException('Berkas data master tidak ditemukan: '.$path);
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! isset($data['categories'], $data['indicators'], $data['phenomena'])) {
            throw new RuntimeException('Struktur berkas data master tidak valid.');
        }

        return $data;
    }

    /**
     * @return array<string, int> ID kategori berdasarkan nama ternormalisasi.
     */
    private function importCategories(array $rows, bool $updateExisting, array $newRecordState, array &$stats): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $uuid = DeterministicUuid::category($name);
            $class = $this->categoryModel;

            $model = $class::withTrashed()->where('uuid', $uuid)->first()
                ?? $class::withTrashed()->where('name', $name)->first();

            if ($model) {
                if ($updateExisting) {
                    $model->fill(['name' => $name, 'icon' => $row['icon'] ?? null]);
                    $this->persist($model, $stats);
                } else {
                    $stats['skipped']++;
                }
            } else {
                $model = new $class;
                $model->forceFill(array_merge([
                    'uuid' => $uuid,
                    'name' => $name,
                    'icon' => $row['icon'] ?? null,
                ], $newRecordState));
                $model->save();
                $stats['created']++;
            }

            $ids[DeterministicUuid::normalizeName($name)] = (int) $model->getKey();
            $stats['categories']++;
        }

        return $ids;
    }

    private function importIndicators(array $rows, array $categoryIds, bool $updateExisting, array $newRecordState, array &$stats): void
    {
        foreach ($rows as $row) {
            $title = (string) ($row['title'] ?? '');
            $year = (int) ($row['year'] ?? 0);
            $categoryName = (string) ($row['category'] ?? '');
            $categoryId = $categoryIds[DeterministicUuid::normalizeName($categoryName)] ?? null;

            if ($title === '' || $year === 0 || $categoryId === null) {
                continue;
            }

            $seriesKey = DeterministicUuid::seriesKey($title);
            $uuid = DeterministicUuid::indicator($seriesKey, $year);
            $class = $this->indicatorModel;

            $model = $class::withTrashed()->where('uuid', $uuid)->first()
                ?? $class::withTrashed()
                    ->where('series_key', $seriesKey)
                    ->where('year', $year)
                    ->first();

            [$periodStart, $periodEnd] = $this->periodFor($year, (string) ($row['frequency'] ?? 'annual'));

            $attributes = [
                'category_id' => $categoryId,
                'series_key' => $seriesKey,
                'title' => $title,
                'value' => $row['value'] ?? null,
                'unit' => $row['unit'] ?? null,
                'year' => $year,
                'trend' => $row['trend'] ?? null,
                'is_higher_better' => (bool) ($row['is_higher_better'] ?? false),
                'description' => $row['description'] ?? null,
                'frequency' => $row['frequency'] ?? 'annual',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'source_document' => $row['source_document'] ?? null,
                'source_table' => $row['source_table'] ?? null,
                'quality_note' => $row['quality_note'] ?? null,
            ];

            if (array_key_exists('region_level', $row)) {
                $attributes['region_level'] = $row['region_level'];
            }

            if ($model) {
                if ($updateExisting) {
                    $model->fill($attributes);
                    $this->persist($model, $stats);
                } else {
                    $stats['skipped']++;
                }
            } else {
                $model = new $class;
                $model->forceFill(array_merge([
                    'uuid' => $uuid,
                    'region_level' => $row['region_level'] ?? 'kabupaten',
                ], $attributes, $newRecordState));
                $model->save();
                $stats['created']++;
            }

            $stats['indicators']++;
        }
    }

    private function importPhenomena(array $rows, bool $updateExisting, array $newRecordState, array &$stats): void
    {
        $order = 0;

        foreach ($rows as $row) {
            $title = (string) ($row['title'] ?? '');
            $indicatorTitle = (string) ($row['indicator'] ?? '');

            if ($title === '' || $indicatorTitle === '') {
                continue;
            }

            $order++;
            $indicator = $this->findLatestIndicator($indicatorTitle);

            if (! $indicator) {
                continue;
            }

            $seriesKey = (string) $indicator->series_key;
            $uuid = $seriesKey !== ''
                ? DeterministicUuid::phenomenon($seriesKey, (int) $indicator->year, $order)
                : (string) Str::uuid();

            $class = $this->phenomenonModel;

            $model = $class::withTrashed()->where('uuid', $uuid)->first()
                ?? $class::withTrashed()
                    ->where('indicator_id', $indicator->getKey())
                    ->where('order', $order)
                    ->first();

            $attributes = [
                'indicator_id' => $indicator->getKey(),
                'title' => $title,
                'description' => $row['description'] ?? null,
                'impact' => $row['impact'] ?? Impact::Neutral->value,
                'source' => $row['source'] ?? null,
                'order' => $order,
                'insight_type' => $row['insight_type'] ?? null,
                'source_document' => $row['source_document'] ?? null,
                'author' => self::AGENCY,
                'quality_note' => self::QUALITY_NOTE,
            ];

            if ($model) {
                if ($updateExisting) {
                    $attributes['published_at'] = $model->published_at?->toDateString()
                        ?? CarbonImmutable::now()->toDateString();
                    $model->fill($attributes);
                    $this->persist($model, $stats);
                } else {
                    $stats['skipped']++;
                }
            } else {
                $model = new $class;
                $model->forceFill(array_merge($attributes, [
                    'uuid' => $uuid,
                    'published_at' => CarbonImmutable::now()->toDateString(),
                ], $newRecordState));
                $model->save();
                $stats['created']++;
            }

            $stats['phenomena']++;
        }
    }

    private function findLatestIndicator(string $title): ?Model
    {
        $class = $this->indicatorModel;
        $seriesKey = DeterministicUuid::seriesKey($title);

        return $class::withTrashed()
            ->where('series_key', $seriesKey)
            ->orderByDesc('year')
            ->first()
            ?? $class::withTrashed()
                ->where('title', $title)
                ->orderByDesc('year')
                ->first();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodFor(int $year, string $frequency): array
    {
        $frequency = strtolower($frequency);
        $start = CarbonImmutable::create($year, 1, 1);
        $end = CarbonImmutable::create($year, 12, 31);

        if ($frequency === 'monthly') {
            $start = CarbonImmutable::create($year, 9, 1);
            $end = $start->endOfMonth();
        } elseif ($frequency === 'semiannual') {
            $start = CarbonImmutable::create($year, 3, 1);
            $end = $start->endOfMonth();
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    private function persist(Model $model, array &$stats): void
    {
        if ($model->isDirty()) {
            $model->save();
            $stats['updated']++;
        } else {
            $stats['skipped']++;
        }
    }
}

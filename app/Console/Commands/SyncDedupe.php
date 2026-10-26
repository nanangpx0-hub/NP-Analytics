<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Indicator;
use App\Models\Phenomenon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NpAnalytics\Core\Support\DeterministicUuid;

class SyncDedupe extends Command
{
    protected $signature = 'sync:dedupe';

    protected $description = 'Gabungkan data duplikat lokal dan selaraskan UUID deterministik';

    public function handle(): int
    {
        $stats = [
            'categories_merged' => 0,
            'indicators_merged' => 0,
            'phenomena_merged' => 0,
            'rekeyed' => 0,
        ];

        DB::transaction(function () use (&$stats): void {
            $stats['categories_merged'] += $this->dedupeCategories($stats);
            $stats['indicators_merged'] += $this->dedupeIndicators($stats);
            $stats['phenomena_merged'] += $this->dedupePhenomena($stats);
        });

        $this->info(sprintf(
            'Dedupe selesai: kategori %d, indikator %d, fenomena %d digabung; %d record memakai UUID deterministik.',
            $stats['categories_merged'],
            $stats['indicators_merged'],
            $stats['phenomena_merged'],
            $stats['rekeyed'],
        ));
        $this->line('Jalankan sync:dedupe juga di server agar UUID kedua sisi kembali sejajar.');

        return self::SUCCESS;
    }

    private function dedupeCategories(array &$stats): int
    {
        $merged = 0;
        $groups = Category::withTrashed()
            ->get()
            ->filter(fn (Category $category): bool => ! $category->trashed())
            ->groupBy(fn (Category $category): string => DeterministicUuid::normalizeName((string) $category->name));

        foreach ($groups as $group) {
            /** @var Category $keeper */
            $keeper = $this->newest($group);

            foreach ($group->where('id', '!=', $keeper->id) as $duplicate) {
                Indicator::withTrashed()
                    ->where('category_id', $duplicate->id)
                    ->get()
                    ->each(fn (Indicator $indicator) => $this->moveChild($indicator, ['category_id' => $keeper->id]));

                $duplicate->deleteQuietly();
                $this->markCleanAndSynced($duplicate);
                $merged++;
            }

            $uuid = DeterministicUuid::category((string) $keeper->name);

            if ($keeper->uuid !== $uuid) {
                $keeper->uuid = $uuid;
                $keeper->saveQuietly();
                $stats['rekeyed']++;
            }
        }

        return $merged;
    }

    private function dedupeIndicators(array &$stats): int
    {
        $merged = 0;
        $groups = Indicator::withTrashed()
            ->get()
            ->filter(fn (Indicator $indicator): bool => ! $indicator->trashed())
            ->groupBy(fn (Indicator $indicator): string => DeterministicUuid::seriesKey((string) $indicator->title).'|'.$indicator->year);

        foreach ($groups as $group) {
            /** @var Indicator $keeper */
            $keeper = $this->newest($group);
            $baseKey = DeterministicUuid::seriesKey((string) $keeper->title);

            foreach ($group->where('id', '!=', $keeper->id) as $duplicate) {
                Phenomenon::withTrashed()
                    ->where('indicator_id', $duplicate->id)
                    ->get()
                    ->each(fn (Phenomenon $phenomenon) => $this->moveChild($phenomenon, ['indicator_id' => $keeper->id]));

                $duplicate->series_key = Str::limit($baseKey, 120, '').'-duplicate-'.$duplicate->id;
                $duplicate->saveQuietly();
                $duplicate->deleteQuietly();
                $this->markCleanAndSynced($duplicate);
                $merged++;
            }

            $uuid = DeterministicUuid::indicator($baseKey, (int) $keeper->year);

            if ($keeper->series_key !== $baseKey || $keeper->uuid !== $uuid) {
                $keeper->series_key = $baseKey;
                $keeper->uuid = $uuid;
                $keeper->saveQuietly();
                $stats['rekeyed']++;
            }
        }

        return $merged;
    }

    private function dedupePhenomena(array &$stats): int
    {
        $merged = 0;
        $groups = Phenomenon::withTrashed()
            ->get()
            ->filter(fn (Phenomenon $phenomenon): bool => ! $phenomenon->trashed())
            ->groupBy(fn (Phenomenon $phenomenon): string => $phenomenon->indicator_id.'|'.$phenomenon->order);

        foreach ($groups as $group) {
            /** @var Phenomenon $keeper */
            $keeper = $this->newest($group);

            foreach ($group->where('id', '!=', $keeper->id) as $duplicate) {
                $duplicate->deleteQuietly();
                $this->markCleanAndSynced($duplicate);
                $merged++;
            }

            $indicator = Indicator::withTrashed()->find($keeper->indicator_id);

            if (! $indicator || ! $indicator->series_key) {
                continue;
            }

            $uuid = DeterministicUuid::phenomenon(
                (string) $indicator->series_key,
                (int) $indicator->year,
                (int) $keeper->order,
            );

            if ($keeper->uuid !== $uuid) {
                $keeper->uuid = $uuid;
                $keeper->saveQuietly();
                $stats['rekeyed']++;
            }
        }

        return $merged;
    }

    private function newest($models)
    {
        return $models
            ->sortByDesc(fn ($model): string => sprintf(
                '%020d-%020d',
                $model->updated_at?->getTimestamp() ?? 0,
                $model->getKey(),
            ))
            ->first();
    }

    /**
     * Memindahkan child ke keeper tanpa mengubah dirty flag maupun updated_at,
     * supaya pemilihan record terbaru dan antrean push tetap akurat.
     */
    private function moveChild($child, array $attributes): void
    {
        $updatedAt = $child->updated_at;

        $child->fill($attributes);
        $child->saveQuietly();

        if ($updatedAt) {
            DB::table($child->getTable())
                ->where($child->getKeyName(), $child->getKey())
                ->update(['updated_at' => $updatedAt]);

            $child->updated_at = $updatedAt;
        }
    }

    private function markCleanAndSynced($model): void
    {
        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update([
                'dirty' => false,
                'base_version' => (int) $model->server_version,
            ]);

        $model->forceFill([
            'dirty' => false,
            'base_version' => (int) $model->server_version,
        ]);
    }
}

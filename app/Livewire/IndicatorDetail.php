<?php

namespace App\Livewire;

use App\Models\Indicator;
use App\Services\GeminiInsightService;
use Illuminate\Support\Collection;
use Livewire\Component;

class IndicatorDetail extends Component
{
    public Indicator $indicator;

    public bool $showAllPhenomena = false;

    public ?array $aiInsight = null;

    /**
     * Mount the component with the indicator ID.
     */
    public function mount(int $id): void
    {
        // Eager load relasi phenomena dan category untuk menghindari N+1 query
        $this->indicator = Indicator::with(['phenomena', 'category'])
            ->findOrFail($id);
    }

    /**
     * Toggle untuk menampilkan semua phenomena.
     */
    public function togglePhenomena(): void
    {
        $this->showAllPhenomena = ! $this->showAllPhenomena;
    }

    public function generateAiInsight(GeminiInsightService $gemini): void
    {
        // Livewire menghidrasi ulang model dari database, jadi relasi perlu dimuat lagi.
        $this->indicator->loadMissing(['category', 'phenomena']);

        $this->aiInsight = $gemini->analyzeIndicator(
            $this->indicator,
            $this->indicator->history,
            true,
        );
    }

    /**
     * Render the component view.
     *
     * Mengirimkan data indikator beserta relasi phenomena ke view.
     */
    public function render()
    {
        // Group phenomena berdasarkan impact (positive/negative/neutral)
        $phenomenaGrouped = $this->indicator->phenomena->groupBy('impact');

        // Hitung statistik phenomena
        $phenomenaStats = [
            'total' => $this->indicator->phenomena->count(),
            'positive' => $phenomenaGrouped->get('positive', collect())->count(),
            'negative' => $phenomenaGrouped->get('negative', collect())->count(),
            'neutral' => $phenomenaGrouped->get('neutral', collect())->count(),
        ];

        // Data untuk trend visualization
        $trendData = [
            'value' => $this->indicator->trend,
            'color' => $this->indicator->trend_color,
            'icon' => $this->indicator->trend_icon,
            'formatted' => $this->indicator->formatted_trend,
            'is_positive' => $this->isTrendPositive(),
        ];

        // Riwayat seri waktu indikator yang sama lintas tahun (berbasis series_key)
        $history = $this->indicator->history;
        $chart = $this->chartFor($history);
        $gemini = app(GeminiInsightService::class);
        // Livewire menggabungkan properti publik setelah data view, jadi hasil
        // cache tidak boleh dikirim dengan nama kunci "aiInsight".
        $resolvedAiInsight = $this->aiInsight ?? $gemini->cachedForIndicator($this->indicator, $history);

        return view('livewire.indicator-detail', [
            'indicator' => $this->indicator,
            'category' => $this->indicator->category,
            'phenomena' => $this->showAllPhenomena
                ? $this->indicator->phenomena
                : $this->indicator->phenomena->take(4),
            'phenomenaGrouped' => $phenomenaGrouped,
            'phenomenaStats' => $phenomenaStats,
            'trendData' => $trendData,
            'history' => $history,
            'chart' => $chart,
            'hasMorePhenomena' => $this->indicator->phenomena->count() > 4,
            'resolvedAiInsight' => $resolvedAiInsight,
            'geminiConfigured' => $gemini->isConfigured(),
        ])->layout('layouts.app', [
            'title' => $this->indicator->title,
        ]);
    }

    /**
     * Bangun koordinat SVG time-series dari riwayat indikator.
     * Grafik dirender lokal tanpa CDN maupun dependensi JavaScript.
     */
    private function chartFor(Collection $history): ?array
    {
        $points = $history
            ->filter(fn (Indicator $item): bool => $item->value !== null)
            ->sortBy('year')
            ->values();

        if ($points->count() < 2) {
            return null;
        }

        $values = $points->map(fn (Indicator $item): float => (float) $item->value);
        $min = (float) $values->min();
        $max = (float) $values->max();
        $range = $max - $min;

        if ($range <= 0.0) {
            $range = max(abs($max) * 0.1, 1.0);
            $min -= $range / 2;
            $max += $range / 2;
        } else {
            $min -= $range * 0.12;
            $max += $range * 0.12;
        }

        $width = 720;
        $height = 260;
        $left = 76;
        $right = 24;
        $top = 20;
        $bottom = 42;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $count = $points->count();
        $step = $plotWidth / ($count - 1);

        $plotted = $points->map(function (Indicator $item, int $index) use ($left, $top, $plotHeight, $step, $min, $max): array {
            $ratio = ($max - $min) > 0 ? (((float) $item->value - $min) / ($max - $min)) : 0.5;

            return [
                'x' => round($left + ($index * $step), 1),
                'y' => round($top + $plotHeight - ($ratio * $plotHeight), 1),
                'year' => (int) $item->year,
                'label' => $this->formatChartValue((float) $item->value),
                'current' => (int) $item->id === (int) $this->indicator->id,
            ];
        })->all();

        $line = implode(' ', array_map(
            fn (array $point): string => $point['x'].','.$point['y'],
            $plotted,
        ));
        $lastX = $plotted[count($plotted) - 1]['x'];
        $baseline = $top + $plotHeight;

        $grid = [];
        for ($index = 0; $index <= 4; $index++) {
            $grid[] = [
                'y' => round($top + ($plotHeight * $index / 4), 1),
                'label' => $this->formatAxisValue($max - (($max - $min) * $index / 4)),
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'left' => $left,
            'right' => $right,
            'grid' => $grid,
            'line' => $line,
            'area' => $left.','.$baseline.' '.$line.' '.$lastX.','.$baseline,
            'points' => $plotted,
        ];
    }

    private function formatChartValue(float $value): string
    {
        return number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.');
    }

    private function formatAxisValue(float $value): string
    {
        $absolute = abs($value);

        if ($absolute >= 1_000_000) {
            return number_format($value / 1_000_000, 1, ',', '.').' jt';
        }

        if ($absolute >= 1_000) {
            return number_format($value / 1_000, 0, ',', '.').' rb';
        }

        return number_format($value, $absolute < 10 ? 2 : 1, ',', '.');
    }

    /**
     * Determine if the trend is positive based on Smart Trend Logic.
     *
     * Smart Trend Logic:
     * - is_higher_better = true (PDRB, Padi): Naik = Positif, Turun = Negatif
     * - is_higher_better = false (Kemiskinan, Inflasi): Naik = Negatif, Turun = Positif
     */
    private function isTrendPositive(): bool
    {
        if ($this->indicator->trend === null || $this->indicator->trend == 0) {
            return false; // Netral
        }

        $isUpTrend = $this->indicator->trend > 0;

        if ($this->indicator->is_higher_better) {
            return $isUpTrend; // Naik = positif
        } else {
            return ! $isUpTrend; // Turun = positif
        }
    }
}

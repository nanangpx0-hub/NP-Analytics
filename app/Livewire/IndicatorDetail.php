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
        $this->showAllPhenomena = !$this->showAllPhenomena;
    }

    public function generateAiInsight(GeminiInsightService $gemini): void
    {
        // Livewire menghidrasi ulang model dari database, jadi relasi perlu dimuat lagi.
        $this->indicator->loadMissing(['category', 'phenomena']);

        $this->aiInsight = $gemini->analyzeIndicator(
            $this->indicator,
            $this->historyFor($this->indicator),
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

        // Riwayat seri waktu indikator yang sama lintas tahun
        $history = $this->historyFor($this->indicator);
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
            'hasMorePhenomena' => $this->indicator->phenomena->count() > 4,
            'resolvedAiInsight' => $resolvedAiInsight,
            'geminiConfigured' => $gemini->isConfigured(),
        ])->layout('layouts.app', [
            'title' => $this->indicator->title,
        ]);
    }

    private function historyFor(Indicator $indicator): Collection
    {
        return Indicator::where('title', $indicator->title)
            ->whereNull('deleted_at')
            ->orderBy('year', 'asc')
            ->get();
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
            return !$isUpTrend; // Turun = positif
        }
    }
}

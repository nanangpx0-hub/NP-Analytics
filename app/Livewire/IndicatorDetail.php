<?php

namespace App\Livewire;

use App\Models\Indicator;
use Livewire\Component;

class IndicatorDetail extends Component
{
    public Indicator $indicator;
    public bool $showAllPhenomena = false;

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

    /**
     * Render the component view.
     * 
     * Mengirimkan data indikator beserta relasi phenomena ke view.
     */
    public function render()
    {
        // Group phenomena berdasarkan impact (positive/negative)
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

        return view('livewire.indicator-detail', [
            'indicator' => $this->indicator,
            'category' => $this->indicator->category,
            'phenomena' => $this->showAllPhenomena 
                ? $this->indicator->phenomena 
                : $this->indicator->phenomena->take(3),
            'phenomenaGrouped' => $phenomenaGrouped,
            'phenomenaStats' => $phenomenaStats,
            'trendData' => $trendData,
            'hasMorePhenomena' => $this->indicator->phenomena->count() > 3,
        ])->layout('layouts.app', [
            'title' => $this->indicator->title,
        ]);
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

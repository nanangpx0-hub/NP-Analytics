<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Indicator;
use App\Services\GeminiInsightService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Url(as: 'year', except: '')]
    public $selectedYear;

    public $availableYears = [];

    public ?array $aiInsight = null;

    public function mount()
    {
        // Get available years from indicators
        $this->availableYears = Indicator::distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($this->availableYears)) {
            $this->availableYears = [(int) date('Y')];
        }

        // Set default to latest year with comprehensive indicators (2025 or 2026)
        if (empty($this->selectedYear) || ! in_array((int) $this->selectedYear, array_map('intval', $this->availableYears), true)) {
            $this->selectedYear = $this->availableYears[0];
        }
    }

    public function updatedSelectedYear(): void
    {
        $this->aiInsight = null;
    }

    public function generateAiInsight(GeminiInsightService $gemini): void
    {
        $categories = $this->categoriesForYear();

        $this->aiInsight = $gemini->analyzeDashboard(
            (int) $this->selectedYear,
            $categories,
            $this->buildStats($categories),
            true,
        );
    }

    public function render()
    {
        $categories = $this->categoriesForYear();
        $stats = $this->buildStats($categories);
        $gemini = app(GeminiInsightService::class);
        // Jangan memakai nama kunci yang sama dengan properti publik: Livewire
        // menggabungkan properti setelah data view sehingga nilai null menimpa cache.
        $resolvedAiInsight = $this->aiInsight ?? $gemini->cachedForDashboard(
            (int) $this->selectedYear,
            $categories,
            $stats,
        );

        return view('livewire.dashboard', [
            'categories' => $categories,
            'stats' => $stats,
            'resolvedAiInsight' => $resolvedAiInsight,
            'geminiConfigured' => $gemini->isConfigured(),
        ])->layout('layouts.app', [
            'title' => 'Dashboard',
        ]);
    }

    private function categoriesForYear()
    {
        // Hanya kategori dengan indikator pada tahun terpilih, relasi dimuat sekali.
        return Category::with(['indicators' => function ($query) {
            $query->where('year', $this->selectedYear);
        }])
            ->whereHas('indicators', function ($query) {
                $query->where('year', $this->selectedYear);
            })
            ->get();
    }

    private function buildStats($categories): array
    {
        $indicators = $categories->flatMap->indicators;

        return [
            'total_categories' => $categories->count(),
            'total_indicators' => $indicators->count(),
            'positive_trends' => $indicators->filter(function (Indicator $indicator): bool {
                if ($indicator->trend === null) {
                    return false;
                }

                return $indicator->is_higher_better
                    ? $indicator->trend > 0
                    : $indicator->trend < 0;
            })->count(),
            'negative_trends' => $indicators->filter(function (Indicator $indicator): bool {
                if ($indicator->trend === null) {
                    return false;
                }

                return $indicator->is_higher_better
                    ? $indicator->trend < 0
                    : $indicator->trend > 0;
            })->count(),
        ];
    }
}

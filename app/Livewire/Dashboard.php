<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Indicator;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public $selectedYear;
    public $availableYears = [];

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

        // Set default to latest year
        $this->selectedYear = $this->availableYears[0];
    }

    public function render()
    {
        $categories = Category::with(['indicators' => function ($query) {
            $query->where('year', $this->selectedYear);
        }])->get();

        $stats = [
            'total_categories' => Category::count(),
            'total_indicators' => Indicator::where('year', $this->selectedYear)->count(),
            'positive_trends' => Indicator::where('year', $this->selectedYear)
                ->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('is_higher_better', true)->where('trend', '>', 0);
                    })->orWhere(function ($inner) {
                        $inner->where('is_higher_better', false)->where('trend', '<', 0);
                    });
                })->count(),
            'negative_trends' => Indicator::where('year', $this->selectedYear)
                ->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('is_higher_better', true)->where('trend', '<', 0);
                    })->orWhere(function ($inner) {
                        $inner->where('is_higher_better', false)->where('trend', '>', 0);
                    });
                })->count(),
        ];

        return view('livewire.dashboard', [
            'categories' => $categories,
            'stats' => $stats,
        ])->layout('layouts.app', [
            'title' => 'Dashboard',
        ]);
    }
}

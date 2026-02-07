<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Indicator;
use Livewire\Component;

class IndicatorList extends Component
{
    public $categoryId = null;
    public $selectedYear;
    public $search = '';

    public function mount($categoryId = null)
    {
        $this->categoryId = $categoryId;
        $this->selectedYear = Indicator::max('year') ?? date('Y');
    }

    public function render()
    {
        $query = Indicator::with(['category', 'phenomena'])
            ->where('year', $this->selectedYear);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        $indicators = $query->orderBy('title')->get();
        $categories = Category::orderBy('name')->get();

        return view('livewire.indicator-list', [
            'indicators' => $indicators,
            'categories' => $categories,
        ])->layout('layouts.app', [
            'title' => 'Daftar Indikator',
        ]);
    }

    public function filterByCategory($categoryId)
    {
        $this->categoryId = $categoryId ?: null;
    }

    public function updatedSearch()
    {
        // Auto-filter on search change (Livewire reactive)
    }
}

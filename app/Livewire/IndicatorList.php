<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Indicator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IndicatorList extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public $search = '';

    #[Url(as: 'category', except: '')]
    public $categoryId = null;

    #[Url(as: 'year', except: '')]
    public $selectedYear = 'all';

    public $availableYears = [];

    public function mount($categoryId = null)
    {
        if ($categoryId) {
            $this->categoryId = $categoryId;
        }

        $this->availableYears = Indicator::distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($this->availableYears)) {
            $this->availableYears = [(int) date('Y')];
        }

        if (empty($this->selectedYear)) {
            $this->selectedYear = 'all';
        }
    }

    public function render()
    {
        $query = Indicator::with(['category']);

        if (! empty($this->selectedYear) && $this->selectedYear !== 'all') {
            $query->where('year', $this->selectedYear);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        $indicators = $query->orderByDesc('year')->orderBy('title')->paginate(12);
        $categories = Category::orderBy('name')->get();

        return view('livewire.indicator-list', [
            'indicators' => $indicators,
            'categories' => $categories,
            'availableYears' => $this->availableYears,
        ])->layout('layouts.app', [
            'title' => 'Daftar Indikator',
        ]);
    }

    public function filterByCategory($categoryId)
    {
        $this->categoryId = $categoryId ?: null;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryId()
    {
        $this->resetPage();
    }

    public function updatedSelectedYear()
    {
        $this->resetPage();
    }
}

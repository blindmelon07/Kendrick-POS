<?php

namespace App\Livewire\DailyMenu;

use App\Models\Category;
use App\Models\DailyMenu;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Management extends Component
{
    public string $selectedDate = '';
    public string $searchProduct = '';
    public ?int $filterCategoryId = null;
    public ?int $addProductId = null;
    public int $addSortOrder = 0;
    public bool $showAddModal = false;

    public function mount(): void
    {
        $this->selectedDate = today()->toDateString();
    }

    #[Computed]
    public function featuredToday(): \Illuminate\Support\Collection
    {
        return DailyMenu::whereDate('featured_date', $this->selectedDate)
            ->with('product.category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function availableProducts(): \Illuminate\Support\Collection
    {
        $alreadyFeatured = DailyMenu::whereDate('featured_date', $this->selectedDate)
            ->pluck('product_id');

        return Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $alreadyFeatured)
            ->whereHas('category', fn ($q) => $q->where('is_menu_item', true))
            ->when($this->filterCategoryId, fn ($q) => $q->where('category_id', $this->filterCategoryId))
            ->when($this->searchProduct, fn ($q) => $q->where('name', 'like', "%{$this->searchProduct}%"))
            ->with('category')
            ->orderBy('name')
            ->take(50)
            ->get();
    }

    #[Computed]
    public function menuCategories(): \Illuminate\Support\Collection
    {
        return Category::where('is_menu_item', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function openAddModal(): void
    {
        $this->addProductId     = null;
        $this->addSortOrder     = $this->featuredToday->count();
        $this->searchProduct    = '';
        $this->filterCategoryId = null;
        $this->showAddModal     = true;
        unset($this->availableProducts);
    }

    public function addToMenu(): void
    {
        $this->validate([
            'addProductId'  => 'required|exists:products,id',
            'addSortOrder'  => 'required|integer|min:0',
            'selectedDate'  => 'required|date',
        ]);

        $exists = DailyMenu::where('product_id', $this->addProductId)
            ->whereDate('featured_date', $this->selectedDate)
            ->exists();

        if (! $exists) {
            DailyMenu::create([
                'product_id'    => $this->addProductId,
                'featured_date' => $this->selectedDate,
                'sort_order'    => $this->addSortOrder,
            ]);
        }

        $this->showAddModal  = false;
        $this->addProductId  = null;
        $this->searchProduct = '';
        unset($this->featuredToday, $this->availableProducts);
        session()->flash('success', 'Dish added to the daily menu.');
    }

    public function remove(int $id): void
    {
        DailyMenu::where('id', $id)
            ->whereDate('featured_date', $this->selectedDate)
            ->delete();

        unset($this->featuredToday, $this->availableProducts);
        session()->flash('success', 'Dish removed from the daily menu.');
    }

    public function updateOrder(int $id, int $order): void
    {
        DailyMenu::where('id', $id)->update(['sort_order' => $order]);
        unset($this->featuredToday);
    }

    public function copyFromDate(string $fromDate): void
    {
        $entries = DailyMenu::whereDate('featured_date', $fromDate)->get();

        foreach ($entries as $entry) {
            $exists = DailyMenu::where('product_id', $entry->product_id)
                ->whereDate('featured_date', $this->selectedDate)
                ->exists();

            if (! $exists) {
                DailyMenu::create([
                    'product_id'    => $entry->product_id,
                    'featured_date' => $this->selectedDate,
                    'sort_order'    => $entry->sort_order,
                ]);
            }
        }

        unset($this->featuredToday, $this->availableProducts);
        session()->flash('success', "Menu copied from {$fromDate}.");
    }

    public function clearDate(): void
    {
        DailyMenu::whereDate('featured_date', $this->selectedDate)->delete();
        unset($this->featuredToday, $this->availableProducts);
        session()->flash('success', 'Daily menu cleared.');
    }

    public function render()
    {
        return view('livewire.daily-menu.management')
            ->layout('layouts.app', ['title' => 'Daily Menu']);
    }
}

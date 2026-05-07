<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Menu extends Component
{
    use WithPagination;

    #[Url]
    public ?int $category = null;

    #[Url]
    public string $search = '';

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('id', $productId)->where('is_active', true)->firstOrFail();
        CartService::add($product);
        session()->flash('cart_message', "\"{$product->name}\" added to cart!");
    }

    public function render()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)->where('stock_quantity', '>', 0)])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->products_count > 0);

        $products = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with('category')
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(12);

        $activeCategory = $this->category ? Category::find($this->category) : null;

        return view('livewire.public.menu', compact('categories', 'products', 'activeCategory'))
            ->layout('layouts.public');
    }
}

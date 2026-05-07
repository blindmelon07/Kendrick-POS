<?php

namespace App\Livewire\Public;

use App\Models\Category;
use App\Models\DailyMenu;
use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class Home extends Component
{
    public function addToCart(int $productId): void
    {
        $product = Product::where('id', $productId)->where('is_active', true)->firstOrFail();
        CartService::add($product);
        $this->dispatch('cart-updated');
        session()->flash('cart_message', "\"{$product->name}\" added to cart!");
    }

    public function render()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)->where('stock_quantity', '>', 0)])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->products_count > 0);

        $featuredProducts = DailyMenu::todaysFeatured();

        $hasDailyMenu = $featuredProducts->isNotEmpty();

        if (! $hasDailyMenu) {
            $featuredProducts = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->with('category')
                ->latest()
                ->take(6)
                ->get();
        }

        return view('livewire.public.home', compact('categories', 'featuredProducts', 'hasDailyMenu'))
            ->layout('layouts.public');
    }
}

<?php

namespace App\Livewire\Public;

use App\Services\CartService;
use Livewire\Component;

class Cart extends Component
{
    public function increment(int $productId): void
    {
        $cart = CartService::get();
        if (isset($cart[$productId])) {
            CartService::update($productId, $cart[$productId]['quantity'] + 1);
        }
    }

    public function decrement(int $productId): void
    {
        $cart = CartService::get();
        if (isset($cart[$productId])) {
            CartService::update($productId, $cart[$productId]['quantity'] - 1);
        }
    }

    public function remove(int $productId): void
    {
        CartService::remove($productId);
    }

    public function clear(): void
    {
        CartService::clear();
    }

    public function render()
    {
        $cartItems = CartService::get();
        $subtotal  = CartService::subtotal();

        return view('livewire.public.cart', compact('cartItems', 'subtotal'))
            ->layout('layouts.public');
    }
}

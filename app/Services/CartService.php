<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    public static function get(): array
    {
        return session('cart', []);
    }

    public static function add(Product $product, int $quantity = 1): void
    {
        $cart = self::get();
        $id = $product->id;

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'product_id' => $id,
                'name'       => $product->name,
                'price'      => (float) $product->selling_price,
                'quantity'   => $quantity,
            ];
        }

        session()->put('cart', $cart);
    }

    public static function update(int $productId, int $quantity): void
    {
        $cart = self::get();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
        }

        session()->put('cart', $cart);
    }

    public static function remove(int $productId): void
    {
        $cart = self::get();
        unset($cart[$productId]);
        session()->put('cart', $cart);
    }

    public static function clear(): void
    {
        session()->forget('cart');
    }

    public static function count(): int
    {
        return (int) collect(self::get())->sum('quantity');
    }

    public static function subtotal(): float
    {
        return (float) collect(self::get())->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public static function isEmpty(): bool
    {
        return empty(self::get());
    }
}

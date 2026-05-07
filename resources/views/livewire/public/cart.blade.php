<div>
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Your Cart</h1>
            <p class="mt-1 text-zinc-500 dark:text-zinc-400">Review your items before checkout</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if(empty($cartItems))
            {{-- Empty state --}}
            <div class="text-center py-20">
                <div class="size-20 bg-zinc-100 dark:bg-zinc-800 rounded-3xl flex items-center justify-center mx-auto mb-5">
                    <svg class="size-10 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-zinc-700 dark:text-zinc-300">Your cart is empty</h3>
                <p class="mt-2 text-zinc-500 dark:text-zinc-400">Add some delicious items from our menu!</p>
                <a href="{{ route('public.menu') }}" wire:navigate
                   class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    Browse Menu
                </a>
            </div>
        @else
            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Cart items --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ count($cartItems) }} {{ Str::plural('item', count($cartItems)) }}</span>
                        <button wire:click="clear" wire:confirm="Clear all items from cart?"
                                class="text-sm text-red-500 hover:text-red-600 font-medium transition-colors">
                            Clear All
                        </button>
                    </div>

                    @foreach($cartItems as $item)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-4 flex items-center gap-4">
                        <div class="size-16 bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="size-8 text-amber-300 dark:text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-zinc-900 dark:text-white truncate">{{ $item['name'] }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">₱{{ number_format($item['price'], 2) }} each</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="decrement({{ $item['product_id'] }})"
                                    class="size-8 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors font-bold text-lg leading-none">
                                −
                            </button>
                            <span class="w-8 text-center font-semibold text-zinc-900 dark:text-white">{{ $item['quantity'] }}</span>
                            <button wire:click="increment({{ $item['product_id'] }})"
                                    class="size-8 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors font-bold text-lg leading-none">
                                +
                            </button>
                        </div>

                        <div class="w-20 text-right shrink-0">
                            <div class="font-bold text-zinc-900 dark:text-white">
                                ₱{{ number_format($item['price'] * $item['quantity'], 2) }}
                            </div>
                        </div>

                        <button wire:click="remove({{ $item['product_id'] }})"
                                class="p-1.5 text-zinc-400 hover:text-red-500 transition-colors shrink-0">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    @endforeach

                    <a href="{{ route('public.menu') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 font-medium mt-2">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Continue Shopping
                    </a>
                </div>

                {{-- Order summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-6 sticky top-24">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-5">Order Summary</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span>₱{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-500 dark:text-zinc-500 text-xs">
                                <span>Delivery fee</span>
                                <span class="italic">Set at checkout</span>
                            </div>
                            <hr class="border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between font-bold text-zinc-900 dark:text-white text-base">
                                <span>Total</span>
                                <span>₱{{ number_format($subtotal, 2) }}</span>
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            @auth
                            <a href="{{ route('public.checkout') }}" wire:navigate
                               class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors shadow-lg shadow-amber-200 dark:shadow-amber-900/30">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Proceed to Checkout
                            </a>
                            @else
                            <div class="text-center text-sm text-zinc-500 dark:text-zinc-400 mb-3">
                                Sign in to complete your order
                            </div>
                            <a href="{{ route('customer.login') }}" wire:navigate
                               class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                                Log in to Checkout
                            </a>
                            <a href="{{ route('customer.register') }}" wire:navigate
                               class="w-full flex items-center justify-center px-6 py-3 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors text-sm">
                                Create Account
                            </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

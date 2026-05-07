<div>
    @if($orderPlaced)
        {{-- Success state --}}
        <div class="max-w-lg mx-auto px-4 py-24 text-center">
            <div class="size-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="size-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Order Placed!</h1>
            <p class="mt-3 text-zinc-500 dark:text-zinc-400">
                Your order has been received. We'll prepare it right away!
            </p>
            <div class="mt-5 inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-5 py-2.5 rounded-xl font-mono font-semibold text-lg">
                {{ $orderReference }}
            </div>
            <p class="mt-3 text-sm text-zinc-400">Save this reference number to track your order.</p>
            <div class="mt-8 flex flex-wrap gap-3 justify-center">
                <a href="{{ route('public.my-orders') }}" wire:navigate
                   class="px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                    View My Orders
                </a>
                <a href="{{ route('public.menu') }}" wire:navigate
                   class="px-6 py-3 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                    Order More
                </a>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">Checkout</h1>
                <p class="mt-1 text-zinc-500 dark:text-zinc-400">Complete your order details below</p>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid lg:grid-cols-5 gap-8">

                {{-- Form --}}
                <div class="lg:col-span-3">
                    <form wire:submit="placeOrder" class="space-y-6">

                        {{-- Contact info --}}
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-6">
                            <h2 class="font-semibold text-zinc-900 dark:text-white mb-5 flex items-center gap-2">
                                <svg class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Contact Information
                            </h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                    <input wire:model="client_name" type="text" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400" placeholder="Your full name">
                                    @error('client_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Phone Number <span class="text-red-500">*</span></label>
                                    <input wire:model="client_phone" type="tel" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400" placeholder="09xx-xxx-xxxx">
                                    @error('client_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                    <input wire:model="client_email" type="email" class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400" placeholder="you@example.com">
                                    @error('client_email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Delivery info --}}
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-6">
                            <h2 class="font-semibold text-zinc-900 dark:text-white mb-5 flex items-center gap-2">
                                <svg class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Delivery Details
                            </h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Delivery Address <span class="text-red-500">*</span></label>
                                    <textarea wire:model="delivery_address" rows="3"
                                              class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"
                                              placeholder="House/Unit No., Street, Barangay, City"></textarea>
                                    @error('delivery_address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Preferred Delivery Date</label>
                                        <input wire:model="delivery_date" type="date" min="{{ date('Y-m-d') }}"
                                               class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400">
                                        @error('delivery_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Delivery Notes <span class="text-xs font-normal text-zinc-400">(optional)</span></label>
                                    <textarea wire:model="delivery_notes" rows="2"
                                              class="w-full px-4 py-2.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"
                                              placeholder="Special instructions, landmarks, etc."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Payment method --}}
                        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-6">
                            <h2 class="font-semibold text-zinc-900 dark:text-white mb-1 flex items-center gap-2">
                                <svg class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Payment Method
                            </h2>
                            <p class="text-xs text-zinc-400 mb-5">Online payments (GCash, Maya, Card) are processed securely via PayMongo.</p>

                            {{-- Cash options --}}
                            <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">Cash</p>
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                @foreach([
                                    ['value' => 'on_delivery', 'label' => 'Cash on Delivery', 'icon' => '💵', 'desc' => 'Pay when order arrives'],
                                    ['value' => 'cash',        'label' => 'Pay at Counter',    'icon' => '💰', 'desc' => 'Pick up and pay in store'],
                                ] as $m)
                                <label class="cursor-pointer">
                                    <input wire:model.live="payment_method" type="radio" value="{{ $m['value'] }}" class="sr-only peer">
                                    <div class="border-2 rounded-xl p-4 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 border-zinc-200 dark:border-zinc-700 hover:border-amber-300">
                                        <div class="text-2xl mb-1">{{ $m['icon'] }}</div>
                                        <div class="text-sm font-semibold text-zinc-800 dark:text-white">{{ $m['label'] }}</div>
                                        <div class="text-xs text-zinc-400 mt-0.5">{{ $m['desc'] }}</div>
                                    </div>
                                </label>
                                @endforeach
                            </div>

                            {{-- Online options --}}
                            <p class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">Online Payment via PayMongo</p>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach([
                                    ['value' => 'gcash',       'label' => 'GCash',       'icon' => '📱', 'desc' => 'e-Wallet'],
                                    ['value' => 'paymaya',     'label' => 'Maya',         'icon' => '🟢', 'desc' => 'e-Wallet'],
                                    ['value' => 'credit_card', 'label' => 'Credit/Debit', 'icon' => '💳', 'desc' => 'Visa, Mastercard'],
                                ] as $m)
                                <label class="cursor-pointer">
                                    <input wire:model.live="payment_method" type="radio" value="{{ $m['value'] }}" class="sr-only peer">
                                    <div class="border-2 rounded-xl p-4 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-zinc-200 dark:border-zinc-700 hover:border-blue-300">
                                        <div class="text-2xl mb-1">{{ $m['icon'] }}</div>
                                        <div class="text-sm font-semibold text-zinc-800 dark:text-white">{{ $m['label'] }}</div>
                                        <div class="text-xs text-zinc-400 mt-0.5">{{ $m['desc'] }}</div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('payment_method') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror

                            {{-- PayMongo badge --}}
                            @if(in_array($payment_method, \App\Livewire\Public\Checkout::ONLINE_METHODS))
                            <div class="mt-4 flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 px-4 py-3 rounded-xl text-sm">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                You'll be redirected to PayMongo's secure payment page to complete your payment.
                            </div>
                            @endif
                        </div>

                        {{-- PayMongo error --}}
                        @if($paymongoError)
                        <div class="flex items-start gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl text-sm">
                            <svg class="size-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                            </svg>
                            {{ $paymongoError }}
                        </div>
                        @endif

                        <button type="submit" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center gap-2 py-4 bg-amber-500 text-white font-bold rounded-xl text-base hover:bg-amber-600 transition-colors shadow-lg shadow-amber-200 dark:shadow-amber-900/30 disabled:opacity-60">
                            <span wire:loading.remove class="flex items-center gap-2">
                                @if(in_array($payment_method, \App\Livewire\Public\Checkout::ONLINE_METHODS))
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                    Pay with PayMongo
                                @else
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Place Order
                                @endif
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </form>
                </div>

                {{-- Order summary --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm p-6 sticky top-24">
                        <h2 class="font-semibold text-zinc-900 dark:text-white mb-5">Order Summary</h2>

                        <div class="space-y-3 mb-5">
                            @foreach($cartItems as $item)
                            <div class="flex items-center gap-3">
                                <div class="size-10 bg-amber-50 dark:bg-amber-900/20 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="size-5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $item['name'] }}</div>
                                    <div class="text-xs text-zinc-400">x{{ $item['quantity'] }}</div>
                                </div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 shrink-0">
                                    ₱{{ number_format($item['price'] * $item['quantity'], 2) }}
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <hr class="border-zinc-200 dark:border-zinc-700 mb-4">

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span>₱{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-500 dark:text-zinc-500 text-xs">
                                <span>Delivery fee</span>
                                <span class="italic">TBD</span>
                            </div>
                        </div>

                        <hr class="border-zinc-200 dark:border-zinc-700 my-4">

                        <div class="flex justify-between font-bold text-zinc-900 dark:text-white text-lg">
                            <span>Total</span>
                            <span>₱{{ number_format($subtotal, 2) }}</span>
                        </div>

                        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 text-xs text-amber-700 dark:text-amber-300">
                            Delivery fee will be confirmed by our team before delivery.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

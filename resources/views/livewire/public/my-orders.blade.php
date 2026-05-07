<div>
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl lg:text-3xl font-bold text-zinc-900 dark:text-white">My Orders</h1>
            <p class="mt-1 text-zinc-500 dark:text-zinc-400">Track all your food orders</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if($orders->isEmpty())
            <div class="text-center py-20">
                <div class="size-20 bg-zinc-100 dark:bg-zinc-800 rounded-3xl flex items-center justify-center mx-auto mb-5">
                    <svg class="size-10 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-zinc-700 dark:text-zinc-300">No orders yet</h3>
                <p class="mt-2 text-zinc-500 dark:text-zinc-400">Place your first order from our menu!</p>
                <a href="{{ route('public.menu') }}" wire:navigate
                   class="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    Browse Menu
                </a>
            </div>
        @else
            <div class="space-y-5">
                @foreach($orders as $order)
                @php
                    $statusColors = [
                        'pending'           => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                        'confirmed'         => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        'preparing'         => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                        'out_for_delivery'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                        'delivered'         => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'cancelled'         => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                    ];
                    $statusLabel = [
                        'pending'           => 'Pending',
                        'confirmed'         => 'Confirmed',
                        'preparing'         => 'Preparing',
                        'out_for_delivery'  => 'Out for Delivery',
                        'delivered'         => 'Delivered',
                        'cancelled'         => 'Cancelled',
                    ];
                    $statusEmoji = [
                        'pending'           => '⏳',
                        'confirmed'         => '✅',
                        'preparing'         => '👨‍🍳',
                        'out_for_delivery'  => '🛵',
                        'delivered'         => '🎉',
                        'cancelled'         => '❌',
                    ];
                @endphp
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-100 dark:border-zinc-700 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex-wrap gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ $order->reference_no }}</span>
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statusColors[$order->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ $statusEmoji[$order->status] ?? '' }} {{ $statusLabel[$order->status] ?? ucfirst($order->status) }}
                                </span>
                            </div>
                            <div class="text-xs text-zinc-400 mt-0.5">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-zinc-900 dark:text-white text-lg">₱{{ number_format($order->total, 2) }}</div>
                            <div class="text-xs text-zinc-400 capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</div>
                        </div>
                    </div>

                    <div class="px-6 py-4">
                        <div class="space-y-2">
                            @foreach($order->items as $item)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="size-6 bg-amber-50 dark:bg-amber-900/20 rounded-md flex items-center justify-center text-xs text-amber-500 font-bold shrink-0">{{ $item->quantity }}</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $item->product_name }}</span>
                                </div>
                                <span class="text-zinc-500 dark:text-zinc-400">₱{{ number_format($item->subtotal, 2) }}</span>
                            </div>
                            @endforeach
                        </div>

                        @if($order->delivery_address)
                        <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-700 flex items-start gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <svg class="size-4 text-zinc-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="line-clamp-1">{{ $order->delivery_address }}</span>
                        </div>
                        @endif

                        @if($order->status === 'cancelled' && $order->cancellation_reason)
                        <div class="mt-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs px-4 py-2.5 rounded-xl">
                            <span class="font-medium">Cancelled:</span> {{ $order->cancellation_reason }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

<div class="max-w-lg mx-auto px-4 py-20 text-center">

    @if($isCancelled)
        {{-- Cancelled --}}
        <div class="size-24 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="size-12 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Payment Cancelled</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">
            Your payment was not completed.
            @if($record)
                Order <span class="font-mono font-semibold">{{ $record->reference_no }}</span> is still <span class="font-medium text-yellow-600">pending payment</span>.
            @endif
        </p>
        <div class="mt-8 flex flex-wrap gap-3 justify-center">
            @if($record)
                <a href="{{ route('public.checkout') }}" wire:navigate
                   class="px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                    Try Again
                </a>
            @endif
            <a href="{{ route('public.menu') }}" wire:navigate
               class="px-6 py-3 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                Back to Menu
            </a>
        </div>

    @elseif($isPaid)
        {{-- Success --}}
        <div class="size-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="size-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Payment Successful!</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">
            Thank you! Your order has been confirmed and is being prepared.
        </p>
        @if($record)
            <div class="mt-4 inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-5 py-2.5 rounded-xl font-mono font-semibold">
                {{ $record->reference_no }}
            </div>
        @endif
        <div class="mt-8 flex flex-wrap gap-3 justify-center">
            <a href="{{ route('public.my-orders') }}" wire:navigate
               class="px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                Track My Order
            </a>
            <a href="{{ route('public.menu') }}" wire:navigate
               class="px-6 py-3 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                Order More
            </a>
        </div>

    @else
        {{-- Pending (webhook not yet received) --}}
        <div class="size-24 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="size-12 text-blue-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Processing Payment…</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">
            We're confirming your payment. This usually takes a few seconds.
        </p>
        @if($record)
            <p class="mt-2 text-sm text-zinc-400">Order: <span class="font-mono">{{ $record->reference_no }}</span></p>
        @endif
        <div class="mt-8">
            <a href="{{ route('public.my-orders') }}" wire:navigate
               class="px-6 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-colors">
                View My Orders
            </a>
        </div>
        {{-- Auto-refresh every 5s while pending --}}
        <div wire:poll.5000ms="$refresh" class="hidden"></div>
    @endif

</div>

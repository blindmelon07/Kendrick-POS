<div>
    <flux:heading size="xl" class="mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}</flux:heading>
    <flux:text class="mb-6 text-zinc-500">Here's your delivery summary for today.</flux:text>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Assigned</p>
            <p class="mt-1 text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $this->stats['assigned'] }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
            <p class="text-xs font-medium text-blue-600 dark:text-blue-400">On the Road</p>
            <p class="mt-1 text-3xl font-bold text-blue-700 dark:text-blue-300">{{ $this->stats['out_for_delivery'] }}</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <p class="text-xs font-medium text-green-600 dark:text-green-400">Delivered Today</p>
            <p class="mt-1 text-3xl font-bold text-green-700 dark:text-green-300">{{ $this->stats['delivered_today'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Delivered</p>
            <p class="mt-1 text-3xl font-bold text-zinc-800 dark:text-zinc-100">{{ $this->stats['total_delivered'] }}</p>
        </div>
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
            <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400">Earnings Today</p>
            <p class="mt-1 text-2xl font-bold text-yellow-700 dark:text-yellow-300">₱{{ number_format($this->stats['earnings_today'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Earnings</p>
            <p class="mt-1 text-2xl font-bold text-zinc-800 dark:text-zinc-100">₱{{ number_format($this->stats['earnings_total'], 2) }}</p>
        </div>
    </div>

    {{-- Quick Action --}}
    <div class="mb-6 flex gap-3">
        <flux:button href="{{ route('rider.deliveries') }}" wire:navigate variant="primary" icon="truck">
            View My Deliveries
        </flux:button>
    </div>

    {{-- Recent Activity --}}
    <flux:heading size="lg" class="mb-3">Recent Activity</flux:heading>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Ref #</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Client</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Address</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Fee</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->recentDeliveries as $order)
                    @php
                        $color = match($order->status) {
                            'delivered'        => 'green',
                            'out_for_delivery' => 'blue',
                            'preparing'        => 'purple',
                            'confirmed'        => 'cyan',
                            'cancelled'        => 'red',
                            default            => 'yellow',
                        };
                    @endphp
                    <tr wire:key="rd-{{ $order->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-medium">{{ $order->reference_no }}</td>
                        <td class="px-4 py-3 font-medium">{{ $order->client_name }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-500 max-w-xs truncate">{{ $order->delivery_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $color }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">₱{{ number_format($order->delivery_fee, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-400">No recent deliveries.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

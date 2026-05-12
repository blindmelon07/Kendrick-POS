<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">My Deliveries</flux:heading>
        <flux:select wire:model.live="statusFilter" class="w-48">
            <flux:select.option value="">All Status</flux:select.option>
            <flux:select.option value="confirmed">Confirmed</flux:select.option>
            <flux:select.option value="preparing">Preparing</flux:select.option>
            <flux:select.option value="out_for_delivery">Out for Delivery</flux:select.option>
            <flux:select.option value="delivered">Delivered</flux:select.option>
            <flux:select.option value="cancelled">Cancelled</flux:select.option>
        </flux:select>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse ($this->deliveries as $order)
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
            <div wire:key="dlv-m-{{ $order->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-mono text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $order->reference_no }}</p>
                        <p class="mt-0.5 font-semibold text-zinc-800 dark:text-zinc-100">{{ $order->client_name }}</p>
                        @if ($order->client_phone)
                            <p class="text-xs text-zinc-500">{{ $order->client_phone }}</p>
                        @endif
                    </div>
                    <flux:badge size="sm" color="{{ $color }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </flux:badge>
                </div>

                @if ($order->delivery_address)
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 flex items-start gap-1">
                        <flux:icon name="map-pin" class="size-3 mt-0.5 shrink-0" />
                        {{ $order->delivery_address }}
                    </p>
                @endif
                @if ($order->delivery_date)
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                        <flux:icon name="calendar" class="size-3 shrink-0" />
                        {{ $order->delivery_date->format('M d, Y') }}
                        @if ($order->delivery_notes)
                            · {{ $order->delivery_notes }}
                        @endif
                    </p>
                @endif

                <div class="mt-3 flex items-center justify-between">
                    <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                        Fee: <span class="font-bold text-zinc-800 dark:text-zinc-100">₱{{ number_format($order->delivery_fee, 2) }}</span>
                    </p>
                    <div class="flex gap-1">
                        <flux:button wire:click="viewItems({{ $order->id }})" variant="ghost" size="sm" icon="list-bullet" title="View Items" />
                        @if ($order->status === 'confirmed' || $order->status === 'preparing')
                            <flux:button wire:click="markOutForDelivery({{ $order->id }})" variant="filled" size="sm" icon="truck">
                                Pick Up
                            </flux:button>
                        @elseif ($order->status === 'out_for_delivery')
                            <flux:button wire:click="markDelivered({{ $order->id }})" variant="primary" size="sm" icon="check-circle">
                                Delivered
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-zinc-400">No deliveries assigned to you.</p>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Ref #</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Client</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Address</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Delivery Date</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Items</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Fee</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->deliveries as $order)
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
                    <tr wire:key="dlv-{{ $order->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-medium">{{ $order->reference_no }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $order->client_name }}</p>
                            @if ($order->client_phone)
                                <p class="text-xs text-zinc-500">{{ $order->client_phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400 max-w-xs">
                            {{ $order->delivery_address ?? '—' }}
                            @if ($order->delivery_notes)
                                <p class="mt-0.5 italic text-zinc-400">{{ $order->delivery_notes }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                            {{ $order->delivery_date?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">{{ $order->items->count() }}</td>
                        <td class="px-4 py-3 text-right font-medium">₱{{ number_format($order->delivery_fee, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $color }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button wire:click="viewItems({{ $order->id }})" variant="ghost" size="sm" icon="list-bullet" title="View Items" />
                                @if ($order->status === 'confirmed' || $order->status === 'preparing')
                                    <flux:button wire:click="markOutForDelivery({{ $order->id }})" variant="filled" size="sm" icon="truck">
                                        Pick Up
                                    </flux:button>
                                @elseif ($order->status === 'out_for_delivery')
                                    <flux:button wire:click="markDelivered({{ $order->id }})" variant="primary" size="sm" icon="check-circle">
                                        Delivered
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">No deliveries assigned to you.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->deliveries->links() }}</div>

    {{-- View Items Modal --}}
    <flux:modal wire:model="showItemsModal" class="max-w-2xl">
        @if ($this->viewingOrder)
            <flux:heading>Order {{ $this->viewingOrder->reference_no }}</flux:heading>
            <flux:text class="mt-1">{{ $this->viewingOrder->client_name }} · {{ $this->viewingOrder->delivery_address }}</flux:text>

            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-2 text-left font-medium">Product</th>
                            <th class="px-4 py-2 text-right font-medium">Qty</th>
                            <th class="px-4 py-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->viewingOrder->items as $item)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-4 py-2 font-medium">{{ $item->product_name }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-4 py-2 text-right font-semibold">₱{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-right text-sm font-bold">
                Total: ₱{{ number_format($this->viewingOrder->total, 2) }}
            </div>
        @endif
        <div class="mt-4 flex justify-end">
            <flux:button wire:click="$set('showItemsModal', false)" variant="ghost">Close</flux:button>
        </div>
    </flux:modal>
</div>

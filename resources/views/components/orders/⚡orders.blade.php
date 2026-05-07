<?php

use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customer Orders')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $paymentFilter = '';

    // Status update
    public bool $showStatusModal = false;
    public ?int $updatingId = null;
    public string $newStatus = '';

    // Cancel
    public bool $showCancelModal = false;
    public ?int $cancellingId = null;
    public string $cancellationReason = '';

    // View items
    public bool $showItemsModal = false;
    public ?int $viewingId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function orders(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::query()
            ->with(['items', 'createdBy'])
            ->when($this->search, fn ($q) => $q
                ->where('reference_no', 'like', "%{$this->search}%")
                ->orWhere('client_name', 'like', "%{$this->search}%")
                ->orWhere('client_phone', 'like', "%{$this->search}%")
            )
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentFilter, fn ($q) => $q->where('payment_status', $this->paymentFilter))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function viewingOrder(): ?Order
    {
        return $this->viewingId
            ? Order::with('items')->find($this->viewingId)
            : null;
    }

    public function viewItems(int $id): void
    {
        $this->viewingId = $id;
        $this->showItemsModal = true;
    }

    public function openStatusModal(int $id, string $current): void
    {
        $this->updatingId = $id;
        $this->newStatus  = $current;
        $this->showStatusModal = true;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'newStatus' => ['required', 'in:pending,confirmed,preparing,out_for_delivery,delivered,cancelled'],
        ]);

        $order = Order::findOrFail($this->updatingId);

        if ($this->newStatus === 'cancelled') {
            $this->showStatusModal = false;
            $this->cancellingId = $this->updatingId;
            $this->showCancelModal = true;
            return;
        }

        $order->update(['status' => $this->newStatus]);
        $this->showStatusModal = false;
        unset($this->orders);
        $this->dispatch('notify', message: 'Order status updated.');
    }

    public function openCancelModal(int $id): void
    {
        $this->cancellingId       = $id;
        $this->cancellationReason = '';
        $this->showCancelModal    = true;
    }

    public function cancelOrder(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'manager']), 403);

        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:5'],
        ]);

        Order::findOrFail($this->cancellingId)->update([
            'status'              => 'cancelled',
            'cancelled_by'        => auth()->id(),
            'cancelled_at'        => now(),
            'cancellation_reason' => $this->cancellationReason,
        ]);

        $this->showCancelModal    = false;
        $this->cancellingId       = null;
        $this->cancellationReason = '';
        unset($this->orders);
        $this->dispatch('notify', message: 'Order cancelled.');
    }

    private function statusColor(string $status): string
    {
        return match($status) {
            'delivered'        => 'green',
            'out_for_delivery' => 'blue',
            'preparing'        => 'purple',
            'confirmed'        => 'cyan',
            'cancelled'        => 'red',
            default            => 'yellow',
        };
    }

    private function paymentColor(string $status): string
    {
        return match($status) {
            'paid'    => 'green',
            'partial' => 'yellow',
            default   => 'red',
        };
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:flex-row">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search ref, client, phone..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:select wire:model.live="statusFilter" class="w-44">
                <flux:select.option value="">All Status</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="confirmed">Confirmed</flux:select.option>
                <flux:select.option value="preparing">Preparing</flux:select.option>
                <flux:select.option value="out_for_delivery">Out for Delivery</flux:select.option>
                <flux:select.option value="delivered">Delivered</flux:select.option>
                <flux:select.option value="cancelled">Cancelled</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="paymentFilter" class="w-36">
                <flux:select.option value="">All Payment</flux:select.option>
                <flux:select.option value="unpaid">Unpaid</flux:select.option>
                <flux:select.option value="partial">Partial</flux:select.option>
                <flux:select.option value="paid">Paid</flux:select.option>
            </flux:select>
        </div>
        <flux:button href="{{ route('orders.create') }}" wire:navigate variant="primary" icon="plus">New Order</flux:button>
    </div>

    {{-- Mobile cards --}}
    <div class="sm:hidden space-y-3">
        @forelse ($this->orders as $order)
            <div wire:key="ord-m-{{ $order->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-mono text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $order->reference_no }}</p>
                        <p class="mt-0.5 font-semibold text-zinc-800 dark:text-zinc-100">{{ $order->client_name }}</p>
                        @if ($order->client_phone)
                            <p class="text-xs text-zinc-500">{{ $order->client_phone }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <flux:badge size="sm" color="{{ $this->statusColor($order->status) }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </flux:badge>
                        <flux:badge size="sm" color="{{ $this->paymentColor($order->payment_status) }}">
                            {{ ucfirst($order->payment_status) }}
                        </flux:badge>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <div>
                        <p class="font-medium text-zinc-400 dark:text-zinc-500">Delivery</p>
                        <p class="text-zinc-700 dark:text-zinc-200">{{ $order->delivery_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-400 dark:text-zinc-500">Total</p>
                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">₱{{ number_format($order->total, 2) }}</p>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-400 dark:text-zinc-500">Balance</p>
                        <p class="font-semibold {{ $order->balance > 0 ? 'text-red-500' : 'text-zinc-400' }}">₱{{ number_format($order->balance, 2) }}</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-zinc-400">{{ $order->items->count() }} item(s)</span>
                    <div class="flex gap-1">
                        <flux:button wire:click="viewItems({{ $order->id }})" variant="ghost" size="sm" icon="list-bullet" title="View Items" />
                        @if ($order->status !== 'cancelled' && $order->status !== 'delivered')
                            <flux:button wire:click="openStatusModal({{ $order->id }}, '{{ $order->status }}')" variant="ghost" size="sm" icon="arrow-path" title="Update Status" />
                            <flux:button wire:click="openCancelModal({{ $order->id }})" variant="ghost" size="sm" icon="x-circle" class="text-red-500" title="Cancel" />
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-zinc-400">No orders found.</p>
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Ref #</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Client</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Delivery Date</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Items</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Balance</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Payment</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->orders as $order)
                    <tr wire:key="ord-{{ $order->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-medium">{{ $order->reference_no }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $order->client_name }}</p>
                            @if ($order->client_phone)
                                <p class="text-xs text-zinc-500">{{ $order->client_phone }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                            {{ $order->delivery_date?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">{{ $order->items->count() }}</td>
                        <td class="px-4 py-3 text-right font-medium">₱{{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $order->balance > 0 ? 'text-red-500 font-semibold' : 'text-zinc-400' }}">
                            ₱{{ number_format($order->balance, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $this->statusColor($order->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $this->paymentColor($order->payment_status) }}">
                                {{ ucfirst($order->payment_status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button wire:click="viewItems({{ $order->id }})" variant="ghost" size="sm" icon="list-bullet" title="View Items" />
                                @if ($order->status !== 'cancelled' && $order->status !== 'delivered')
                                    <flux:button wire:click="openStatusModal({{ $order->id }}, '{{ $order->status }}')" variant="ghost" size="sm" icon="arrow-path" title="Update Status" />
                                    <flux:button wire:click="openCancelModal({{ $order->id }})" variant="ghost" size="sm" icon="x-circle" class="text-red-500" title="Cancel Order" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-zinc-400">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->orders->links() }}</div>

    {{-- View Items Modal --}}
    <flux:modal wire:model="showItemsModal" class="max-w-3xl">
        @if ($this->viewingOrder)
            <flux:heading>Order {{ $this->viewingOrder->reference_no }}</flux:heading>
            <div class="mt-1 text-sm text-zinc-500">
                {{ $this->viewingOrder->client_name }}
                @if ($this->viewingOrder->delivery_address)
                    · {{ $this->viewingOrder->delivery_address }}
                @endif
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-2 text-left font-medium">Product</th>
                            <th class="px-4 py-2 text-right font-medium">Qty</th>
                            <th class="px-4 py-2 text-right font-medium">Unit Price</th>
                            <th class="px-4 py-2 text-right font-medium">Discount</th>
                            <th class="px-4 py-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->viewingOrder->items as $item)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-4 py-2">
                                    <p class="font-medium">{{ $item->product_name }}</p>
                                    @if ($item->sku)
                                        <p class="text-xs text-zinc-400">{{ $item->sku }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right">{{ $item->quantity ?? 0 }} {{ $item->unit }}</td>
                                <td class="px-4 py-2 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->discount_amount > 0 ? '₱' . number_format($item->discount_amount, 2) : '—' }}</td>
                                <td class="px-4 py-2 text-right font-semibold">₱{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-x-8 text-sm">
                <div class="space-y-1 text-zinc-500">
                    @if ($this->viewingOrder->delivery_date)
                        <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Delivery:</span> {{ $this->viewingOrder->delivery_date->format('M d, Y') }}</p>
                    @endif
                    @if ($this->viewingOrder->driver_name)
                        <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Driver:</span> {{ $this->viewingOrder->driver_name }} {{ $this->viewingOrder->driver_phone ? '(' . $this->viewingOrder->driver_phone . ')' : '' }}</p>
                    @endif
                    @if ($this->viewingOrder->vehicle_type)
                        <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Vehicle:</span> {{ $this->viewingOrder->vehicle_type }}</p>
                    @endif
                </div>
                <div class="space-y-1 text-right">
                    <p class="text-zinc-500">Subtotal: <span class="text-zinc-700 dark:text-zinc-200">₱{{ number_format($this->viewingOrder->subtotal, 2) }}</span></p>
                    @if ($this->viewingOrder->delivery_fee > 0)
                        <p class="text-zinc-500">Delivery Fee: <span class="text-zinc-700 dark:text-zinc-200">₱{{ number_format($this->viewingOrder->delivery_fee, 2) }}</span></p>
                    @endif
                    @if ($this->viewingOrder->discount_amount > 0)
                        <p class="text-zinc-500">Discount: <span class="text-red-500">-₱{{ number_format($this->viewingOrder->discount_amount, 2) }}</span></p>
                    @endif
                    @if ($this->viewingOrder->tax_amount > 0)
                        <p class="text-zinc-500">Tax: <span class="text-zinc-700 dark:text-zinc-200">₱{{ number_format($this->viewingOrder->tax_amount, 2) }}</span></p>
                    @endif
                    <p class="border-t border-zinc-200 pt-1 font-bold dark:border-zinc-700">Total: ₱{{ number_format($this->viewingOrder->total, 2) }}</p>
                    <p class="text-zinc-500">Paid: <span class="text-green-600">₱{{ number_format($this->viewingOrder->amount_paid, 2) }}</span></p>
                    <p class="font-semibold {{ $this->viewingOrder->balance > 0 ? 'text-red-500' : 'text-green-600' }}">
                        Balance: ₱{{ number_format($this->viewingOrder->balance, 2) }}
                    </p>
                </div>
            </div>
        @endif
        <div class="mt-4 flex justify-end">
            <flux:button wire:click="$set('showItemsModal', false)" variant="ghost">Close</flux:button>
        </div>
    </flux:modal>

    {{-- Update Status Modal --}}
    <flux:modal wire:model="showStatusModal" class="max-w-sm">
        <flux:heading>Update Order Status</flux:heading>
        <div class="mt-4">
            <flux:field>
                <flux:label>New Status</flux:label>
                <flux:select wire:model="newStatus">
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="confirmed">Confirmed</flux:select.option>
                    <flux:select.option value="preparing">Preparing</flux:select.option>
                    <flux:select.option value="out_for_delivery">Out for Delivery</flux:select.option>
                    <flux:select.option value="delivered">Delivered</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>
                @error('newStatus') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showStatusModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateStatus" variant="primary">Update</flux:button>
        </div>
    </flux:modal>

    {{-- Cancel Modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-sm">
        <flux:heading>Cancel Order</flux:heading>
        <flux:text class="mt-1">Please provide a reason for cancellation.</flux:text>
        <div class="mt-4">
            <flux:field>
                <flux:label>Reason</flux:label>
                <flux:textarea wire:model="cancellationReason" rows="3" placeholder="e.g. Client requested cancellation" />
                @error('cancellationReason') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showCancelModal', false)" variant="ghost">Back</flux:button>
            <flux:button wire:click="cancelOrder" variant="danger">Confirm Cancel</flux:button>
        </div>
    </flux:modal>
</div>

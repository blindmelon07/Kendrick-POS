<?php

use App\Models\DeliveryOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Delivery Orders')] class extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    public bool $showStatusModal = false;
    public bool $showItemsModal = false;
    public ?int $updatingId = null;
    public ?int $viewingId = null;
    public string $newStatus = '';

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function deliveries(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return DeliveryOrder::query()
            ->with(['supplier', 'purchaseOrder', 'creator', 'items'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where('delivery_number', 'like', "%{$this->search}%")->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(15);
    }

    public function openStatusModal(int $id, string $currentStatus): void
    {
        $this->updatingId = $id;
        $this->newStatus  = $currentStatus;
        $this->showStatusModal = true;
    }

    public function viewItems(int $id): void
    {
        $this->viewingId = $id;
        $this->showItemsModal = true;
    }

    #[Computed]
    public function viewingDelivery(): ?DeliveryOrder
    {
        return $this->viewingId
            ? DeliveryOrder::with('items')->find($this->viewingId)
            : null;
    }

    public function updateStatus(): void
    {
        $this->validate(['newStatus' => ['required', 'in:pending,in_transit,received,cancelled']]);

        $delivery = DeliveryOrder::with('items')->findOrFail($this->updatingId);

        if ($this->newStatus === 'received' && $delivery->status !== 'received') {
            foreach ($delivery->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    $before                  = (float) $product->stock_quantity;
                    $received                = (float) $item->received_quantity ?: (float) $item->expected_quantity;
                    $product->stock_quantity += $received;
                    $product->save();

                    \App\Models\StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'in',
                        'quantity'        => $received,
                        'before_quantity' => $before,
                        'after_quantity'  => (float) $product->stock_quantity,
                        'reason'          => 'Delivery - ' . $delivery->delivery_number,
                        'reference'       => $delivery->delivery_number,
                        'user_id'         => \Illuminate\Support\Facades\Auth::id(),
                    ]);

                    $item->update(['received_quantity' => $received]);
                }
            }

            $delivery->update(['received_at' => now()]);
        }

        $delivery->update(['status' => $this->newStatus]);
        $this->showStatusModal = false;
        unset($this->deliveries);
    }
};
?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="flex flex-1 gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search delivery no or supplier..." icon="magnifying-glass" class="flex-1" />
            <flux:select wire:model.live="statusFilter" class="w-40">
                <flux:select.option value="">All Status</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="in_transit">In Transit</flux:select.option>
                <flux:select.option value="received">Received</flux:select.option>
                <flux:select.option value="cancelled">Cancelled</flux:select.option>
            </flux:select>
        </div>
        <flux:button href="{{ route('deliveries.create') }}" variant="primary" icon="plus">New Delivery</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Delivery #</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Supplier</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">PO #</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Items</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Expected</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->deliveries as $delivery)
                    <tr wire:key="dlv-{{ $delivery->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-medium">{{ $delivery->delivery_number }}</td>
                        <td class="px-4 py-3">{{ $delivery->supplier->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500">{{ $delivery->purchaseOrder?->po_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">{{ $delivery->items->count() }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ match($delivery->status) { 'received' => 'green', 'in_transit' => 'blue', 'cancelled' => 'red', default => 'yellow' } }}">
                                {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-500">{{ $delivery->expected_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button wire:click="viewItems({{ $delivery->id }})" variant="ghost" size="sm" icon="list-bullet" />
                                <flux:button wire:click="openStatusModal({{ $delivery->id }}, '{{ $delivery->status }}')" variant="ghost" size="sm" icon="arrow-path">Update</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-400">No deliveries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->deliveries->links() }}</div>

    <flux:modal wire:model="showItemsModal" class="max-w-2xl">
        @if ($this->viewingDelivery)
            <flux:heading>Items — {{ $this->viewingDelivery->delivery_number }}</flux:heading>
            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-2 text-left font-medium">Product</th>
                            <th class="px-4 py-2 text-right font-medium">Expected Qty</th>
                            <th class="px-4 py-2 text-right font-medium">Received Qty</th>
                            <th class="px-4 py-2 text-right font-medium">Unit Cost</th>
                            <th class="px-4 py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->viewingDelivery->items as $item)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-4 py-2 font-medium">{{ $item->product_name }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->expected_quantity + 0 }} {{ $item->unit_label }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->received_quantity + 0 }} {{ $item->unit_label }}</td>
                                <td class="px-4 py-2 text-right">₱{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="px-4 py-2 text-right font-semibold">₱{{ number_format($item->expected_quantity * $item->unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="mt-4 flex justify-end">
            <flux:button wire:click="\$set('showItemsModal', false)" variant="ghost">Close</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showStatusModal" class="max-w-sm">
        <flux:heading>Update Delivery Status</flux:heading>
        <div class="mt-4">
            <flux:field>
                <flux:label>New Status</flux:label>
                <flux:select wire:model="newStatus">
                    <flux:select.option value="pending">Pending</flux:select.option>
                    <flux:select.option value="in_transit">In Transit</flux:select.option>
                    <flux:select.option value="received">Received (updates stock)</flux:select.option>
                    <flux:select.option value="cancelled">Cancelled</flux:select.option>
                </flux:select>
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showStatusModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="updateStatus" variant="primary">Update</flux:button>
        </div>
    </flux:modal>
</div>
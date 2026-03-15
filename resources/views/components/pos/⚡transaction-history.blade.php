<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transaction History')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showVoidModal = false;
    public ?int $voidTransactionId = null;
    public string $voidReason = '';

    public bool $showReceiptModal = false;
    public ?int $previewTransactionId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transactions(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Transaction::query()
            ->with(['cashier', 'items'])
            ->when($this->search, fn ($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);
    }

    public function openVoidModal(int $transactionId): void
    {
        $this->voidTransactionId = $transactionId;
        $this->voidReason        = '';
        $this->showVoidModal     = true;
    }

    public function voidTransaction(): void
    {
        $this->validate(['voidReason' => ['required', 'string', 'min:5']]);

        $transaction = Transaction::where('status', 'completed')
            ->findOrFail($this->voidTransactionId);

        $transaction->update([
            'status'      => 'voided',
            'voided_by'   => Auth::id(),
            'voided_at'   => now(),
            'void_reason' => $this->voidReason,
        ]);

        foreach ($transaction->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = \App\Models\Product::find($item->product_id);
            if ($product) {
                $before                  = (float) $product->stock_quantity;
                $product->stock_quantity += $item->quantity;
                $product->save();

                \App\Models\StockMovement::create([
                    'product_id'      => $product->id,
                    'type'            => 'in',
                    'quantity'        => $item->quantity,
                    'before_quantity' => $before,
                    'after_quantity'  => (float) $product->stock_quantity,
                    'reason'          => 'Void - ' . $transaction->reference_no,
                    'reference'       => $transaction->reference_no,
                    'user_id'         => Auth::id(),
                ]);
            }
        }

        $this->showVoidModal     = false;
        $this->voidTransactionId = null;
        unset($this->transactions);
        $this->dispatch('notify', message: 'Transaction voided successfully.');
    }

    public function previewReceipt(int $transactionId): void
    {
        $this->previewTransactionId = $transactionId;
        $this->showReceiptModal     = true;
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by reference no..."
            icon="magnifying-glass"
            class="flex-1"
        />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="">All Status</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
            <flux:select.option value="voided">Voided</flux:select.option>
            <flux:select.option value="refunded">Refunded</flux:select.option>
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" class="w-36" />
        <flux:input type="date" wire:model.live="dateTo" class="w-36" />
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Reference</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Date</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Cashier</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Method</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->transactions as $transaction)
                    <tr wire:key="tx-{{ $transaction->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-zinc-800 dark:text-zinc-100">
                            {{ $transaction->reference_no }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $transaction->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-4 py-3">{{ $transaction->cashier?->name }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" variant="outline">
                                {{ ucfirst(str_replace('_', ' ', $transaction->payment_method)) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">₱{{ number_format($transaction->total, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge
                                size="sm"
                                color="{{ $transaction->status === 'completed' ? 'green' : ($transaction->status === 'voided' ? 'red' : 'yellow') }}"
                            >{{ ucfirst($transaction->status) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-1">
                                <flux:button
                                    wire:click="previewReceipt({{ $transaction->id }})"
                                    variant="ghost" size="sm" icon="document-text"
                                    title="View Receipt"
                                />
                                @if ($transaction->status === 'completed')
                                    <flux:button
                                        wire:click="openVoidModal({{ $transaction->id }})"
                                        variant="ghost" size="sm" icon="x-circle"
                                        class="text-red-500 hover:text-red-700"
                                        title="Void"
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-400">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->transactions->links() }}
    </div>

    {{-- Void Modal --}}
    <flux:modal wire:model="showVoidModal" class="max-w-md">
        <flux:heading>Void Transaction</flux:heading>
        <flux:text class="mt-1">This will restore stock for all items in this transaction.</flux:text>
        <div class="mt-4">
            <flux:field>
                <flux:label>Reason for Voiding</flux:label>
                <flux:textarea wire:model="voidReason" rows="3" placeholder="Enter reason..." />
                @error('voidReason') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showVoidModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="voidTransaction" variant="danger">Void Transaction</flux:button>
        </div>
    </flux:modal>

    {{-- Receipt Preview Modal --}}
    @if ($showReceiptModal && $previewTransactionId)
        @php $tx = \App\Models\Transaction::with(['items', 'cashier'])->find($previewTransactionId); @endphp
        @if ($tx)
            <flux:modal wire:model="showReceiptModal" class="max-w-sm">
                <div class="text-center">
                    <flux:heading size="lg">Receipt</flux:heading>
                    <p class="font-mono text-sm text-zinc-500">{{ $tx->reference_no }}</p>
                    <p class="text-xs text-zinc-400">{{ $tx->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <hr class="my-3 border-dashed" />
                <div class="space-y-1 text-sm">
                    @foreach ($tx->items as $item)
                        <div class="flex justify-between">
                            <span>{{ $item->product_name }}</span>
                            <span>₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</p>
                    @endforeach
                </div>
                <hr class="my-3 border-dashed" />
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between text-base font-bold">
                        <span>TOTAL</span><span>₱{{ number_format($tx->total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-500">
                        <span>{{ ucfirst(str_replace('_', ' ', $tx->payment_method)) }}</span>
                        <span>₱{{ number_format($tx->amount_tendered, 2) }}</span>
                    </div>
                    @if ($tx->change_amount > 0)
                        <div class="flex justify-between font-semibold text-green-600">
                            <span>Change</span><span>₱{{ number_format($tx->change_amount, 2) }}</span>
                        </div>
                    @endif
                    @if ($tx->status === 'voided')
                        <div class="mt-2 rounded bg-red-100 px-3 py-2 text-center text-xs text-red-700">
                            VOIDED — {{ $tx->void_reason }}
                        </div>
                    @endif
                </div>
                <div class="mt-4 flex gap-2">
                    <flux:button onclick="window.print()" variant="ghost" class="flex-1" icon="printer">Print</flux:button>
                    <flux:button wire:click="$set('showReceiptModal', false)" variant="primary" class="flex-1">Close</flux:button>
                </div>
            </flux:modal>
        @endif
    @endif
</div>

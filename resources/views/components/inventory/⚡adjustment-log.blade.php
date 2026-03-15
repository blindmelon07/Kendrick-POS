<?php

use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Adjustment Log')] class extends Component
{
    use WithPagination;

    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }

    #[Computed]
    public function movements(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['product', 'user'])
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(25);
    }
};
?>

<div>
    <div class="mb-4 flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search product..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="typeFilter" class="w-36">
            <flux:select.option value="">All Types</flux:select.option>
            <flux:select.option value="in">Stock In</flux:select.option>
            <flux:select.option value="out">Stock Out</flux:select.option>
            <flux:select.option value="adjustment">Adjustment</flux:select.option>
        </flux:select>
        <flux:input type="date" wire:model.live="dateFrom" class="w-36" />
        <flux:input type="date" wire:model.live="dateTo" class="w-36" />
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Date</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Product</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Type</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Before</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Qty</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">After</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Reason</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">User</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->movements as $movement)
                    <tr wire:key="mv-{{ $movement->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-2 text-xs text-zinc-500">{{ $movement->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-4 py-2">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $movement->product?->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $movement->product?->sku }}</p>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <flux:badge size="sm" color="{{ match($movement->type) { 'in' => 'green', 'out' => 'red', default => 'blue' } }}">
                                {{ ucfirst($movement->type) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ number_format($movement->before_quantity, 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs font-semibold">{{ number_format($movement->quantity, 2) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ number_format($movement->after_quantity, 2) }}</td>
                        <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $movement->reason }}</td>
                        <td class="px-4 py-2 text-xs text-zinc-500">{{ $movement->user?->name }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-zinc-500">{{ $movement->reference }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-zinc-400">No movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->movements->links() }}</div>
</div>
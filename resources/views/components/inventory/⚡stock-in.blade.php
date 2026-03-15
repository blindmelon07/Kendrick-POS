<?php

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Stock Management')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';

    public bool $showModal = false;
    public ?int $productId = null;
    public string $movementType = 'in';
    public float $quantity = 1;
    public string $reason = '';
    public string $notes = '';

    /** Current product snapshot for modal display */
    public ?float $currentStock = null;
    public string $currentProductName = '';

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function products(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->where('is_active', true)
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Category::where('is_active', true)->orderBy('name')->get();
    }

    public function openModal(int $productId, string $type): void
    {
        $product = Product::findOrFail($productId);
        $this->productId          = $productId;
        $this->movementType       = $type;
        $this->currentStock       = (float) $product->stock_quantity;
        $this->currentProductName = $product->name;
        $this->quantity           = 1;
        $this->reason             = '';
        $this->notes              = '';
        $this->showModal          = true;
    }

    public function saveMovement(): void
    {
        $this->validate([
            'productId'    => ['required', 'exists:products,id'],
            'movementType' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'reason'       => ['required', 'string', 'max:255'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::lockForUpdate()->findOrFail($this->productId);
        $before  = (float) $product->stock_quantity;

        if ($this->movementType === 'out' && $this->quantity > $before) {
            $this->addError('quantity', 'Cannot remove more than current stock (' . $before . ').');
            return;
        }

        $after = match ($this->movementType) {
            'in'         => $before + $this->quantity,
            'out'        => $before - $this->quantity,
            'adjustment' => $this->quantity,
            default      => $before,
        };

        $appliedQty = abs($after - $before);

        $product->update(['stock_quantity' => $after]);

        StockMovement::create([
            'product_id'      => $product->id,
            'type'            => $this->movementType,
            'quantity'        => $this->movementType === 'adjustment' ? $appliedQty : $this->quantity,
            'before_quantity' => $before,
            'after_quantity'  => $after,
            'reason'          => $this->reason,
            'notes'           => $this->notes ?: null,
            'user_id'         => Auth::id(),
        ]);

        $this->showModal = false;
        unset($this->products);
    }
};
?>

<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search product or SKU..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="categoryFilter" class="w-44">
            <flux:select.option value="">All Categories</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Category</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Stock</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Reorder</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->products as $product)
                    <tr wire:key="prod-{{ $product->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $product->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $product->sku }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-mono font-semibold {{ $product->isLowStock() ? 'text-red-600' : 'text-zinc-800 dark:text-zinc-100' }}">
                                {{ number_format($product->stock_quantity, 2) }}
                            </span>
                            <span class="text-xs text-zinc-400"> {{ $product->unit?->abbreviation }}</span>
                            @if ($product->isLowStock())
                                <flux:badge size="sm" color="red" class="ml-1">Low</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-zinc-500">{{ number_format($product->reorder_level, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-1">
                                <flux:button wire:click="openModal({{ $product->id }}, 'in')" variant="ghost" size="sm" class="text-green-600">In</flux:button>
                                <flux:button wire:click="openModal({{ $product->id }}, 'out')" variant="ghost" size="sm" class="text-red-600">Out</flux:button>
                                <flux:button wire:click="openModal({{ $product->id }}, 'adjustment')" variant="ghost" size="sm" class="text-blue-600">Adj</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-400">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->products->links() }}</div>

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>
            @if ($movementType === 'in') Stock In
            @elseif ($movementType === 'out') Stock Out
            @else Adjust Stock
            @endif
        </flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ $currentProductName }}</p>
        <p class="mt-1 text-xs text-zinc-400">Current stock: <strong>{{ number_format($currentStock ?? 0, 2) }}</strong></p>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>
                    @if ($movementType === 'adjustment') New Stock Level @else Quantity @endif
                </flux:label>
                <flux:input type="number" wire:model="quantity" min="0.001" step="0.001" autofocus />
                @error('quantity') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Reason <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="reason" placeholder="e.g. Purchase, Damage, Count correction..." />
                @error('reason') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="2" />
            </flux:field>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="saveMovement" variant="primary">Save</flux:button>
        </div>
    </flux:modal>
</div>
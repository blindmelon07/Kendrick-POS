<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $stockFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $sku = '';
    public string $name = '';
    public string $description = '';
    public ?int $categoryId = null;
    public ?int $unitId = null;
    public float $costPrice = 0;
    public float $sellingPrice = 0;
    public float $stockQuantity = 0;
    public float $reorderLevel = 0;
    public bool $isActive = true;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->whereRaw('stock_quantity <= reorder_level'))
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('stock_quantity', '<=', 0))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function units(): \Illuminate\Database\Eloquent\Collection
    {
        return Unit::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId  = null;
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $product             = Product::findOrFail($id);
        $this->editingId     = $id;
        $this->sku           = $product->sku;
        $this->name          = $product->name;
        $this->description   = $product->description ?? '';
        $this->categoryId    = $product->category_id;
        $this->unitId        = $product->unit_id;
        $this->costPrice     = (float) $product->cost_price;
        $this->sellingPrice  = (float) $product->selling_price;
        $this->stockQuantity = (float) $product->stock_quantity;
        $this->reorderLevel  = (float) $product->reorder_level;
        $this->isActive      = $product->is_active;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate([
            'sku'          => ['required', 'string', 'max:100', $this->editingId
                ? \Illuminate\Validation\Rule::unique('products', 'sku')->ignore($this->editingId)
                : \Illuminate\Validation\Rule::unique('products', 'sku')],
            'name'         => ['required', 'string', 'max:255'],
            'costPrice'    => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
        ]);

        $data = [
            'sku'            => $this->sku,
            'name'           => $this->name,
            'description'    => $this->description ?: null,
            'category_id'    => $this->categoryId,
            'unit_id'        => $this->unitId,
            'cost_price'     => $this->costPrice,
            'selling_price'  => $this->sellingPrice,
            'reorder_level'  => $this->reorderLevel,
            'is_active'      => $this->isActive,
        ];

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update($data);
        } else {
            $data['stock_quantity'] = $this->stockQuantity;
            Product::create($data);
        }

        $this->showModal = false;
        unset($this->products);
        $this->dispatch('notify', message: $this->editingId ? 'Product updated.' : 'Product created.');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteModal = true;
    }

    public function deleteProduct(): void
    {
        Product::findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId      = null;
        unset($this->products);
    }

    private function resetForm(): void
    {
        $this->sku           = '';
        $this->name          = '';
        $this->description   = '';
        $this->categoryId    = null;
        $this->unitId        = null;
        $this->costPrice     = 0;
        $this->sellingPrice  = 0;
        $this->stockQuantity = 0;
        $this->reorderLevel  = 0;
        $this->isActive      = true;
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:flex-row">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name or SKU..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:select wire:model.live="categoryFilter" class="w-44">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach ($this->categories as $cat)
                    <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="stockFilter" class="w-36">
                <flux:select.option value="">All Stock</flux:select.option>
                <flux:select.option value="low">Low Stock</flux:select.option>
                <flux:select.option value="out">Out of Stock</flux:select.option>
            </flux:select>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Add Product</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">SKU</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">Category</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Cost</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Price</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-300">Stock</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->products as $product)
                    <tr wire:key="prod-{{ $product->id }}" class="border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $product->name }}</p>
                            @if ($product->unit)
                                <p class="text-xs text-zinc-500">per {{ $product->unit->abbreviation }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">₱{{ number_format($product->cost_price, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium">₱{{ number_format($product->selling_price, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="{{ $product->isLowStock() ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-200' }}">
                                {{ number_format($product->stock_quantity, 2) }}
                            </span>
                            @if ($product->isLowStock())
                                <flux:badge size="xs" color="red" class="ml-1">Low</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <flux:badge size="sm" color="{{ $product->is_active ? 'green' : 'zinc' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-1">
                                <flux:button wire:click="edit({{ $product->id }})" variant="ghost" size="sm" icon="pencil" title="Edit" />
                                <flux:button wire:click="confirmDelete({{ $product->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500" title="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-400">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $this->products->links() }}</div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <flux:heading>{{ $editingId ? 'Edit Product' : 'Add Product' }}</flux:heading>
        <div class="mt-4 grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>SKU <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="sku" />
                @error('sku') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" />
                @error('name') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field class="col-span-2">
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="2" />
            </flux:field>
            <flux:field>
                <flux:label>Category</flux:label>
                <flux:select wire:model="categoryId">
                    <flux:select.option value="">— None —</flux:select.option>
                    @foreach ($this->categories as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Unit</flux:label>
                <flux:select wire:model="unitId">
                    <flux:select.option value="">— None —</flux:select.option>
                    @foreach ($this->units as $unit)
                        <flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Cost Price</flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="costPrice" prefix="₱" />
                @error('costPrice') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Selling Price</flux:label>
                <flux:input type="number" min="0" step="0.01" wire:model="sellingPrice" prefix="₱" />
                @error('sellingPrice') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            @if (! $editingId)
                <flux:field>
                    <flux:label>Initial Stock</flux:label>
                    <flux:input type="number" min="0" step="0.001" wire:model="stockQuantity" />
                </flux:field>
            @endif
            <flux:field>
                <flux:label>Reorder Level</flux:label>
                <flux:input type="number" min="0" step="0.001" wire:model="reorderLevel" />
                @error('reorderLevel') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:switch wire:model="isActive" label="Active" />
            </flux:field>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="save" variant="primary">{{ $editingId ? 'Update' : 'Create' }}</flux:button>
        </div>
    </flux:modal>

    {{-- Delete Confirm --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <flux:heading>Delete Product?</flux:heading>
        <flux:text class="mt-1">This action cannot be undone.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="deleteProduct" variant="danger">Delete</flux:button>
        </div>
    </flux:modal>
</div>

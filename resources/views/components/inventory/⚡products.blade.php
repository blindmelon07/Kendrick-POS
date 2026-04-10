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
    public string $barcode = '';
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
        $this->barcode       = $product->barcode ?? '';
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
            'barcode'      => ['nullable', 'string', 'max:100', $this->editingId
                ? \Illuminate\Validation\Rule::unique('products', 'barcode')->ignore($this->editingId)
                : \Illuminate\Validation\Rule::unique('products', 'barcode')],
            'name'         => ['required', 'string', 'max:255'],
            'costPrice'    => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
        ]);

        $data = [
            'sku'            => $this->sku,
            'barcode'        => $this->barcode ?: null,
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
        $this->barcode       = '';
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

<div
    @inv-open-camera.window="window.invStartCamera && window.invStartCamera()"
    @inv-barcode-scanned.document="$wire.set('barcode', $event.detail)"
>
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
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300">SKU / Barcode</th>
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
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs text-zinc-700 dark:text-zinc-200">{{ $product->sku }}</p>
                            @if ($product->barcode)
                                <p class="mt-0.5 font-mono text-xs text-blue-600 dark:text-blue-400">{{ $product->barcode }}</p>
                            @endif
                        </td>
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
                <flux:input wire:model="sku" placeholder="e.g. CON-001" />
                @error('sku') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                <flux:input wire:model="name" />
                @error('name') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            {{-- Barcode field with live preview --}}
            <flux:field class="col-span-2"
                x-data="{
                    renderPreview() {
                        const val = $wire.barcode || $wire.sku;
                        if (!val || !window.JsBarcode) return;
                        try {
                            JsBarcode('#barcode-preview-svg', val, {
                                format: 'CODE128', width: 1.8, height: 50,
                                displayValue: true, fontSize: 11, margin: 6,
                            });
                        } catch(e) {}
                    }
                }"
                x-init="$watch(() => $wire.barcode + $wire.sku, () => $nextTick(() => renderPreview()))"
            >
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <flux:label>
                            Barcode
                            <span class="ml-1 text-xs font-normal text-zinc-400">(leave blank to use SKU)</span>
                        </flux:label>
                        <flux:input
                            wire:model.live="barcode"
                            placeholder="Scan or type actual barcode number"
                            icon="qr-code"
                        />
                        @error('barcode') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>
                    <flux:button
                        type="button"
                        wire:click="$set('barcode', sku)"
                        variant="ghost"
                        size="sm"
                        class="mb-0.5 shrink-0"
                        title="Copy SKU as barcode"
                    >Use SKU</flux:button>
                    <flux:button
                        type="button"
                        @click="$dispatch('inv-open-camera')"
                        variant="ghost"
                        size="sm"
                        icon="camera"
                        class="mb-0.5 shrink-0"
                        title="Scan barcode with camera"
                    />
                </div>

                {{-- Live barcode preview --}}
                @if ($barcode || $sku)
                    <div class="mt-3 flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <svg id="barcode-preview-svg" class="max-w-full"></svg>
                        <p class="text-xs text-zinc-400">
                            Rendering: <span class="font-mono font-medium text-zinc-600 dark:text-zinc-300">{{ $barcode ?: $sku }}</span>
                        </p>
                    </div>
                @endif
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

    {{-- Inventory Barcode Camera Scanner (native dialog — renders in top layer above all modals) --}}
    <dialog id="inv-camera-dialog" style="padding:0;border:none;border-radius:1rem;background:transparent;max-width:95vw;width:380px;">
        <div class="rounded-2xl bg-white p-5 shadow-2xl dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Scan Barcode</h3>
                <button
                    type="button"
                    onclick="window.invStopCamera()"
                    class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div id="inv-camera-feed" style="min-height:280px;background:#000;border-radius:8px;overflow:hidden;"></div>
            <p class="mt-3 text-center text-xs text-zinc-400">Point camera at a barcode to scan</p>
        </div>
    </dialog>

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

@script
<script>
    let invScanner = null;

    window.invStartCamera = function () {
        if (!window.Html5Qrcode) return;
        const dialog = document.getElementById('inv-camera-dialog');
        const feedEl = document.getElementById('inv-camera-feed');
        if (!dialog || !feedEl) return;

        feedEl.innerHTML = '';
        dialog.showModal();

        // Style the backdrop via CSS (dialog::backdrop)
        if (!document.getElementById('inv-dialog-style')) {
            const s = document.createElement('style');
            s.id = 'inv-dialog-style';
            s.textContent = '#inv-camera-dialog::backdrop { background: rgba(0,0,0,0.65); }';
            document.head.appendChild(s);
        }

        Html5Qrcode.getCameras().then(function (cameras) {
            if (!cameras || cameras.length === 0) {
                feedEl.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">No camera found.</p>';
                return;
            }
            const camId = cameras[cameras.length - 1].id;
            invScanner = new Html5Qrcode('inv-camera-feed', {
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.ITF,
                    Html5QrcodeSupportedFormats.QR_CODE,
                ],
                verbose: false,
            });
            invScanner.start(
                camId,
                { fps: 10, qrbox: { width: 260, height: 120 } },
                function (decodedText) {
                    document.dispatchEvent(new CustomEvent('inv-barcode-scanned', { detail: decodedText }));
                    window.invStopCamera();
                },
                function () {}
            ).catch(function (err) {
                feedEl.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">Camera error: ' + err + '</p>';
            });
        }).catch(function () {
            feedEl.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">Cannot access camera.</p>';
        });
    };

    window.invStopCamera = function () {
        const dialog = document.getElementById('inv-camera-dialog');
        if (invScanner) {
            invScanner.stop().catch(() => {});
            invScanner = null;
        }
        if (dialog && dialog.open) dialog.close();
    };
</script>
@endscript

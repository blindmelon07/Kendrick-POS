<?php

use App\Models\DeliveryItem;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Delivery')] class extends Component
{
    public ?int $supplierId = null;
    public ?int $purchaseOrderId = null;
    public string $notes = '';
    public string $expectedAt = '';

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public ?int $addProductId = null;
    public float $addExpectedQty = 1;
    public ?int $addUnitId = null;
    public float $addUnitCost = 0;

    #[Computed]
    public function units(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Unit::orderBy('name')->get();
    }

    #[Computed]
    public function suppliers(): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function purchaseOrders(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->supplierId) {
            return collect();
        }

        return PurchaseOrder::where('supplier_id', $this->supplierId)
            ->whereNotIn('status', ['received', 'cancelled'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function availableProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    public function updatedAddProductId(): void
    {
        $product = $this->addProductId ? Product::find($this->addProductId) : null;
        $this->addUnitCost = $product ? (float) $product->cost_price : 0;
    }

    public function addItem(): void
    {
        $this->validate([
            'addProductId'    => ['required', 'exists:products,id'],
            'addExpectedQty'  => ['required', 'numeric', 'min:0.001'],
            'addUnitCost'     => ['required', 'numeric', 'min:0'],
        ]);

        $product = Product::find($this->addProductId);
        $unit    = $this->addUnitId ? \App\Models\Unit::find($this->addUnitId) : null;

        $this->items[] = [
            'product_id'        => $product->id,
            'product_name'      => $product->name,
            'unit_id'           => $unit?->id,
            'unit_label'        => $unit?->abbreviation ?? '',
            'expected_quantity' => $this->addExpectedQty,
            'unit_cost'         => $this->addUnitCost,
        ];

        $this->addProductId   = null;
        $this->addExpectedQty = 1;
        $this->addUnitId      = null;
        $this->addUnitCost    = 0;
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'supplierId' => ['required', 'exists:suppliers,id'],
            'items'      => ['required', 'array', 'min:1'],
        ]);

        $delivery = DeliveryOrder::create([
            'delivery_number'   => DeliveryOrder::generateDeliveryNumber(),
            'supplier_id'       => $this->supplierId,
            'purchase_order_id' => $this->purchaseOrderId,
            'status'            => 'pending',
            'notes'             => $this->notes ?: null,
            'expected_at'       => $this->expectedAt ?: null,
            'created_by'        => Auth::id(),
        ]);

        foreach ($this->items as $item) {
            DeliveryItem::create([
                'delivery_order_id' => $delivery->id,
                'product_id'        => $item['product_id'],
                'product_name'      => $item['product_name'],
                'unit_id'           => $item['unit_id'] ?? null,
                'unit_label'        => $item['unit_label'] ?? null,
                'expected_quantity' => $item['expected_quantity'],
                'received_quantity' => 0,
                'unit_cost'         => $item['unit_cost'],
            ]);
        }

        $this->redirect(route('deliveries.index'), navigate: false);
    }
};
?>

<div>
    <flux:heading class="mb-4">New Delivery Order</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-1">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Delivery Details</flux:heading>
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Supplier</flux:label>
                        <flux:select wire:model.live="supplierId">
                            <flux:select.option value="">— Select Supplier —</flux:select.option>
                            @foreach ($this->suppliers as $supplier)
                                <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('supplierId') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>

                    @if ($supplierId && $this->purchaseOrders->isNotEmpty())
                        <flux:field>
                            <flux:label>Linked PO (optional)</flux:label>
                            <flux:select wire:model="purchaseOrderId">
                                <flux:select.option value="">— None —</flux:select.option>
                                @foreach ($this->purchaseOrders as $po)
                                    <flux:select.option value="{{ $po->id }}">{{ $po->po_number }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>Expected Date</flux:label>
                        <flux:input type="date" wire:model="expectedAt" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea wire:model="notes" rows="3" />
                    </flux:field>
                </div>
            </flux:card>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <flux:card>
                <flux:heading size="sm" class="mb-3">Add Items</flux:heading>
                <div class="flex gap-2 flex-wrap">
                    <flux:field class="flex-1">
                        <flux:label>Product</flux:label>
                        <flux:select wire:model.live="addProductId">
                            <flux:select.option value="">— Select Product —</flux:select.option>
                            @foreach ($this->availableProducts as $product)
                                <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('addProductId') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                    <flux:field class="w-28">
                        <flux:label>Qty</flux:label>
                        <flux:input type="number" min="0.001" step="0.001" wire:model="addExpectedQty" />
                        @error('addExpectedQty') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                    <flux:field class="w-32">
                        <flux:label>Unit</flux:label>
                        <flux:select wire:model="addUnitId">
                            <flux:select.option value="">— None —</flux:select.option>
                            @foreach ($this->units as $unit)
                                <flux:select.option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field class="w-32">
                        <flux:label>Unit Cost</flux:label>
                        <flux:input type="number" min="0" step="0.01" wire:model="addUnitCost" prefix="₱" />
                        @error('addUnitCost') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                    <div class="flex items-end pb-1">
                        <flux:button wire:click="addItem" variant="primary" icon="plus">Add</flux:button>
                    </div>
                </div>
            </flux:card>

            @if (! empty($items))
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Expected Qty</th>
                                <th class="px-4 py-2 text-right">Unit Cost</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr wire:key="di-{{ $index }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-4 py-2 font-medium">{{ $item['product_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item['expected_quantity'] }} {{ $item['unit_label'] ?? '' }}</td>
                                    <td class="px-4 py-2 text-right">₱{{ number_format($item['unit_cost'], 2) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">₱{{ number_format($item['expected_quantity'] * $item['unit_cost'], 2) }}</td>
                                    <td class="py-2 pr-2">
                                        <flux:button wire:click="removeItem({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @error('items') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('deliveries.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="save" variant="primary" icon="check">Create Delivery</flux:button>
            </div>
        </div>
    </div>
</div>
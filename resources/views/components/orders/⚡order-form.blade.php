<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Order')] class extends Component
{
    // Customer picker
    public ?int $customerId = null;

    // Client (auto-filled from customer or typed manually)
    public string $clientName = '';
    public string $clientPhone = '';
    public string $clientEmail = '';

    // Delivery
    public string $deliveryAddress = '';
    public string $deliveryDate = '';
    public string $deliveryTime = '';
    public string $deliveryNotes = '';

    // Shipping
    public string $vehicleType = '';
    public string $driverName = '';
    public string $driverPhone = '';
    public float $deliveryFee = 0;

    // Financials
    public float $discountAmount = 0;
    public float $taxAmount = 0;

    // Payment
    public string $paymentMethod = 'on_delivery';
    public string $paymentStatus = 'unpaid';
    public float $amountPaid = 0;

    public string $notes = '';

    // Item being added
    public ?int $addProductId = null;
    public string $addProductName = '';
    public string $addSku = '';
    public string $addUnit = '';
    public float $addQty = 1;
    public float $addUnitPrice = 0;
    public float $addDiscount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    #[Computed]
    public function customers(): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function availableProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->items)->sum('subtotal');
    }

    #[Computed]
    public function total(): float
    {
        return max(0, $this->subtotal + $this->deliveryFee - $this->discountAmount + $this->taxAmount);
    }

    public function updatedCustomerId(): void
    {
        if (! $this->customerId) {
            return;
        }

        $customer = Customer::find($this->customerId);
        if ($customer) {
            $this->clientName      = $customer->name;
            $this->clientPhone     = $customer->phone ?? '';
            $this->clientEmail     = $customer->email ?? '';
            $this->deliveryAddress = $customer->address ?? '';
        }
    }

    public function updatedAddProductId(): void
    {
        if (! $this->addProductId) {
            return;
        }

        $product = Product::find($this->addProductId);
        if ($product) {
            $this->addProductName = $product->name;
            $this->addSku         = $product->sku;
            $this->addUnit        = $product->unit?->abbreviation ?? '';
            $this->addUnitPrice   = (float) $product->selling_price;
        }
    }

    public function addItem(): void
    {
        $this->validate([
            'addProductName' => ['required', 'string'],
            'addQty'         => ['required', 'numeric', 'min:0.001'],
            'addUnitPrice'   => ['required', 'numeric', 'min:0'],
            'addDiscount'    => ['nullable', 'numeric', 'min:0'],
        ]);

        $subtotal = max(0, ($this->addQty * $this->addUnitPrice) - $this->addDiscount);

        $this->items[] = [
            'product_id'      => $this->addProductId,
            'product_name'    => $this->addProductName,
            'sku'             => $this->addSku,
            'unit'            => $this->addUnit,
            'quantity'        => $this->addQty,
            'unit_price'      => $this->addUnitPrice,
            'discount_amount' => $this->addDiscount,
            'subtotal'        => $subtotal,
        ];

        $this->resetAddFields();
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'clientName'      => ['required', 'string', 'max:255'],
            'deliveryAddress' => ['required', 'string'],
            'items'           => ['required', 'array', 'min:1'],
            'paymentMethod'   => ['required', 'in:cash,gcash,credit_card,bank_transfer,on_delivery'],
            'paymentStatus'   => ['required', 'in:unpaid,partial,paid'],
            'amountPaid'      => ['nullable', 'numeric', 'min:0'],
            'deliveryFee'     => ['nullable', 'numeric', 'min:0'],
            'discountAmount'  => ['nullable', 'numeric', 'min:0'],
            'taxAmount'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $subtotal = $this->subtotal;
        $total    = $this->total;

        $order = Order::create([
            'reference_no'    => Order::generateReferenceNo(),
            'customer_id'     => $this->customerId ?: null,
            'client_name'     => $this->clientName,
            'client_phone'    => $this->clientPhone ?: null,
            'client_email'    => $this->clientEmail ?: null,
            'delivery_address'=> $this->deliveryAddress,
            'delivery_date'   => $this->deliveryDate ?: null,
            'delivery_time'   => $this->deliveryTime ?: null,
            'delivery_notes'  => $this->deliveryNotes ?: null,
            'vehicle_type'    => $this->vehicleType ?: null,
            'driver_name'     => $this->driverName ?: null,
            'driver_phone'    => $this->driverPhone ?: null,
            'delivery_fee'    => $this->deliveryFee,
            'subtotal'        => $subtotal,
            'discount_amount' => $this->discountAmount,
            'tax_amount'      => $this->taxAmount,
            'total'           => $total,
            'payment_method'  => $this->paymentMethod,
            'payment_status'  => $this->paymentStatus,
            'amount_paid'     => $this->amountPaid,
            'status'          => 'pending',
            'notes'           => $this->notes ?: null,
            'created_by'      => Auth::id(),
        ]);

        foreach ($this->items as $item) {
            OrderItem::create([
                'order_id'        => $order->id,
                'product_id'      => $item['product_id'],
                'product_name'    => $item['product_name'],
                'sku'             => $item['sku'] ?: null,
                'unit'            => $item['unit'] ?: null,
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'discount_amount' => $item['discount_amount'],
                'subtotal'        => $item['subtotal'],
            ]);
        }

        $this->redirect(route('orders.index'), navigate: true);
    }

    private function resetAddFields(): void
    {
        $this->addProductId   = null;
        $this->addProductName = '';
        $this->addSku         = '';
        $this->addUnit        = '';
        $this->addQty         = 1;
        $this->addUnitPrice   = 0;
        $this->addDiscount    = 0;
        $this->resetValidation();
    }
};
?>

<div>
    <flux:heading class="mb-4">New Customer Order</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT: Client + Delivery + Payment --}}
        <div class="space-y-4 lg:col-span-1">

            <flux:card>
                <flux:heading size="sm" class="mb-3">Client Info</flux:heading>
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Select Existing Customer</flux:label>
                        <flux:select wire:model.live="customerId">
                            <flux:select.option value="">— New / Walk-in —</flux:select.option>
                            @foreach ($this->customers as $customer)
                                <flux:select.option value="{{ $customer->id }}">
                                    {{ $customer->name }}{{ $customer->phone ? ' · ' . $customer->phone : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        @if (! $customerId)
                            <flux:description>
                                Or fill in the details below manually.
                                <a href="{{ route('customers.index') }}" wire:navigate class="text-accent-600 dark:text-accent-400 underline">Add customer</a>
                            </flux:description>
                        @endif
                    </flux:field>

                    <flux:field>
                        <flux:label>Client Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                        <flux:input wire:model="clientName" placeholder="e.g. Juan dela Cruz" />
                        @error('clientName') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input wire:model="clientPhone" placeholder="09XX XXX XXXX" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="clientEmail" placeholder="optional" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-3">Delivery Details</flux:heading>
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Delivery Address <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                        <flux:textarea wire:model="deliveryAddress" rows="2" placeholder="Full address" />
                        @error('deliveryAddress') <flux:error>{{ $message }}</flux:error> @enderror
                    </flux:field>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:field>
                            <flux:label>Date</flux:label>
                            <flux:input type="date" wire:model="deliveryDate" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Time</flux:label>
                            <flux:input type="time" wire:model="deliveryTime" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Vehicle Type</flux:label>
                        <flux:input wire:model="vehicleType" placeholder="e.g. Truck, Pickup" />
                    </flux:field>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:field>
                            <flux:label>Driver Name</flux:label>
                            <flux:input wire:model="driverName" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Driver Phone</flux:label>
                            <flux:input wire:model="driverPhone" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Delivery Notes</flux:label>
                        <flux:textarea wire:model="deliveryNotes" rows="2" placeholder="Special instructions" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="sm" class="mb-3">Payment</flux:heading>
                <div class="space-y-3">
                    <flux:field>
                        <flux:label>Payment Method</flux:label>
                        <flux:select wire:model="paymentMethod">
                            <flux:select.option value="on_delivery">On Delivery</flux:select.option>
                            <flux:select.option value="cash">Cash</flux:select.option>
                            <flux:select.option value="gcash">GCash</flux:select.option>
                            <flux:select.option value="bank_transfer">Bank Transfer</flux:select.option>
                            <flux:select.option value="credit_card">Credit Card</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Payment Status</flux:label>
                        <flux:select wire:model="paymentStatus">
                            <flux:select.option value="unpaid">Unpaid</flux:select.option>
                            <flux:select.option value="partial">Partial</flux:select.option>
                            <flux:select.option value="paid">Paid</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Amount Paid</flux:label>
                        <flux:input type="number" min="0" step="0.01" wire:model="amountPaid" prefix="₱" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:field>
                <flux:label>Order Notes</flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Internal notes" />
            </flux:field>
        </div>

        {{-- RIGHT: Items + Summary --}}
        <div class="space-y-4 lg:col-span-2">

            <flux:card>
                <flux:heading size="sm" class="mb-3">Add Item</flux:heading>
                <div class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <flux:field class="flex-1 min-w-40">
                            <flux:label>From Product (optional)</flux:label>
                            <flux:select wire:model.live="addProductId">
                                <flux:select.option value="">— Pick or type manually —</flux:select.option>
                                @foreach ($this->availableProducts as $product)
                                    <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:field class="flex-1 min-w-40">
                            <flux:label>Product Name <flux:badge size="xs" color="red">Required</flux:badge></flux:label>
                            <flux:input wire:model="addProductName" placeholder="e.g. Portland Cement 40kg" />
                            @error('addProductName') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                        <flux:field class="w-28">
                            <flux:label>SKU</flux:label>
                            <flux:input wire:model="addSku" placeholder="optional" />
                        </flux:field>
                        <flux:field class="w-24">
                            <flux:label>Unit</flux:label>
                            <flux:input wire:model="addUnit" placeholder="bags, pcs" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap items-end gap-2">
                        <flux:field class="w-28">
                            <flux:label>Qty</flux:label>
                            <flux:input type="number" min="0.001" step="0.001" wire:model="addQty" />
                            @error('addQty') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                        <flux:field class="w-36">
                            <flux:label>Unit Price</flux:label>
                            <flux:input type="number" min="0" step="0.01" wire:model="addUnitPrice" prefix="₱" />
                            @error('addUnitPrice') <flux:error>{{ $message }}</flux:error> @enderror
                        </flux:field>
                        <flux:field class="w-36">
                            <flux:label>Item Discount</flux:label>
                            <flux:input type="number" min="0" step="0.01" wire:model="addDiscount" prefix="₱" />
                        </flux:field>
                        <div class="pb-1">
                            <flux:button wire:click="addItem" variant="primary" icon="plus">Add</flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>

            @error('items')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            @if (! empty($items))
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-2 text-left font-medium">Product</th>
                                <th class="px-4 py-2 text-right font-medium">Qty</th>
                                <th class="px-4 py-2 text-right font-medium">Unit Price</th>
                                <th class="px-4 py-2 text-right font-medium">Discount</th>
                                <th class="px-4 py-2 text-right font-medium">Subtotal</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr wire:key="oi-{{ $index }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                    <td class="px-4 py-2">
                                        <p class="font-medium">{{ $item['product_name'] }}</p>
                                        @if ($item['sku'])
                                            <p class="text-xs text-zinc-400">{{ $item['sku'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ $item['quantity'] + 0 }} {{ $item['unit'] }}</td>
                                    <td class="px-4 py-2 text-right">₱{{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item['discount_amount'] > 0 ? '₱' . number_format($item['discount_amount'], 2) : '—' }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">₱{{ number_format($item['subtotal'], 2) }}</td>
                                    <td class="py-2 pr-2">
                                        <flux:button wire:click="removeItem({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Order Summary --}}
            <flux:card>
                <flux:heading size="sm" class="mb-3">Order Summary</flux:heading>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>Delivery Fee</flux:label>
                            <flux:input type="number" min="0" step="0.01" wire:model.live="deliveryFee" prefix="₱" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Order Discount</flux:label>
                            <flux:input type="number" min="0" step="0.01" wire:model.live="discountAmount" prefix="₱" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Tax</flux:label>
                            <flux:input type="number" min="0" step="0.01" wire:model.live="taxAmount" prefix="₱" />
                        </flux:field>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700 space-y-1">
                        <div class="flex justify-between text-zinc-500">
                            <span>Items Subtotal</span>
                            <span>₱{{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        @if ($deliveryFee > 0)
                            <div class="flex justify-between text-zinc-500">
                                <span>Delivery Fee</span>
                                <span>₱{{ number_format($deliveryFee, 2) }}</span>
                            </div>
                        @endif
                        @if ($discountAmount > 0)
                            <div class="flex justify-between text-zinc-500">
                                <span>Discount</span>
                                <span class="text-red-500">-₱{{ number_format($discountAmount, 2) }}</span>
                            </div>
                        @endif
                        @if ($taxAmount > 0)
                            <div class="flex justify-between text-zinc-500">
                                <span>Tax</span>
                                <span>₱{{ number_format($taxAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-bold dark:border-zinc-700">
                            <span>Total</span>
                            <span>₱{{ number_format($this->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </flux:card>

            <div class="flex justify-end gap-2">
                <flux:button href="{{ route('orders.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
                <flux:button wire:click="save" variant="primary" icon="check">Create Order</flux:button>
            </div>
        </div>
    </div>
</div>

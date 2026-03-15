<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('POS Terminal')] class extends Component
{
    public string $search = '';
    public string $categoryFilter = '';

    /** @var array<int, array<string, mixed>> */
    public array $cartItems = [];

    public string $paymentMethod = 'cash';
    public string $discountType = 'fixed';
    public float $discountValue = 0;
    public float $amountTendered = 0;
    public bool $showCheckout = false;
    public bool $showReceipt = false;
    public ?int $completedTransactionId = null;

    /** @return \Illuminate\Database\Eloquent\Collection<int, Product> */
    #[Computed]
    public function searchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (strlen(trim($this->search)) < 2) {
            return Product::query()->whereRaw('0=1')->get();
        }

        return Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->with(['category', 'unit'])
            ->limit(10)
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Category> */
    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Product> */
    #[Computed]
    public function products(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->with(['unit'])
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->get();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $existingIndex = $this->findCartItemIndex($productId);

        if ($existingIndex !== null) {
            $this->cartItems[$existingIndex]['quantity']++;
            $this->recalculateItemSubtotal($existingIndex);
        } else {
            $this->cartItems[] = [
                'product_id'      => $product->id,
                'sku'             => $product->sku,
                'name'            => $product->name,
                'unit_price'      => (float) $product->selling_price,
                'original_price'  => (float) $product->selling_price,
                'quantity'        => 1,
                'discount_amount' => 0,
                'subtotal'        => (float) $product->selling_price,
            ];
        }

        $this->search = '';
        unset($this->searchResults, $this->products);
    }

    public function updateQuantity(int $index, float $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($index);

            return;
        }

        $this->cartItems[$index]['quantity'] = $quantity;
        $this->recalculateItemSubtotal($index);
    }

    public function updateUnitPrice(int $index, float $price): void
    {
        $this->cartItems[$index]['unit_price'] = max(0, $price);
        $this->recalculateItemSubtotal($index);
    }

    public function updateItemDiscount(int $index, float $discount): void
    {
        $this->cartItems[$index]['discount_amount'] = max(0, $discount);
        $this->recalculateItemSubtotal($index);
    }

    public function incrementQty(int $index, int $delta): void
    {
        $newQty = ($this->cartItems[$index]['quantity'] ?? 1) + $delta;
        $this->updateQuantity($index, $newQty);
    }

    public function removeItem(int $index): void
    {
        array_splice($this->cartItems, $index, 1);
        $this->cartItems = array_values($this->cartItems);
    }

    public function clearCart(): void
    {
        $this->cartItems      = [];
        $this->discountValue  = 0;
        $this->amountTendered = 0;
        $this->showCheckout   = false;
    }

    public function cartSubtotal(): float
    {
        return array_sum(array_column($this->cartItems, 'subtotal'));
    }

    public function globalDiscount(): float
    {
        if ($this->discountType === 'percent') {
            return round($this->cartSubtotal() * ($this->discountValue / 100), 2);
        }

        return (float) $this->discountValue;
    }

    public function cartTotal(): float
    {
        return max(0, $this->cartSubtotal() - $this->globalDiscount());
    }

    public function changeAmount(): float
    {
        return max(0, $this->amountTendered - $this->cartTotal());
    }

    public function checkout(): void
    {
        if (empty($this->cartItems)) {
            $this->addError('cart', 'Cart is empty.');

            return;
        }

        if ($this->amountTendered < $this->cartTotal()) {
            $this->addError('amountTendered', 'Amount tendered is less than the total.');

            return;
        }

        DB::transaction(function () {
            $transaction = Transaction::create([
                'reference_no'    => Transaction::generateReferenceNo(),
                'cashier_id'      => Auth::id(),
                'subtotal'        => $this->cartSubtotal(),
                'discount_amount' => $this->globalDiscount(),
                'tax_amount'      => 0,
                'total'           => $this->cartTotal(),
                'payment_method'  => $this->paymentMethod,
                'amount_tendered' => $this->amountTendered,
                'change_amount'   => $this->changeAmount(),
                'status'          => 'completed',
            ]);

            foreach ($this->cartItems as $item) {
                TransactionItem::create([
                    'transaction_id'  => $transaction->id,
                    'product_id'      => $item['product_id'],
                    'product_name'    => $item['name'],
                    'sku'             => $item['sku'],
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal'        => $item['subtotal'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $before                  = (float) $product->stock_quantity;
                    $product->stock_quantity -= $item['quantity'];
                    $product->save();

                    StockMovement::create([
                        'product_id'      => $product->id,
                        'type'            => 'out',
                        'quantity'        => $item['quantity'],
                        'before_quantity' => $before,
                        'after_quantity'  => (float) $product->stock_quantity,
                        'reason'          => 'Sale',
                        'reference'       => $transaction->reference_no,
                        'user_id'         => Auth::id(),
                    ]);
                }
            }

            $this->completedTransactionId = $transaction->id;
        });

        $this->cartItems         = [];
        $this->discountValue     = 0;
        $this->amountTendered    = 0;
        $this->showCheckout      = false;
        $this->showReceipt       = true;
    }

    public function closeReceipt(): void
    {
        $this->showReceipt            = false;
        $this->completedTransactionId = null;
    }

    private function findCartItemIndex(int $productId): ?int
    {
        foreach ($this->cartItems as $index => $item) {
            if ($item['product_id'] === $productId) {
                return $index;
            }
        }

        return null;
    }

    private function recalculateItemSubtotal(int $index): void
    {
        $item                               = $this->cartItems[$index];
        $this->cartItems[$index]['subtotal'] = round(
            ($item['quantity'] * $item['unit_price']) - $item['discount_amount'],
            2
        );
    }
};
?>

<div class="flex h-full min-h-0 gap-4">
    {{-- Left: Product Browser --}}
    <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-3">

        {{-- Search --}}
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search product by name or SKU..."
            icon="magnifying-glass"
            autofocus
        />

        {{-- Category Tabs --}}
        <div class="flex flex-wrap gap-1.5">
            <button
                wire:click="$set('categoryFilter', '')"
                class="rounded-full border px-3 py-1 text-xs font-medium transition
                    {{ $categoryFilter === ''
                        ? 'border-blue-500 bg-blue-500 text-white'
                        : 'border-zinc-300 bg-white text-zinc-600 hover:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}"
            >All</button>
            @foreach ($this->categories as $cat)
                <button
                    wire:key="cat-tab-{{ $cat->id }}"
                    wire:click="$set('categoryFilter', '{{ $cat->id }}')"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition
                        {{ $categoryFilter == $cat->id
                            ? 'border-blue-500 bg-blue-500 text-white'
                            : 'border-zinc-300 bg-white text-zinc-600 hover:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}"
                >{{ $cat->name }}</button>
            @endforeach
        </div>

        {{-- Product Grid --}}
        <div class="min-h-0 flex-1 overflow-y-auto">
            @if ($this->products->isEmpty())
                <div class="flex h-32 items-center justify-center text-zinc-400">
                    <p class="text-sm">No products found.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-2 pb-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->products as $product)
                        @php $outOfStock = $product->stock_quantity <= 0; @endphp
                        <button
                            wire:key="prod-grid-{{ $product->id }}"
                            @if (!$outOfStock) wire:click="addToCart({{ $product->id }})" @endif
                            @disabled($outOfStock)
                            class="flex flex-col items-start gap-1 rounded-xl border p-3 text-left transition
                                {{ $outOfStock
                                    ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-50 dark:border-zinc-700 dark:bg-zinc-800'
                                    : 'cursor-pointer border-zinc-200 bg-white hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:border-blue-500 dark:hover:bg-zinc-700' }}"
                        >
                            <p class="line-clamp-2 text-sm font-semibold leading-tight text-zinc-800 dark:text-zinc-100">{{ $product->name }}</p>
                            <p class="text-base font-bold text-blue-600 dark:text-blue-400">₱{{ number_format($product->selling_price, 2) }}</p>
                            @if ($outOfStock)
                                <flux:badge size="sm" color="red">Out of Stock</flux:badge>
                            @else
                                <p class="text-xs text-zinc-400">{{ number_format($product->stock_quantity, 0) }} {{ $product->unit?->abbreviation }}</p>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Cart + Checkout --}}
    <div class="flex min-h-0 w-72 shrink-0 flex-col gap-3">

        {{-- Cart Header --}}
        <div class="flex shrink-0 items-center justify-between">
            <flux:heading class="flex items-center gap-2">
                Cart
                @if (!empty($cartItems))
                    <flux:badge color="blue">{{ count($cartItems) }}</flux:badge>
                @endif
            </flux:heading>
            <flux:button wire:click="clearCart" size="sm" variant="ghost" icon="trash">Clear</flux:button>
        </div>

        {{-- Cart Items --}}
        <div class="min-h-0 flex-1 overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            @if (empty($cartItems))
                <div class="flex h-48 items-center justify-center text-zinc-400">
                    <div class="text-center">
                        <flux:icon.shopping-cart class="mx-auto mb-2 size-10 opacity-40" />
                        <p class="text-sm">Click a product to add it</p>
                    </div>
                </div>
            @else
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($cartItems as $index => $item)
                        <li wire:key="cart-{{ $index }}" class="flex items-center gap-2 px-3 py-2">
                            {{-- Name + price --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item['name'] }}</p>
                                <p class="text-xs text-zinc-400">₱{{ number_format($item['unit_price'], 2) }}</p>
                            </div>

                            {{-- Qty stepper --}}
                            <div class="flex shrink-0 items-center rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <button
                                    wire:click="incrementQty({{ $index }}, -1)"
                                    class="flex h-7 w-7 items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-l-lg"
                                    type="button"
                                >−</button>
                                <input
                                    type="number" min="1" step="1"
                                    class="h-7 w-10 border-x border-zinc-200 bg-white text-center text-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                    value="{{ (int) $item['quantity'] }}"
                                    wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                />
                                <button
                                    wire:click="incrementQty({{ $index }}, 1)"
                                    class="flex h-7 w-7 items-center justify-center text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-r-lg"
                                    type="button"
                                >+</button>
                            </div>

                            {{-- Subtotal --}}
                            <span class="w-16 shrink-0 text-right text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                ₱{{ number_format($item['subtotal'], 2) }}
                            </span>

                            {{-- Remove --}}
                            <button
                                wire:click="removeItem({{ $index }})"
                                class="shrink-0 text-zinc-300 hover:text-red-500 dark:text-zinc-600 dark:hover:text-red-400"
                                type="button"
                            >
                                <flux:icon.x-mark class="size-4" />
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @error('cart')
            <p class="shrink-0 text-sm text-red-500">{{ $message }}</p>
        @enderror

        {{-- Order Summary --}}
        <div class="shrink-0 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    <span class="font-medium">₱{{ number_format($this->cartSubtotal(), 2) }}</span>
                </div>

                {{-- Global Discount --}}
                <div class="flex items-center gap-2">
                    <span class="shrink-0 text-zinc-600 dark:text-zinc-400">Discount</span>
                    <div class="ml-auto flex items-center gap-1">
                        <select wire:model.live="discountType" class="rounded border border-zinc-300 bg-white px-1 py-0.5 text-xs dark:border-zinc-600 dark:bg-zinc-700">
                            <option value="fixed">₱</option>
                            <option value="percent">%</option>
                        </select>
                        <input
                            type="number" min="0" step="0.01"
                            wire:model.live="discountValue"
                            class="w-20 rounded border border-zinc-300 bg-white px-2 py-0.5 text-right text-xs dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        />
                    </div>
                </div>

                @if ($this->globalDiscount() > 0)
                    <div class="flex justify-between text-red-600 dark:text-red-400">
                        <span>- Discount</span>
                        <span>-₱{{ number_format($this->globalDiscount(), 2) }}</span>
                    </div>
                @endif

                <div class="border-t border-zinc-300 pt-2 dark:border-zinc-600">
                    <div class="flex justify-between text-base font-bold">
                        <span>TOTAL</span>
                        <span>₱{{ number_format($this->cartTotal(), 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Method --}}
            <div class="mt-4">
                <flux:label class="mb-1 text-xs">Payment Method</flux:label>
                <div class="flex gap-2">
                    @foreach (['cash' => 'Cash', 'gcash' => 'GCash', 'credit_card' => 'Card'] as $value => $label)
                        <button
                            wire:click="$set('paymentMethod', '{{ $value }}')"
                            class="flex-1 rounded-lg border px-2 py-2 text-xs font-medium transition
                                {{ $paymentMethod === $value
                                    ? 'border-blue-500 bg-blue-100 text-blue-700 dark:border-blue-400 dark:bg-blue-900 dark:text-blue-300'
                                    : 'border-zinc-300 bg-white text-zinc-700 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Amount Tendered --}}
            <div class="mt-3">
                <flux:field>
                    <flux:label class="text-xs">Amount Tendered</flux:label>
                    <flux:input
                        type="number" min="0" step="0.01"
                        wire:model.live="amountTendered"
                        prefix="₱"
                    />
                    @error('amountTendered') <flux:error>{{ $message }}</flux:error> @enderror
                </flux:field>
            </div>

            @if ($amountTendered > 0 && $amountTendered >= $this->cartTotal())
                <div class="mt-2 rounded-lg bg-green-100 px-3 py-2 text-center dark:bg-green-900/40">
                    <p class="text-xs text-green-700 dark:text-green-300">Change</p>
                    <p class="text-xl font-bold text-green-700 dark:text-green-300">₱{{ number_format($this->changeAmount(), 2) }}</p>
                </div>
            @endif
        </div>

        <flux:button
            wire:click="checkout"
            variant="primary"
            class="w-full shrink-0 py-3 text-base!"
            icon="check"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>Checkout &mdash; ₱{{ number_format($this->cartTotal(), 2) }}</span>
            <span wire:loading>Processing...</span>
        </flux:button>
    </div>

    {{-- Receipt Modal --}}
    @if ($showReceipt && $completedTransactionId)
        @php $receiptTx = \App\Models\Transaction::with(['items', 'cashier'])->find($completedTransactionId); @endphp
        @if ($receiptTx)
            <flux:modal wire:model="showReceipt" class="max-w-sm">
                <div class="text-center">
                    <flux:heading size="lg">Receipt</flux:heading>
                    <p class="mt-1 text-sm text-zinc-500">{{ $receiptTx->reference_no }}</p>
                    <p class="text-xs text-zinc-400">{{ $receiptTx->created_at->format('M d, Y h:i A') }}</p>
                    <p class="text-xs text-zinc-400">Cashier: {{ $receiptTx->cashier?->name }}</p>
                </div>

                <hr class="my-3 border-dashed border-zinc-300 dark:border-zinc-600" />

                <div class="space-y-1 text-sm">
                    @foreach ($receiptTx->items as $item)
                        <div class="flex justify-between">
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $item->product_name }}</span>
                            <span>₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                        <div class="text-xs text-zinc-500">{{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</div>
                    @endforeach
                </div>

                <hr class="my-3 border-dashed border-zinc-300 dark:border-zinc-600" />

                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Subtotal</span><span>₱{{ number_format($receiptTx->subtotal, 2) }}</span>
                    </div>
                    @if ($receiptTx->discount_amount > 0)
                        <div class="flex justify-between text-red-600">
                            <span>Discount</span><span>-₱{{ number_format($receiptTx->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-base font-bold">
                        <span>TOTAL</span><span>₱{{ number_format($receiptTx->total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-500">
                        <span>Tendered ({{ ucfirst(str_replace('_', ' ', $receiptTx->payment_method)) }})</span>
                        <span>₱{{ number_format($receiptTx->amount_tendered, 2) }}</span>
                    </div>
                    @if ($receiptTx->change_amount > 0)
                        <div class="flex justify-between font-semibold text-green-700 dark:text-green-400">
                            <span>Change</span><span>₱{{ number_format($receiptTx->change_amount, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex gap-2">
                    <flux:button
                        onclick="window.print()"
                        variant="ghost"
                        class="flex-1"
                        icon="printer"
                    >Print</flux:button>
                    <flux:button
                        wire:click="closeReceipt"
                        variant="primary"
                        class="flex-1"
                    >New Sale</flux:button>
                </div>
            </flux:modal>
        @endif
    @endif
</div>

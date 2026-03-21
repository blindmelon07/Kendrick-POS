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

    public function addToCartBySku(string $sku): void
    {
        $product = Product::where('sku', trim($sku))
            ->where('is_active', true)
            ->first();

        if (! $product) {
            $this->dispatch('scan-result', status: 'error', message: "SKU not found: {$sku}");
            return;
        }

        if ($product->stock_quantity <= 0) {
            $this->dispatch('scan-result', status: 'error', message: "{$product->name} is out of stock.");
            return;
        }

        $this->addToCart($product->id);
        $this->dispatch('scan-result', status: 'success', message: "Added: {$product->name}");
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

<div
    class="flex h-full min-h-0 flex-col gap-4 lg:flex-row"
    x-data="{
        tab: 'products',

        // ── Barcode scanner ──────────────────────────────────────
        _buf: '',
        _t0: 0,
        _fast: true,
        _timer: null,
        scanToast: null,
        scanStatus: 'success',

        initScanner() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    if (this._buf.length >= 3 && this._fast) {
                        e.preventDefault();
                        $wire.addToCartBySku(this._buf);
                        this.tab = 'cart';
                    }
                    this._buf = '';
                    this._fast = true;
                    return;
                }

                if (e.key.length !== 1 || e.ctrlKey || e.altKey || e.metaKey) return;

                const now = Date.now();
                const diff = now - this._t0;
                this._t0 = now;

                if (diff > 500) {
                    // Long pause — restart buffer fresh
                    this._buf = '';
                    this._fast = true;
                } else if (diff > 100) {
                    // Human typing speed — not a scanner
                    this._fast = false;
                }

                this._buf += e.key;
            });
        },

        showToast(status, msg) {
            this.scanStatus = status;
            this.scanToast = msg;
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.scanToast = null, 3000);
        },

        // ── Barcode Label Modal ──────────────────────────────────
        barcodeModal: null,

        openBarcode(product) {
            this.barcodeModal = product;
        },

        renderBarcode(sku) {
            if (window.JsBarcode) {
                JsBarcode('#barcode-svg', sku, {
                    format: 'CODE128',
                    width: 2,
                    height: 70,
                    displayValue: true,
                    fontSize: 13,
                    margin: 10,
                    background: '#ffffff',
                    lineColor: '#000000',
                });
            }
        },

        printBarcode() {
            window.posPrintBarcode(this.barcodeModal);
        },

        // ── Camera Scanner ───────────────────────────────────────
        cameraModal: false,

        openCamera() {
            this.cameraModal = true;
            setTimeout(() => window.posStartCamera(), 200);
        },

        closeCamera() {
            window.posStopCamera();
            this.cameraModal = false;
        }
    }"
    x-init="
        initScanner();
        $watch('barcodeModal', val => { if (val) $nextTick(() => renderBarcode(val.sku)); });
    "
    @scan-result.window="showToast($event.detail.status, $event.detail.message)"
    @open-barcode.window="openBarcode($event.detail)"
    @pos-camera-done.document="cameraModal = false"
    @pos-barcode-scanned.document="$wire.addToCartBySku($event.detail); cameraModal = false; tab = 'cart'"
    @keydown.escape.window="barcodeModal = null; closeCamera()"
>
    {{-- Scan Toast Notification --}}
    <div
        x-show="scanToast !== null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        :class="scanStatus === 'success' ? 'bg-emerald-600' : 'bg-red-600'"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl"
        style="display: none;"
    >
        <span x-show="scanStatus === 'success'">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </span>
        <span x-show="scanStatus === 'error'">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </span>
        <span x-text="scanToast"></span>
    </div>

    {{-- Camera Scanner Modal --}}
    <div
        x-show="cameraModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/80 p-4"
        style="display: none;"
    >
        <div class="w-full max-w-sm">
            {{-- Header --}}
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="text-lg font-bold text-white">Camera Scanner</p>
                    <p class="text-xs text-zinc-400">Point camera at a barcode</p>
                </div>
                <button
                    type="button"
                    @click="closeCamera()"
                    class="rounded-xl bg-white/10 p-2 text-white hover:bg-white/20"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Camera Feed --}}
            <div class="overflow-hidden rounded-2xl bg-zinc-900 shadow-2xl" style="min-height: 280px;">
                <div id="camera-feed" style="width: 100%; min-height: 280px;"></div>
            </div>

            {{-- Status --}}
            <div id="camera-status" class="mt-3 text-center text-sm text-zinc-400">
                Starting camera...
            </div>

            {{-- Cancel button --}}
            <button
                type="button"
                @click="closeCamera()"
                class="mt-4 w-full rounded-xl border border-white/20 py-3 text-sm font-medium text-white hover:bg-white/10"
            >Cancel</button>
        </div>
    </div>

    {{-- Mobile tab switcher --}}
    <div class="flex shrink-0 gap-2 lg:hidden">
        <button
            @click="tab = 'products'"
            :class="tab === 'products' ? 'border-blue-500 bg-blue-500 text-white' : 'border-zinc-300 bg-white text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'"
            class="flex-1 rounded-xl border py-2 text-sm font-medium transition"
        >Products</button>
        <button
            @click="tab = 'cart'"
            :class="tab === 'cart' ? 'border-blue-500 bg-blue-500 text-white' : 'border-zinc-300 bg-white text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'"
            class="flex-1 rounded-xl border py-2 text-sm font-medium transition"
        >
            Cart
            @if (!empty($cartItems))
                <span class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-xs font-bold">{{ count($cartItems) }}</span>
            @endif
        </button>
    </div>

    {{-- Left: Product Browser --}}
    <div
        class="flex min-h-0 min-w-0 flex-1 flex-col gap-3"
        :class="tab === 'products' ? 'flex' : 'hidden lg:flex'"
    >

        {{-- Search + Camera + Barcode indicator --}}
        <div class="flex items-center gap-2">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search product by name or SKU..."
                    icon="magnifying-glass"
                    autofocus
                />
            </div>

            {{-- Camera Scan Button --}}
            <button
                type="button"
                @click="openCamera()"
                class="flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                title="Scan barcode using camera"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
                <span class="hidden sm:inline">Camera</span>
            </button>

            {{-- Physical Scanner Ready indicator --}}
            <div class="flex shrink-0 items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-2 text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                <span class="relative flex size-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                </span>
                <span class="hidden sm:inline">Scan Ready</span>
            </div>
        </div>

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
                        <div
                            wire:key="prod-grid-{{ $product->id }}"
                            x-data="{ prod: {{ Js::from(['name' => $product->name, 'sku' => $product->sku, 'price' => number_format($product->selling_price, 2)]) }} }"
                            @if (!$outOfStock)
                                wire:click="addToCart({{ $product->id }})"
                                @click="tab = 'cart'"
                            @endif
                            class="relative flex flex-col items-start gap-1 rounded-xl border p-3 text-left transition
                                {{ $outOfStock
                                    ? 'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-50 dark:border-zinc-700 dark:bg-zinc-800'
                                    : 'cursor-pointer border-zinc-200 bg-white hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:border-blue-500 dark:hover:bg-zinc-700' }}"
                        >
                            {{-- Barcode icon button --}}
                            <button
                                type="button"
                                @click.stop="$dispatch('open-barcode', prod)"
                                class="absolute right-1.5 top-1.5 rounded-md p-1 text-zinc-300 transition hover:bg-zinc-200 hover:text-zinc-600 dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                                title="View / Print Barcode"
                            >
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <rect x="1"  y="2" width="2" height="16" rx="0.4"/>
                                    <rect x="5"  y="2" width="1" height="16" rx="0.4"/>
                                    <rect x="8"  y="2" width="2" height="16" rx="0.4"/>
                                    <rect x="12" y="2" width="1" height="16" rx="0.4"/>
                                    <rect x="15" y="2" width="2" height="16" rx="0.4"/>
                                    <rect x="18" y="2" width="1" height="16" rx="0.4"/>
                                </svg>
                            </button>

                            <p class="line-clamp-2 pr-5 text-sm font-semibold leading-tight text-zinc-800 dark:text-zinc-100">{{ $product->name }}</p>
                            <p class="text-base font-bold text-blue-600 dark:text-blue-400">₱{{ number_format($product->selling_price, 2) }}</p>
                            @if ($outOfStock)
                                <flux:badge size="sm" color="red">Out of Stock</flux:badge>
                            @else
                                <p class="text-xs text-zinc-400">{{ number_format($product->stock_quantity, 0) }} {{ $product->unit?->abbreviation }}</p>
                            @endif
                            <p class="mt-0.5 text-xs font-mono text-zinc-300 dark:text-zinc-600">{{ $product->sku }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Cart + Checkout --}}
    <div
        class="flex min-h-0 shrink-0 flex-col gap-3 lg:w-72"
        :class="tab === 'cart' ? 'flex' : 'hidden lg:flex'"
    >

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

    {{-- Barcode Label Modal --}}
    <div
        x-show="barcodeModal !== null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="barcodeModal = null"
        style="display: none;"
    >
        <div
            x-show="barcodeModal !== null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-xs rounded-2xl bg-white shadow-2xl dark:bg-zinc-900"
        >
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div>
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wide">Barcode Label</p>
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="barcodeModal?.name"></p>
                </div>
                <button
                    type="button"
                    @click="barcodeModal = null"
                    class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Barcode Preview --}}
            <div class="flex flex-col items-center px-5 py-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700">
                    <svg id="barcode-svg"></svg>
                </div>
                <p class="mt-3 font-mono text-sm font-medium text-zinc-500 dark:text-zinc-400" x-text="barcodeModal?.sku"></p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400" x-text="'₱' + barcodeModal?.price"></p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2 border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <button
                    type="button"
                    @click="barcodeModal = null"
                    class="flex-1 rounded-xl border border-zinc-300 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >Close</button>
                <button
                    type="button"
                    @click="printBarcode()"
                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Label
                </button>
            </div>
        </div>
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

@script
<script>
// ── Camera Scanner ────────────────────────────────────────────
var _posScanner = null;

window.posStartCamera = function () {
    var statusEl = document.getElementById('camera-status');

    function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg;
    }

    if (!window.Html5Qrcode) {
        setStatus('Camera library not ready. Please refresh the page.');
        return;
    }

    // Explicitly support 1D barcode formats — without this, only QR codes are scanned
    var formats = [
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39,
        Html5QrcodeSupportedFormats.CODE_93,
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.EAN_8,
        Html5QrcodeSupportedFormats.UPC_A,
        Html5QrcodeSupportedFormats.UPC_E,
        Html5QrcodeSupportedFormats.ITF,
        Html5QrcodeSupportedFormats.QR_CODE,
    ];

    Html5Qrcode.getCameras().then(function (cameras) {
        if (!cameras || cameras.length === 0) {
            setStatus('No camera found on this device.');
            return;
        }

        // Prefer back/environment camera on mobile; fall back to first available
        var cam = cameras.find(function (c) {
            var label = (c.label || '').toLowerCase();
            return label.includes('back') || label.includes('rear') || label.includes('environment');
        }) || cameras[0];

        _posScanner = new Html5Qrcode('camera-feed');

        var config = {
            fps: 15,
            qrbox: { width: 250, height: 100 },
            formatsToSupport: formats,
        };

        _posScanner.start(
            cam.id,
            config,
            function (decodedText) {
                var sku = decodedText.trim();
                window.posStopCamera();
                // Dispatch to Alpine — $wire is called safely in the template listener
                document.dispatchEvent(new CustomEvent('pos-barcode-scanned', { detail: sku }));
            },
            function () { /* per-frame decode miss — normal, keep scanning */ }
        ).then(function () {
            setStatus('Scanning — aim the barcode at the green box.');
        }).catch(function (err) {
            var msg = (err || '').toString();
            if (msg.toLowerCase().includes('permission') || msg.toLowerCase().includes('notallowed')) {
                setStatus('Camera permission denied. Please allow camera access in your browser settings.');
            } else {
                setStatus('Could not start camera: ' + msg);
            }
        });

    }).catch(function () {
        setStatus('Cannot access camera. Check that your browser has camera permission.');
    });
};

window.posStopCamera = function () {
    if (_posScanner) {
        _posScanner.stop().catch(function () {}).finally(function () {
            if (_posScanner) { _posScanner.clear(); }
            _posScanner = null;
        });
    }
};

// ── Barcode Label Printer ─────────────────────────────────────
window.posPrintBarcode = function (product) {
    var svgEl = document.getElementById('barcode-svg');
    var svgContent = svgEl ? svgEl.outerHTML : '';
    var w = window.open('', '_blank', 'width=420,height=340');
    var html = [
        '<!DOCTYPE html><html><head><title>Barcode Label</title>',
        '<style>',
        '@media print { body { margin: 0; } }',
        'body { font-family: sans-serif; text-align: center; padding: 24px; }',
        '.lbl-name  { font-size: 15px; font-weight: 700; margin-bottom: 4px; }',
        '.lbl-sku   { font-size: 11px; color: #888; margin-bottom: 10px; }',
        '.lbl-price { font-size: 22px; font-weight: 800; color: #2563eb; margin-top: 10px; }',
        '</style></head>',
        '<body>',
        '<div class="lbl-name">'  + product.name  + '</div>',
        '<div class="lbl-sku">'   + product.sku   + '</div>',
        svgContent,
        '<div class="lbl-price">&#8369;' + product.price + '</div>',
        '</body></html>',
    ].join('');
    w.document.write(html);
    w.document.close();
    setTimeout(function () { w.print(); }, 400);
};
</script>
@endscript

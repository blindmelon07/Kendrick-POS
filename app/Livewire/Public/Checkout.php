<?php

namespace App\Livewire\Public;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\PayMongoService;
use Livewire\Attributes\Rule;
use Livewire\Component;

class Checkout extends Component
{
    #[Rule('required|string|max:255')]
    public string $client_name = '';

    #[Rule('required|email|max:255')]
    public string $client_email = '';

    #[Rule('required|string|max:30')]
    public string $client_phone = '';

    #[Rule('required|string|max:500')]
    public string $delivery_address = '';

    #[Rule('nullable|date|after_or_equal:today')]
    public ?string $delivery_date = null;

    #[Rule('nullable|string|max:500')]
    public string $delivery_notes = '';

    #[Rule('required|in:on_delivery,cash,gcash,paymaya,credit_card')]
    public string $payment_method = 'on_delivery';

    public bool $orderPlaced = false;
    public string $orderReference = '';
    public ?string $paymongoError = null;

    /** Payment methods that go through PayMongo */
    public const ONLINE_METHODS = ['gcash', 'paymaya', 'credit_card'];

    public function mount(): void
    {
        if (CartService::isEmpty()) {
            $this->redirect(route('public.cart'), navigate: true);
            return;
        }

        $this->client_name  = auth()->user()->name;
        $this->client_email = auth()->user()->email;
    }

    public function placeOrder(): void
    {
        $this->paymongoError = null;
        $this->validate();

        $cartItems = CartService::get();
        if (empty($cartItems)) {
            $this->redirect(route('public.cart'), navigate: true);
            return;
        }

        $subtotal = CartService::subtotal();

        $order = Order::create([
            'reference_no'     => Order::generateReferenceNo(),
            'client_name'      => $this->client_name,
            'client_email'     => $this->client_email,
            'client_phone'     => $this->client_phone,
            'delivery_address' => $this->delivery_address,
            'delivery_date'    => $this->delivery_date ?: null,
            'delivery_notes'   => $this->delivery_notes ?: null,
            'payment_method'   => $this->payment_method,
            'payment_status'   => 'unpaid',
            'subtotal'         => $subtotal,
            'discount_amount'  => 0,
            'tax_amount'       => 0,
            'delivery_fee'     => 0,
            'total'            => $subtotal,
            'amount_paid'      => 0,
            'status'           => 'pending',
            'created_by'       => auth()->id(),
        ]);

        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);
            OrderItem::create([
                'order_id'        => $order->id,
                'product_id'      => $item['product_id'],
                'product_name'    => $item['name'],
                'sku'             => $product?->sku ?? '',
                'unit'            => $product?->unit?->name ?? 'pc',
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['price'],
                'discount_amount' => 0,
                'subtotal'        => $item['price'] * $item['quantity'],
            ]);
        }

        CartService::clear();

        // Online payment → redirect to PayMongo checkout
        if (in_array($this->payment_method, self::ONLINE_METHODS)) {
            $paymongo   = app(PayMongoService::class);
            $checkoutUrl = $paymongo->createCheckoutSession($order);

            if ($checkoutUrl) {
                $this->redirect($checkoutUrl);
                return;
            }

            // PayMongo failed — keep order, show error, let customer retry or pay COD
            $this->paymongoError = 'We could not connect to the payment gateway. Your order was saved — please contact us to arrange payment, or choose Cash on Delivery.';
            return;
        }

        // Cash / COD → show inline success
        $this->orderReference = $order->reference_no;
        $this->orderPlaced    = true;
    }

    public function render()
    {
        $cartItems = CartService::get();
        $subtotal  = CartService::subtotal();

        return view('livewire.public.checkout', compact('cartItems', 'subtotal'))
            ->layout('layouts.public');
    }
}

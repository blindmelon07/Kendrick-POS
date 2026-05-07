<?php

use App\Http\Controllers\PayMongoWebhookController;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\PaymentResult;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// PayMongoService
// ---------------------------------------------------------------------------
describe('PayMongoService', function () {
    it('creates a checkout session and returns the checkout URL', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 120.00, 'stock_quantity' => 10]);

        $order = Order::create([
            'reference_no'    => 'ORD-TEST-0001',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'gcash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 120,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 120,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $user->id,
        ]);

        OrderItem::create([
            'order_id'        => $order->id,
            'product_id'      => $product->id,
            'product_name'    => 'Tapsilog',
            'sku'             => 'FOOD-001',
            'unit'            => 'serving',
            'quantity'        => 1,
            'unit_price'      => 120,
            'discount_amount' => 0,
            'subtotal'        => 120,
        ]);

        Http::fake([
            'api.paymongo.com/*' => Http::response([
                'data' => [
                    'id'         => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.com/cs_test_123',
                    ],
                ],
            ], 200),
        ]);

        $service = new PayMongoService();
        $url     = $service->createCheckoutSession($order);

        expect($url)->toBe('https://checkout.paymongo.com/cs_test_123');
        expect($order->fresh()->paymongo_checkout_id)->toBe('cs_test_123');
    });

    it('returns null when PayMongo API call fails', function () {
        $user  = User::factory()->create();
        $order = Order::create([
            'reference_no'    => 'ORD-TEST-0002',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'gcash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 50,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 50,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $user->id,
        ]);

        Http::fake([
            'api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'Unauthorized']]], 401),
        ]);

        $service = new PayMongoService();
        $url     = $service->createCheckoutSession($order);

        expect($url)->toBeNull();
    });

    it('verifies a valid webhook signature', function () {
        $secret = 'test_webhook_secret';
        config(['paymongo.webhook_secret' => $secret]);

        $body      = '{"data":{"type":"checkout_session.payment.paid"}}';
        $timestamp = '1614556800';
        $hash      = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
        $header    = "t={$timestamp},te={$hash},li=somehash";

        $service = new PayMongoService();
        expect($service->verifyWebhookSignature($body, $header))->toBeTrue();
    });

    it('rejects an invalid webhook signature', function () {
        config(['paymongo.webhook_secret' => 'real_secret']);

        $body   = '{"data":{"type":"checkout_session.payment.paid"}}';
        $header = 't=1614556800,te=invalidsignature,li=invalidsignature';

        $service = new PayMongoService();
        expect($service->verifyWebhookSignature($body, $header))->toBeFalse();
    });

    it('skips signature verification when webhook secret is empty', function () {
        config(['paymongo.webhook_secret' => '']);

        $service = new PayMongoService();
        expect($service->verifyWebhookSignature('body', 'bad_header'))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Webhook controller
// ---------------------------------------------------------------------------
describe('PayMongo webhook', function () {
    it('returns 200 for a valid payment paid event', function () {
        config(['paymongo.webhook_secret' => '']);

        $user  = User::factory()->create();
        $order = Order::create([
            'reference_no'         => 'ORD-WEBHOOK-0001',
            'client_name'          => $user->name,
            'client_email'         => $user->email,
            'client_phone'         => '09123456789',
            'delivery_address'     => '123 Test St',
            'payment_method'       => 'gcash',
            'payment_status'       => 'unpaid',
            'subtotal'             => 200,
            'discount_amount'      => 0,
            'tax_amount'           => 0,
            'delivery_fee'         => 0,
            'total'                => 200,
            'amount_paid'          => 0,
            'status'               => 'pending',
            'created_by'           => $user->id,
            'paymongo_checkout_id' => 'cs_test_abc123',
        ]);

        $payload = [
            'data' => [
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'data' => [
                        'id'         => 'cs_test_abc123',
                        'attributes' => [
                            'payments' => [
                                [
                                    'id'         => 'pay_xyz789',
                                    'attributes' => ['amount' => 20000],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('paymongo.webhook'), $payload)
            ->assertStatus(200);

        $order->refresh();
        expect($order->payment_status)->toBe('paid')
            ->and($order->status)->toBe('confirmed')
            ->and($order->paymongo_payment_id)->toBe('pay_xyz789')
            ->and((float) $order->amount_paid)->toBe(200.0);
    });

    it('returns 200 for unknown event types (ignored gracefully)', function () {
        config(['paymongo.webhook_secret' => '']);

        $this->postJson(route('paymongo.webhook'), [
            'data' => ['attributes' => ['type' => 'some.unknown.event', 'data' => []]],
        ])->assertStatus(200);
    });

    it('returns 401 for invalid signature', function () {
        config(['paymongo.webhook_secret' => 'real_secret']);

        $this->postJson(route('paymongo.webhook'), [], ['paymongo-signature' => 'bad'])
            ->assertStatus(401);
    });
});

// ---------------------------------------------------------------------------
// Checkout — PayMongo redirect flow
// ---------------------------------------------------------------------------
describe('Checkout PayMongo flow', function () {
    beforeEach(function () {
        CartService::clear();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
        $this->product  = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);
    });

    it('redirects to PayMongo for GCash payment', function () {
        CartService::add($this->product);

        Http::fake([
            'api.paymongo.com/*' => Http::response([
                'data' => [
                    'id'         => 'cs_gcash_001',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_gcash_001'],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->customer)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'gcash')
            ->call('placeOrder')
            ->assertRedirect('https://checkout.paymongo.com/cs_gcash_001');

        expect(Order::where('payment_method', 'gcash')->exists())->toBeTrue();
    });

    it('redirects to PayMongo for Maya payment', function () {
        CartService::add($this->product);

        Http::fake([
            'api.paymongo.com/*' => Http::response([
                'data' => [
                    'id'         => 'cs_maya_001',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_maya_001'],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->customer)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'paymaya')
            ->call('placeOrder')
            ->assertRedirect('https://checkout.paymongo.com/cs_maya_001');
    });

    it('shows inline success for cash on delivery without PayMongo', function () {
        Http::fake(); // Should NOT be called
        CartService::add($this->product);

        Livewire::actingAs($this->customer)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'on_delivery')
            ->call('placeOrder')
            ->assertSet('orderPlaced', true)
            ->assertSee('Order Placed!');

        Http::assertNothingSent();
    });

    it('shows error message when PayMongo API is unavailable', function () {
        CartService::add($this->product);

        Http::fake([
            'api.paymongo.com/*' => Http::response([], 500),
        ]);

        Livewire::actingAs($this->customer)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'gcash')
            ->call('placeOrder')
            ->assertSet('paymongoError', fn ($v) => str_contains($v, 'payment gateway'))
            ->assertSet('orderPlaced', false);

        // Order should still be saved so it isn't lost
        expect(Order::where('payment_method', 'gcash')->exists())->toBeTrue();
    });

    it('clears the cart even when redirecting to PayMongo', function () {
        CartService::add($this->product);

        Http::fake([
            'api.paymongo.com/*' => Http::response([
                'data' => ['id' => 'cs_001', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_001']],
            ], 200),
        ]);

        Livewire::actingAs($this->customer)
            ->test(Checkout::class)
            ->set('client_name', 'Test')->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')->set('delivery_address', '123 St')
            ->set('payment_method', 'gcash')
            ->call('placeOrder');

        expect(CartService::isEmpty())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Payment result page
// ---------------------------------------------------------------------------
describe('Payment result page', function () {
    it('shows success state when order is paid', function () {
        $user = User::factory()->create();
        $order = Order::create([
            'reference_no'    => 'ORD-PAID-0001',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'gcash',
            'payment_status'  => 'paid',
            'subtotal'        => 100,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 100,
            'amount_paid'     => 100,
            'status'          => 'confirmed',
            'created_by'      => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(PaymentResult::class, ['orderId' => $order->id])
            ->assertSet('isPaid', true)
            ->assertSee('Payment Successful');
    });

    it('shows pending state when payment not yet confirmed', function () {
        $user = User::factory()->create();
        $order = Order::create([
            'reference_no'    => 'ORD-PENDING-0001',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'gcash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 100,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 100,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(PaymentResult::class, ['orderId' => $order->id])
            ->assertSet('isPaid', false)
            ->assertSee('Processing Payment');
    });

    it('redirects when order does not belong to the user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order = Order::create([
            'reference_no'    => 'ORD-OTHER-0001',
            'client_name'     => $user2->name,
            'client_email'    => $user2->email,
            'client_phone'    => '09999999999',
            'delivery_address' => 'Other address',
            'payment_method'  => 'gcash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 50,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 50,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $user2->id,
        ]);

        Livewire::actingAs($user1)
            ->test(PaymentResult::class, ['orderId' => $order->id])
            ->assertRedirect(route('public.my-orders'));
    });
});

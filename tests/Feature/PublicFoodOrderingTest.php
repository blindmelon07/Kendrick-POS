<?php

use App\Livewire\Public\Cart;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\Home;
use App\Livewire\Public\Menu;
use App\Livewire\Public\MyOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// CartService
// ---------------------------------------------------------------------------
describe('CartService', function () {
    beforeEach(fn () => CartService::clear());

    it('starts empty', function () {
        expect(CartService::isEmpty())->toBeTrue()
            ->and(CartService::count())->toBe(0)
            ->and(CartService::subtotal())->toBe(0.0);
    });

    it('adds a product', function () {
        $product = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);

        CartService::add($product);

        expect(CartService::count())->toBe(1)
            ->and(CartService::get())->toHaveKey($product->id);
    });

    it('increments quantity when same product added again', function () {
        $product = Product::factory()->create(['selling_price' => 50.00, 'stock_quantity' => 10]);

        CartService::add($product);
        CartService::add($product);

        expect(CartService::count())->toBe(2)
            ->and(CartService::get()[$product->id]['quantity'])->toBe(2);
    });

    it('respects custom quantity on add', function () {
        $product = Product::factory()->create(['selling_price' => 20.00, 'stock_quantity' => 20]);

        CartService::add($product, 5);

        expect(CartService::count())->toBe(5);
    });

    it('calculates subtotal across multiple products', function () {
        $p1 = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);
        $p2 = Product::factory()->create(['selling_price' => 50.00, 'stock_quantity' => 10]);

        CartService::add($p1, 2); // 200
        CartService::add($p2, 3); // 150

        expect(CartService::subtotal())->toBe(350.0);
    });

    it('updates quantity of an existing item', function () {
        $product = Product::factory()->create(['selling_price' => 50.00, 'stock_quantity' => 10]);

        CartService::add($product, 2);
        CartService::update($product->id, 5);

        expect(CartService::get()[$product->id]['quantity'])->toBe(5);
    });

    it('removes item when quantity is updated to zero', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        CartService::add($product);
        CartService::update($product->id, 0);

        expect(CartService::isEmpty())->toBeTrue();
    });

    it('removes a specific item without touching others', function () {
        $p1 = Product::factory()->create(['stock_quantity' => 5]);
        $p2 = Product::factory()->create(['stock_quantity' => 5]);

        CartService::add($p1);
        CartService::add($p2);
        CartService::remove($p1->id);

        expect(CartService::get())->not->toHaveKey($p1->id)
            ->and(CartService::get())->toHaveKey($p2->id);
    });

    it('clears the entire cart', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        CartService::add($product, 3);
        CartService::clear();

        expect(CartService::isEmpty())->toBeTrue()
            ->and(CartService::count())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Public routes — HTTP access
// ---------------------------------------------------------------------------
describe('Public routes', function () {
    it('home page is accessible to guests', function () {
        $this->get(route('home'))->assertOk();
    });

    it('menu page is accessible to guests', function () {
        $this->get(route('public.menu'))->assertOk();
    });

    it('cart page is accessible to guests', function () {
        $this->get(route('public.cart'))->assertOk();
    });

    it('checkout redirects guests to login', function () {
        $this->get(route('public.checkout'))->assertRedirect(route('login'));
    });

    it('my-orders redirects guests to login', function () {
        $this->get(route('public.my-orders'))->assertRedirect(route('login'));
    });

    it('authenticated user can reach checkout when cart has items', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);
        CartService::add($product);

        $this->actingAs($user)
            ->get(route('public.checkout'))
            ->assertOk();

        CartService::clear();
    });

    it('authenticated user can reach my-orders', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('public.my-orders'))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// Home component
// ---------------------------------------------------------------------------
describe('Home page', function () {
    beforeEach(fn () => CartService::clear());

    it('renders without errors', function () {
        Livewire::test(Home::class)->assertOk();
    });

    it('shows active in-stock products in featured section', function () {
        $active   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $inactive = Product::factory()->create(['is_active' => false, 'stock_quantity' => 5]);
        $noStock  = Product::factory()->create(['is_active' => true, 'stock_quantity' => 0]);

        Livewire::test(Home::class)
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee($noStock->name);
    });

    it('shows active categories that have available products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->create([
            'category_id'    => $category->id,
            'is_active'      => true,
            'stock_quantity' => 5,
        ]);

        Livewire::test(Home::class)->assertSee($category->name);
    });

    it('addToCart puts the product into the session cart', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        Livewire::test(Home::class)
            ->call('addToCart', $product->id)
            ->assertHasNoErrors();

        expect(CartService::count())->toBe(1)
            ->and(CartService::get())->toHaveKey($product->id);
    });

    it('addToCart accumulates quantity when called multiple times', function () {
        $product   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);
        $component = Livewire::test(Home::class);

        $component->call('addToCart', $product->id);
        $component->call('addToCart', $product->id);

        expect(CartService::get()[$product->id]['quantity'])->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// Menu component
// ---------------------------------------------------------------------------
describe('Menu page', function () {
    beforeEach(fn () => CartService::clear());

    it('renders without errors', function () {
        Livewire::test(Menu::class)->assertOk();
    });

    it('only shows active in-stock products', function () {
        $available = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $inactive  = Product::factory()->create(['is_active' => false, 'stock_quantity' => 5]);
        $noStock   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 0]);

        Livewire::test(Menu::class)
            ->assertSee($available->name)
            ->assertDontSee($inactive->name)
            ->assertDontSee($noStock->name);
    });

    it('filters products by category', function () {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $p1   = Product::factory()->create(['category_id' => $cat1->id, 'is_active' => true, 'stock_quantity' => 5]);
        $p2   = Product::factory()->create(['category_id' => $cat2->id, 'is_active' => true, 'stock_quantity' => 5]);

        Livewire::test(Menu::class)
            ->set('category', $cat1->id)
            ->assertSee($p1->name)
            ->assertDontSee($p2->name);
    });

    it('clears category filter when set to null', function () {
        $cat = Category::factory()->create();
        $p1  = Product::factory()->create(['category_id' => $cat->id, 'is_active' => true, 'stock_quantity' => 5]);
        $p2  = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        Livewire::test(Menu::class)
            ->set('category', $cat->id)
            ->set('category', null)
            ->assertSee($p1->name)
            ->assertSee($p2->name);
    });

    it('searches products by name', function () {
        $match  = Product::factory()->create(['name' => 'Crispy Chicken Burger', 'is_active' => true, 'stock_quantity' => 5]);
        $noMatch = Product::factory()->create(['name' => 'Vegetable Salad',       'is_active' => true, 'stock_quantity' => 5]);

        Livewire::test(Menu::class)
            ->set('search', 'Crispy')
            ->assertSee('Crispy Chicken Burger')
            ->assertDontSee('Vegetable Salad');
    });

    it('shows empty state when no products match the search', function () {
        Product::factory()->create(['name' => 'Pizza', 'is_active' => true, 'stock_quantity' => 5]);

        Livewire::test(Menu::class)
            ->set('search', 'xyznosuchfood')
            ->assertSee('No items found');
    });

    it('addToCart from menu works correctly', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 10]);

        Livewire::test(Menu::class)
            ->call('addToCart', $product->id)
            ->assertHasNoErrors();

        expect(CartService::count())->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// Cart component
// ---------------------------------------------------------------------------
describe('Cart page', function () {
    beforeEach(fn () => CartService::clear());

    it('shows empty-cart message when no items', function () {
        Livewire::test(Cart::class)->assertSee('Your cart is empty');
    });

    it('shows items that are in the cart', function () {
        $product = Product::factory()->create(['selling_price' => 150.00, 'stock_quantity' => 5]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 150.00, 'quantity' => 2]];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)
            ->assertSee($product->name)
            ->assertSee('₱150.00');
    });

    it('increments item quantity', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 50.00]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 50.00, 'quantity' => 1]];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->call('increment', $product->id);

        expect(CartService::get()[$product->id]['quantity'])->toBe(2);
    });

    it('decrements item quantity', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 50.00]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 50.00, 'quantity' => 3]];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->call('decrement', $product->id);

        expect(CartService::get()[$product->id]['quantity'])->toBe(2);
    });

    it('removes item from cart when decremented to zero', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'selling_price' => 50.00]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 50.00, 'quantity' => 1]];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->call('decrement', $product->id);

        expect(CartService::isEmpty())->toBeTrue();
    });

    it('removes a specific item without affecting others', function () {
        $p1   = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 10.00]);
        $p2   = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 20.00]);
        $cart = [
            $p1->id => ['product_id' => $p1->id, 'name' => $p1->name, 'price' => 10.00, 'quantity' => 1],
            $p2->id => ['product_id' => $p2->id, 'name' => $p2->name, 'price' => 20.00, 'quantity' => 1],
        ];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->call('remove', $p1->id);

        expect(CartService::get())->not->toHaveKey($p1->id)
            ->and(CartService::get())->toHaveKey($p2->id);
    });

    it('clears all items from cart', function () {
        $p1   = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 10.00]);
        $p2   = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 20.00]);
        $cart = [
            $p1->id => ['product_id' => $p1->id, 'name' => $p1->name, 'price' => 10.00, 'quantity' => 1],
            $p2->id => ['product_id' => $p2->id, 'name' => $p2->name, 'price' => 20.00, 'quantity' => 1],
        ];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->call('clear');

        expect(CartService::isEmpty())->toBeTrue();
    });

    it('shows login prompt for guests with items in cart', function () {
        $product = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 50.00]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 50.00, 'quantity' => 1]];

        $this->withSession(['cart' => $cart]);

        Livewire::test(Cart::class)->assertSee('Log in to Checkout');
    });

    it('shows checkout button for authenticated users with items', function () {
        $product = Product::factory()->create(['stock_quantity' => 5, 'selling_price' => 50.00]);
        $cart    = [$product->id => ['product_id' => $product->id, 'name' => $product->name, 'price' => 50.00, 'quantity' => 1]];

        $this->withSession(['cart' => $cart]);

        Livewire::actingAs(User::factory()->create())
            ->test(Cart::class)
            ->assertSee('Proceed to Checkout');
    });
});

// ---------------------------------------------------------------------------
// Checkout component
// ---------------------------------------------------------------------------
describe('Checkout page', function () {
    beforeEach(fn () => CartService::clear());

    it('redirects to cart when cart is empty', function () {
        Livewire::actingAs(User::factory()->create())
            ->test(Checkout::class)
            ->assertRedirect(route('public.cart'));
    });

    it('pre-fills name and email from the authenticated user', function () {
        $user    = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $product = Product::factory()->create(['stock_quantity' => 5]);
        CartService::add($product);

        $component = Livewire::actingAs($user)->test(Checkout::class);

        expect($component->get('client_name'))->toBe('Jane Doe')
            ->and($component->get('client_email'))->toBe('jane@example.com');
    });

    it('creates an order with the correct total', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 200.00, 'stock_quantity' => 10]);
        CartService::add($product, 2); // 400

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St, Manila')
            ->set('payment_method', 'cash')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::first();
        expect($order)->not->toBeNull()
            ->and((float) $order->total)->toBe(400.0)
            ->and($order->status)->toBe('pending')
            ->and($order->created_by)->toBe($user->id);
    });

    it('creates the correct order items', function () {
        $user = User::factory()->create();
        $p1   = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);
        $p2   = Product::factory()->create(['selling_price' => 50.00, 'stock_quantity' => 10]);
        CartService::add($p1, 2);
        CartService::add($p2, 3);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'gcash')
            ->call('placeOrder');

        expect(OrderItem::count())->toBe(2)
            ->and((float) OrderItem::where('product_id', $p1->id)->value('quantity'))->toBe(2.0)
            ->and((float) OrderItem::where('product_id', $p2->id)->value('quantity'))->toBe(3.0);
    });

    it('clears the cart after a successful order', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);
        CartService::add($product);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'on_delivery')
            ->call('placeOrder');

        expect(CartService::isEmpty())->toBeTrue();
    });

    it('shows success confirmation after placing order', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 100.00, 'stock_quantity' => 10]);
        CartService::add($product);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test User')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 Test St')
            ->set('payment_method', 'on_delivery')
            ->call('placeOrder')
            ->assertSee('Order Placed!')
            ->assertSee('ORD-');
    });

    it('validates required fields', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);
        CartService::add($product);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', '')
            ->set('client_phone', '')
            ->set('delivery_address', '')
            ->call('placeOrder')
            ->assertHasErrors(['client_name', 'client_phone', 'delivery_address']);
    });

    it('validates email format', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);
        CartService::add($product);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test')
            ->set('client_email', 'not-an-email')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 St')
            ->set('payment_method', 'cash')
            ->call('placeOrder')
            ->assertHasErrors(['client_email']);
    });

    it('rejects invalid payment methods', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);
        CartService::add($product);

        Livewire::actingAs($user)
            ->test(Checkout::class)
            ->set('client_name', 'Test')
            ->set('client_email', 'test@example.com')
            ->set('client_phone', '09123456789')
            ->set('delivery_address', '123 St')
            ->set('payment_method', 'bitcoin')
            ->call('placeOrder')
            ->assertHasErrors(['payment_method']);
    });

    it('generates unique reference numbers for sequential orders', function () {
        $user = User::factory()->create();

        $p1 = Product::factory()->create(['selling_price' => 50.00, 'stock_quantity' => 10]);
        CartService::add($p1);
        Livewire::actingAs($user)->test(Checkout::class)
            ->set('client_name', 'A')->set('client_email', 'a@x.com')
            ->set('client_phone', '09111111111')->set('delivery_address', 'Addr A')
            ->set('payment_method', 'cash')->call('placeOrder');

        CartService::clear();

        $p2 = Product::factory()->create(['selling_price' => 75.00, 'stock_quantity' => 10]);
        CartService::add($p2);
        Livewire::actingAs($user)->test(Checkout::class)
            ->set('client_name', 'B')->set('client_email', 'b@x.com')
            ->set('client_phone', '09222222222')->set('delivery_address', 'Addr B')
            ->set('payment_method', 'gcash')->call('placeOrder');

        expect(Order::count())->toBe(2)
            ->and(Order::pluck('reference_no')->unique()->count())->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// My Orders component
// ---------------------------------------------------------------------------
describe('My Orders page', function () {
    it('renders without errors for authenticated user', function () {
        Livewire::actingAs(User::factory()->create())
            ->test(MyOrders::class)
            ->assertOk();
    });

    it('shows empty state when user has no orders', function () {
        Livewire::actingAs(User::factory()->create())
            ->test(MyOrders::class)
            ->assertSee('No orders yet');
    });

    it('shows orders created by the authenticated user', function () {
        $user = User::factory()->create();
        Order::create([
            'reference_no'    => 'ORD-TEST-0001',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'cash',
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
            ->test(MyOrders::class)
            ->assertSee('ORD-TEST-0001')
            ->assertSee('₱100.00');
    });

    it('does not show orders belonging to a different user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::create([
            'reference_no'    => 'ORD-OTHER-0001',
            'client_name'     => $user2->name,
            'client_email'    => $user2->email,
            'client_phone'    => '09999999999',
            'delivery_address' => 'Other address',
            'payment_method'  => 'cash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 200,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 200,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $user2->id,
        ]);

        Livewire::actingAs($user1)
            ->test(MyOrders::class)
            ->assertDontSee('ORD-OTHER-0001');
    });

    it('displays the correct status label', function () {
        $user = User::factory()->create();
        Order::create([
            'reference_no'    => 'ORD-STAT-0001',
            'client_name'     => $user->name,
            'client_email'    => $user->email,
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'gcash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 150,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 150,
            'amount_paid'     => 0,
            'status'          => 'confirmed',
            'created_by'      => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(MyOrders::class)
            ->assertSee('Confirmed');
    });

    it('shows orders matched by client email regardless of created_by', function () {
        $user  = User::factory()->create(['email' => 'customer@example.com']);
        $admin = User::factory()->create();

        Order::create([
            'reference_no'    => 'ORD-EMAIL-0001',
            'client_name'     => 'Customer',
            'client_email'    => 'customer@example.com',
            'client_phone'    => '09123456789',
            'delivery_address' => '123 Test St',
            'payment_method'  => 'cash',
            'payment_status'  => 'unpaid',
            'subtotal'        => 300,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'delivery_fee'    => 0,
            'total'           => 300,
            'amount_paid'     => 0,
            'status'          => 'pending',
            'created_by'      => $admin->id,
        ]);

        Livewire::actingAs($user)
            ->test(MyOrders::class)
            ->assertSee('ORD-EMAIL-0001');
    });
});

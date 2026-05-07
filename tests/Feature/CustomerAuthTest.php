<?php

use App\Livewire\Auth\CustomerLogin;
use App\Livewire\Auth\CustomerRegister;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// Customer Login page
// ---------------------------------------------------------------------------
describe('Customer Login page', function () {
    it('is accessible to guests', function () {
        $this->get(route('customer.login'))->assertOk();
    });

    it('redirects authenticated users away', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('customer.login'))
            ->assertRedirect();
    });

    it('renders the login form', function () {
        Livewire::test(CustomerLogin::class)
            ->assertSee('Sign In')
            ->assertSee('Email Address')
            ->assertSee('Password');
    });

    it('shows link to customer register page', function () {
        Livewire::test(CustomerLogin::class)
            ->assertSee('Create one');
    });

    it('shows administrator login link', function () {
        Livewire::test(CustomerLogin::class)
            ->assertSee('Administrator login');
    });

    it('logs in a valid customer and redirects to my-orders', function () {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('customer');

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('public.my-orders'));
    });

    it('redirects non-customer users to dashboard after login', function () {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('admin');

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    });

    it('rejects invalid credentials', function () {
        User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('correct')]);

        $this->post(route('login.store'), [
            'email'    => 'test@example.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');
    });
});

// ---------------------------------------------------------------------------
// Customer Register page
// ---------------------------------------------------------------------------
describe('Customer Register page', function () {
    it('is accessible to guests', function () {
        $this->get(route('customer.register'))->assertOk();
    });

    it('redirects authenticated users away', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('customer.register'))
            ->assertRedirect();
    });

    it('renders the registration form', function () {
        Livewire::test(CustomerRegister::class)
            ->assertSee('Create an Account')
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Password');
    });

    it('shows link to customer login page', function () {
        Livewire::test(CustomerRegister::class)
            ->assertSee('Sign in');
    });

    it('creates a user with the customer role', function () {
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Maria Santos')
            ->set('email', 'maria@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'maria@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('customer'))->toBeTrue();
    });

    it('logs in the user after registration', function () {
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Jose Rizal')
            ->set('email', 'jose@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $this->assertAuthenticated();
    });

    it('redirects to my-orders after registration', function () {
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Ana Cruz')
            ->set('email', 'ana@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('public.my-orders'));
    });

    it('validates required fields', function () {
        Livewire::test(CustomerRegister::class)
            ->call('register')
            ->assertHasErrors(['name', 'email', 'password']);
    });

    it('validates email is unique', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(CustomerRegister::class)
            ->set('name', 'Test')
            ->set('email', 'taken@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    });

    it('validates password confirmation matches', function () {
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Test User')
            ->set('email', 'new@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different999')
            ->call('register')
            ->assertHasErrors(['password']);
    });

    it('validates minimum password length', function () {
        Livewire::test(CustomerRegister::class)
            ->set('name', 'Test User')
            ->set('email', 'short@example.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors(['password']);
    });

    it('creates the customer role if it does not exist yet', function () {
        Role::where('name', 'customer')->delete();

        Livewire::test(CustomerRegister::class)
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        expect(Role::where('name', 'customer')->exists())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Admin login alias
// ---------------------------------------------------------------------------
describe('Admin login alias', function () {
    it('administrator/login redirects to /login', function () {
        $this->get(route('admin.login'))
            ->assertRedirect('/login');
    });
});

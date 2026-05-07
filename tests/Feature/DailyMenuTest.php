<?php

use App\Livewire\DailyMenu\Management;
use App\Livewire\Public\Home;
use App\Models\Category;
use App\Models\DailyMenu;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// DailyMenu model
// ---------------------------------------------------------------------------
describe('DailyMenu model', function () {
    it('returns todays featured active in-stock products', function () {
        $active   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $inactive = Product::factory()->create(['is_active' => false, 'stock_quantity' => 5]);
        $noStock  = Product::factory()->create(['is_active' => true, 'stock_quantity' => 0]);

        DailyMenu::factory()->today()->create(['product_id' => $active->id,   'sort_order' => 0]);
        DailyMenu::factory()->today()->create(['product_id' => $inactive->id, 'sort_order' => 1]);
        DailyMenu::factory()->today()->create(['product_id' => $noStock->id,  'sort_order' => 2]);

        $featured = DailyMenu::todaysFeatured();

        expect($featured)->toHaveCount(1)
            ->and($featured->first()->id)->toBe($active->id);
    });

    it('returns empty collection when no daily menu is set for today', function () {
        expect(DailyMenu::todaysFeatured())->toBeEmpty();
    });

    it('returns products ordered by sort_order', function () {
        $p1 = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $p2 = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $p3 = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        DailyMenu::factory()->today()->create(['product_id' => $p1->id, 'sort_order' => 2]);
        DailyMenu::factory()->today()->create(['product_id' => $p2->id, 'sort_order' => 0]);
        DailyMenu::factory()->today()->create(['product_id' => $p3->id, 'sort_order' => 1]);

        $ids = DailyMenu::todaysFeatured()->pluck('id');

        expect($ids->toArray())->toBe([$p2->id, $p3->id, $p1->id]);
    });

    it('does not return products featured on other dates', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        DailyMenu::factory()->forDate(today()->subDay()->toDateString())
            ->create(['product_id' => $product->id]);

        expect(DailyMenu::todaysFeatured())->toBeEmpty();
    });

    it('forDate returns entries for a specific date', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $date    = '2026-01-15';

        DailyMenu::factory()->forDate($date)->create(['product_id' => $product->id]);

        $entries = DailyMenu::forDate($date);
        expect($entries)->toHaveCount(1)
            ->and($entries->first()->product_id)->toBe($product->id);
    });

    it('enforces unique product per date constraint', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        expect(fn () => DailyMenu::factory()->today()->create(['product_id' => $product->id]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });
});

// ---------------------------------------------------------------------------
// Home page — daily menu integration
// ---------------------------------------------------------------------------
describe('Home page daily menu', function () {
    it('shows todays featured dishes when daily menu is set', function () {
        $product = Product::factory()->create([
            'name'           => 'Special Adobo',
            'is_active'      => true,
            'stock_quantity' => 10,
        ]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::test(Home::class)
            ->assertSee('Special Adobo')
            ->assertSee("Today's Featured Dishes", false)
            ->assertSee("Today's Menu", false);
    });

    it('shows todays menu date label when daily menu is active', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::test(Home::class)->assertSee(now()->format('F j, Y'));
    });

    it('falls back to latest products when no daily menu is set', function () {
        $product = Product::factory()->create([
            'name'           => 'Regular Sinigang',
            'is_active'      => true,
            'stock_quantity' => 5,
        ]);

        Livewire::test(Home::class)
            ->assertSee('Regular Sinigang')
            ->assertSee('Featured Items')
            ->assertDontSee("Today's Featured Dishes", false);
    });

    it('does not show inactive products even if in daily menu', function () {
        $product = Product::factory()->create(['is_active' => false, 'stock_quantity' => 5]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        // with no active featured, falls back — product name should not appear
        $active = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        Livewire::test(Home::class)->assertDontSee($product->name);
    });
});

// ---------------------------------------------------------------------------
// Daily Menu admin management component
// ---------------------------------------------------------------------------
describe('Daily Menu management', function () {
    beforeEach(function () {
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    });

    it('is accessible to managers', function () {
        $this->actingAs($this->manager)
            ->get(route('daily-menu.index'))
            ->assertOk();
    });

    it('is inaccessible to guests', function () {
        $this->get(route('daily-menu.index'))->assertRedirect(route('login'));
    });

    it('is inaccessible to customers', function () {
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('daily-menu.index'))
            ->assertForbidden();
    });

    it('renders with todays date by default', function () {
        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->assertSet('selectedDate', today()->toDateString());
    });

    it('shows empty state when no dishes are featured', function () {
        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->assertSee('No featured dishes for this date');
    });

    it('shows featured dishes for the selected date', function () {
        $product = Product::factory()->create(['name' => 'Kare-Kare', 'is_active' => true, 'stock_quantity' => 5]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->assertSee('Kare-Kare');
    });

    it('can add a product to the daily menu', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->set('addProductId', $product->id)
            ->set('addSortOrder', 0)
            ->call('addToMenu')
            ->assertHasNoErrors();

        expect(DailyMenu::where('product_id', $product->id)
            ->whereDate('featured_date', today()->toDateString())
            ->exists())->toBeTrue();
    });

    it('prevents adding the same product twice to the same date', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->set('addProductId', $product->id)
            ->set('addSortOrder', 0)
            ->call('addToMenu');

        expect(DailyMenu::where('product_id', $product->id)
            ->whereDate('featured_date', today()->toDateString())
            ->count())->toBe(1);
    });

    it('validates that a product must be selected before adding', function () {
        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->set('addProductId', null)
            ->call('addToMenu')
            ->assertHasErrors(['addProductId']);
    });

    it('can remove a dish from the daily menu', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $entry   = DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->call('remove', $entry->id);

        expect(DailyMenu::find($entry->id))->toBeNull();
    });

    it('can update the sort order of a dish', function () {
        $product = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $entry   = DailyMenu::factory()->today()->create(['product_id' => $product->id, 'sort_order' => 0]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->call('updateOrder', $entry->id, 5);

        expect($entry->fresh()->sort_order)->toBe(5);
    });

    it('can clear all dishes from the selected date', function () {
        $p1 = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $p2 = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        DailyMenu::factory()->today()->create(['product_id' => $p1->id]);
        DailyMenu::factory()->today()->create(['product_id' => $p2->id]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->call('clearDate');

        expect(DailyMenu::where('featured_date', today()->toDateString())->count())->toBe(0);
    });

    it('can copy the menu from yesterday', function () {
        $product   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $yesterday = today()->subDay()->toDateString();
        DailyMenu::factory()->forDate($yesterday)->create(['product_id' => $product->id, 'sort_order' => 2]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->call('copyFromDate', $yesterday);

        expect(DailyMenu::where('product_id', $product->id)
            ->whereDate('featured_date', today()->toDateString())
            ->exists())->toBeTrue();
    });

    it('does not duplicate when copying from a date that overlaps', function () {
        $product   = Product::factory()->create(['is_active' => true, 'stock_quantity' => 5]);
        $yesterday = today()->subDay()->toDateString();

        DailyMenu::factory()->forDate($yesterday)->create(['product_id' => $product->id]);
        DailyMenu::factory()->today()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->manager)
            ->test(Management::class)
            ->call('copyFromDate', $yesterday);

        expect(DailyMenu::where('product_id', $product->id)
            ->whereDate('featured_date', today()->toDateString())
            ->count())->toBe(1);
    });

    it('does not show already-featured products in the available list', function () {
        $menuCat     = Category::factory()->create(['is_active' => true, 'is_menu_item' => true]);
        $featured    = Product::factory()->create(['name' => 'Lechon',  'is_active' => true, 'stock_quantity' => 5, 'category_id' => $menuCat->id]);
        $notFeatured = Product::factory()->create(['name' => 'Pancit', 'is_active' => true, 'stock_quantity' => 5, 'category_id' => $menuCat->id]);
        DailyMenu::factory()->today()->create(['product_id' => $featured->id]);

        $component = Livewire::actingAs($this->manager)->test(Management::class);

        $available = $component->get('availableProducts');

        expect($available->pluck('id')->contains($featured->id))->toBeFalse()
            ->and($available->pluck('id')->contains($notFeatured->id))->toBeTrue();
    });
});

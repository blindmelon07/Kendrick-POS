<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class FoodMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a "serving" unit exists
        $serving = Unit::firstOrCreate(
            ['name' => 'Serving'],
            ['abbreviation' => 'srv'],
        );

        $categories = [
            'Silog Meals' => [
                ['name' => 'Tapsilog',      'description' => 'Beef tapa, garlic rice, and sunny-side-up egg.',            'price' => 120],
                ['name' => 'Longsilog',     'description' => 'Pork longganisa, garlic rice, and sunny-side-up egg.',       'price' => 110],
                ['name' => 'Tocilog',       'description' => 'Sweet pork tocino, garlic rice, and sunny-side-up egg.',     'price' => 110],
                ['name' => 'Bangsilog',     'description' => 'Fried bangus, garlic rice, and sunny-side-up egg.',          'price' => 130],
                ['name' => 'Hotsilog',      'description' => 'Hotdog, garlic rice, and sunny-side-up egg.',                'price' => 90],
                ['name' => 'Spamsilog',     'description' => 'SPAM slices, garlic rice, and sunny-side-up egg.',           'price' => 140],
                ['name' => 'Cornsilog',     'description' => 'Corned beef, garlic rice, and sunny-side-up egg.',           'price' => 100],
            ],
            'Main Dishes' => [
                ['name' => 'Adobong Manok',   'description' => 'Classic chicken braised in vinegar, soy sauce, and garlic.', 'price' => 95],
                ['name' => 'Adobong Baboy',   'description' => 'Classic pork belly braised in vinegar, soy sauce, and garlic.', 'price' => 100],
                ['name' => 'Sinigang na Baboy', 'description' => 'Pork ribs in sour tamarind broth with vegetables.',      'price' => 110],
                ['name' => 'Sinigang na Hipon', 'description' => 'Shrimp in sour tamarind broth with vegetables.',         'price' => 130],
                ['name' => 'Kare-Kare',       'description' => 'Oxtail and vegetables in rich peanut sauce with bagoong.', 'price' => 150],
                ['name' => 'Lechon Kawali',   'description' => 'Crispy deep-fried pork belly served with liver sauce.',    'price' => 140],
                ['name' => 'Bistek Tagalog',  'description' => 'Tender beef slices in soy sauce and calamansi with onion rings.', 'price' => 125],
                ['name' => 'Pinakbet',        'description' => 'Mixed vegetables sautéed in bagoong and pork.',            'price' => 85],
                ['name' => 'Dinuguan',        'description' => 'Savory pork blood stew with pork meat.',                   'price' => 95],
                ['name' => 'Caldereta',       'description' => 'Tender beef or goat in rich tomato and liver sauce.',      'price' => 140],
            ],
            'Soups & Stews' => [
                ['name' => 'Bulalo',          'description' => 'Slow-cooked beef shank and marrow bone soup.',             'price' => 180],
                ['name' => 'Nilaga',          'description' => 'Boiled pork or beef with potatoes and cabbage.',           'price' => 110],
                ['name' => 'Arroz Caldo',     'description' => 'Thick rice porridge with chicken, ginger, and toppings.', 'price' => 80],
                ['name' => 'Goto',            'description' => 'Rice porridge with beef tripe and ginger.',               'price' => 85],
                ['name' => 'Chicken Tinola',  'description' => 'Chicken in ginger broth with green papaya and malunggay.','price' => 100],
                ['name' => 'Monggo Guisado',  'description' => 'Sautéed mung bean soup with pork and leafy greens.',      'price' => 80],
            ],
            'Snacks & Merienda' => [
                ['name' => 'Lugaw',           'description' => 'Plain rice porridge with toppings.',                      'price' => 50],
                ['name' => 'Palabok',         'description' => 'Rice noodles in shrimp sauce with various toppings.',     'price' => 90],
                ['name' => 'Pansit Bihon',    'description' => 'Stir-fried rice noodles with meat and vegetables.',       'price' => 85],
                ['name' => 'Pansit Canton',   'description' => 'Stir-fried egg noodles with meat and vegetables.',        'price' => 85],
                ['name' => 'Lumpiang Shanghai', 'description' => 'Crispy mini spring rolls filled with seasoned pork.',   'price' => 75],
                ['name' => 'Tokwa\'t Baboy',  'description' => 'Fried tofu and pork ears in vinegar-soy sauce.',          'price' => 80],
            ],
            'Desserts' => [
                ['name' => 'Halo-Halo',       'description' => 'Mixed shaved ice dessert with milk, ube, and toppings.',  'price' => 75],
                ['name' => 'Leche Flan',      'description' => 'Creamy caramel custard.',                                 'price' => 60],
                ['name' => 'Biko',            'description' => 'Sticky sweet rice cake with coconut milk and latik.',     'price' => 50],
                ['name' => 'Puto',            'description' => 'Steamed rice cake, plain or with salted egg.',            'price' => 40],
                ['name' => 'Kutsinta',        'description' => 'Brown steamed rice cake topped with grated coconut.',     'price' => 35],
                ['name' => 'Maja Blanca',     'description' => 'Coconut milk pudding with corn kernels.',                 'price' => 45],
                ['name' => 'Sago\'t Gulaman', 'description' => 'Cold drink with sago pearls, gulaman, and brown sugar.',  'price' => 40],
            ],
            'Drinks' => [
                ['name' => 'Fresh Buko Juice',  'description' => 'Fresh young coconut water.',                            'price' => 50],
                ['name' => 'Calamansi Juice',   'description' => 'Fresh calamansi with sugar and water.',                 'price' => 40],
                ['name' => 'Iced Tea',          'description' => 'Cold brewed tea with ice.',                             'price' => 35],
                ['name' => 'Bottled Water',     'description' => '500ml purified drinking water.',                        'price' => 20],
                ['name' => 'Softdrinks',        'description' => 'Coke, Sprite, or Royal (330ml).',                       'price' => 35],
            ],
        ];

        foreach ($categories as $categoryName => $dishes) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['description' => null, 'is_active' => true, 'is_menu_item' => true],
            );

            // Ensure existing categories are also marked as menu items
            $category->update(['is_menu_item' => true]);

            foreach ($dishes as $dish) {
                Product::firstOrCreate(
                    ['name' => $dish['name']],
                    [
                        'sku'            => 'FOOD-' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $dish['name'])),
                        'description'    => $dish['description'],
                        'category_id'    => $category->id,
                        'unit_id'        => $serving->id,
                        'cost_price'     => round($dish['price'] * 0.55, 2),
                        'selling_price'  => $dish['price'],
                        'stock_quantity' => 50,
                        'reorder_level'  => 5,
                        'is_active'      => true,
                    ],
                );
            }
        }

        $this->command->info('Food menu seeded: ' . array_sum(array_map('count', $categories)) . ' dishes across ' . count($categories) . ' categories.');
    }
}

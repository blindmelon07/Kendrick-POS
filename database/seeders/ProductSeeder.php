<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // ── Units ─────────────────────────────────────────────────────────────
        $pcs  = Unit::firstOrCreate(['name' => 'Piece'],  ['abbreviation' => 'pcs']);
        $kg   = Unit::firstOrCreate(['name' => 'Kilogram'], ['abbreviation' => 'kg']);
        $g    = Unit::firstOrCreate(['name' => 'Gram'],   ['abbreviation' => 'g']);
        $ltr  = Unit::firstOrCreate(['name' => 'Liter'],  ['abbreviation' => 'L']);
        $pack = Unit::firstOrCreate(['name' => 'Pack'],   ['abbreviation' => 'pack']);
        $box  = Unit::firstOrCreate(['name' => 'Box'],    ['abbreviation' => 'box']);
        $doz  = Unit::firstOrCreate(['name' => 'Dozen'],  ['abbreviation' => 'doz']);

        // ── Categories ────────────────────────────────────────────────────────
        $beverages   = Category::firstOrCreate(['name' => 'Beverages'],   ['description' => 'Drinks and juices', 'is_active' => true]);
        $snacks      = Category::firstOrCreate(['name' => 'Snacks'],      ['description' => 'Chips, crackers, and biscuits', 'is_active' => true]);
        $dairy       = Category::firstOrCreate(['name' => 'Dairy'],       ['description' => 'Milk, cheese, eggs, and butter', 'is_active' => true]);
        $canned      = Category::firstOrCreate(['name' => 'Canned Goods'],['description' => 'Canned and preserved food', 'is_active' => true]);
        $condiments  = Category::firstOrCreate(['name' => 'Condiments'],  ['description' => 'Sauces, seasonings, and spreads', 'is_active' => true]);
        $grains      = Category::firstOrCreate(['name' => 'Grains & Rice'],['description' => 'Rice, noodles, and flour', 'is_active' => true]);
        $personal    = Category::firstOrCreate(['name' => 'Personal Care'],['description' => 'Soap, shampoo, and hygiene', 'is_active' => true]);
        $household   = Category::firstOrCreate(['name' => 'Household'],   ['description' => 'Cleaning and laundry products', 'is_active' => true]);
        $frozen      = Category::firstOrCreate(['name' => 'Frozen'],      ['description' => 'Frozen meat and ready-to-cook', 'is_active' => true]);


        // ── Products ──────────────────────────────────────────────────────────
        $products = [
            // Beverages
            ['sku' => 'BEV-001', 'name' => 'Coca-Cola 1.5L',          'category' => $beverages,  'unit' => $ltr,  'cost' => 55,  'sell' => 75,  'stock' => 120, 'reorder' => 20],
            ['sku' => 'BEV-002', 'name' => 'Royal Tru-Orange 1L',     'category' => $beverages,  'unit' => $ltr,  'cost' => 40,  'sell' => 58,  'stock' => 80,  'reorder' => 15],
            ['sku' => 'BEV-003', 'name' => 'Sprite 355ml Can',        'category' => $beverages,  'unit' => $pcs,  'cost' => 28,  'sell' => 40,  'stock' => 240, 'reorder' => 50],
            ['sku' => 'BEV-004', 'name' => 'Minute Maid Orange 250ml','category' => $beverages,  'unit' => $pcs,  'cost' => 20,  'sell' => 30,  'stock' => 150, 'reorder' => 30],
            ['sku' => 'BEV-005', 'name' => 'Nestea Iced Tea 1L',      'category' => $beverages,  'unit' => $ltr,  'cost' => 38,  'sell' => 52,  'stock' => 90,  'reorder' => 20],
            ['sku' => 'BEV-006', 'name' => 'C2 Apple 230ml',          'category' => $beverages,  'unit' => $pcs,  'cost' => 18,  'sell' => 28,  'stock' => 200, 'reorder' => 40],
            ['sku' => 'BEV-007', 'name' => 'Nescafe 3-in-1 Original', 'category' => $beverages,  'unit' => $pack, 'cost' => 8,   'sell' => 12,  'stock' => 500, 'reorder' => 100],
            ['sku' => 'BEV-008', 'name' => 'Milo 3-in-1 Sachet',     'category' => $beverages,  'unit' => $pack, 'cost' => 10,  'sell' => 15,  'stock' => 480, 'reorder' => 100],

            // Snacks
            ['sku' => 'SNK-001', 'name' => 'Piattos Cheese 85g',      'category' => $snacks,     'unit' => $pcs,  'cost' => 30,  'sell' => 42,  'stock' => 180, 'reorder' => 30],
            ['sku' => 'SNK-002', 'name' => 'Lays Classic 32g',        'category' => $snacks,     'unit' => $pcs,  'cost' => 25,  'sell' => 38,  'stock' => 200, 'reorder' => 40],
            ['sku' => 'SNK-003', 'name' => 'Skyflakes Crackers 250g', 'category' => $snacks,     'unit' => $pack, 'cost' => 28,  'sell' => 40,  'stock' => 120, 'reorder' => 25],
            ['sku' => 'SNK-004', 'name' => 'Rebisco Crackers 10s',    'category' => $snacks,     'unit' => $pack, 'cost' => 12,  'sell' => 18,  'stock' => 300, 'reorder' => 60],
            ['sku' => 'SNK-005', 'name' => 'Chiz Curls 60g',          'category' => $snacks,     'unit' => $pcs,  'cost' => 22,  'sell' => 32,  'stock' => 160, 'reorder' => 30],
            ['sku' => 'SNK-006', 'name' => 'Boy Bawang Cornick 100g', 'category' => $snacks,     'unit' => $pcs,  'cost' => 25,  'sell' => 36,  'stock' => 140, 'reorder' => 25],
            ['sku' => 'SNK-007', 'name' => 'Magic Crackers 33g',      'category' => $snacks,     'unit' => $pcs,  'cost' => 8,   'sell' => 13,  'stock' => 400, 'reorder' => 80],

            // Dairy
            ['sku' => 'DAI-001', 'name' => 'Bear Brand Adult Plus 1kg','category' => $dairy,     'unit' => $pcs,  'cost' => 280, 'sell' => 370, 'stock' => 50,  'reorder' => 10],
            ['sku' => 'DAI-002', 'name' => 'Alaska Evaporated Milk 370ml','category'=> $dairy,   'unit' => $pcs,  'cost' => 35,  'sell' => 48,  'stock' => 180, 'reorder' => 40],
            ['sku' => 'DAI-003', 'name' => 'Magnolia Full Cream Milk 1L','category'=> $dairy,    'unit' => $ltr,  'cost' => 70,  'sell' => 95,  'stock' => 100, 'reorder' => 20],
            ['sku' => 'DAI-004', 'name' => 'Nestle All Purpose Cream 250ml','category'=>$dairy,  'unit' => $pcs,  'cost' => 45,  'sell' => 62,  'stock' => 120, 'reorder' => 25],
            ['sku' => 'DAI-005', 'name' => 'Egg (per tray 30pcs)',    'category' => $dairy,      'unit' => $pcs,  'cost' => 180, 'sell' => 230, 'stock' => 60,  'reorder' => 15],

            // Canned Goods
            ['sku' => 'CAN-001', 'name' => 'CDO Liver Spread 165g',   'category' => $canned,    'unit' => $pcs,  'cost' => 35,  'sell' => 50,  'stock' => 200, 'reorder' => 40],
            ['sku' => 'CAN-002', 'name' => 'Ligo Sardines in Tomato Sauce 155g','category'=>$canned,'unit'=>$pcs, 'cost' => 22,  'sell' => 32,  'stock' => 350, 'reorder' => 60],
            ['sku' => 'CAN-003', 'name' => 'Argentina Corned Beef 260g','category'=> $canned,   'unit' => $pcs,  'cost' => 72,  'sell' => 98,  'stock' => 150, 'reorder' => 30],
            ['sku' => 'CAN-004', 'name' => 'Delimondo Corned Beef 380g','category'=> $canned,   'unit' => $pcs,  'cost' => 145, 'sell' => 195, 'stock' => 80,  'reorder' => 15],
            ['sku' => 'CAN-005', 'name' => 'Tuna Flakes in Oil 180g', 'category' => $canned,    'unit' => $pcs,  'cost' => 38,  'sell' => 55,  'stock' => 220, 'reorder' => 40],
            ['sku' => 'CAN-006', 'name' => 'San Marino Corned Tuna 150g','category'=>$canned,   'unit' => $pcs,  'cost' => 30,  'sell' => 44,  'stock' => 200, 'reorder' => 40],

            // Condiments
            ['sku' => 'CON-001', 'name' => 'Datu Puti Soy Sauce 1L',  'category' => $condiments,'unit' => $ltr,  'cost' => 50,  'sell' => 70,  'stock' => 100, 'reorder' => 20],
            ['sku' => 'CON-002', 'name' => 'Datu Puti Vinegar 1L',    'category' => $condiments,'unit' => $ltr,  'cost' => 42,  'sell' => 60,  'stock' => 100, 'reorder' => 20],
            ['sku' => 'CON-003', 'name' => 'Mang Tomas All-Purpose Sauce 550g','category'=>$condiments,'unit'=>$pcs,'cost'=>55,'sell'=>78,'stock'=>90,'reorder'=>15],
            ['sku' => 'CON-004', 'name' => 'UFC Banana Ketchup 320g', 'category' => $condiments,'unit' => $pcs,  'cost' => 48,  'sell' => 65,  'stock' => 120, 'reorder' => 20],
            ['sku' => 'CON-005', 'name' => 'Knorr Sinigang Mix 40g',  'category' => $condiments,'unit' => $pack, 'cost' => 14,  'sell' => 20,  'stock' => 400, 'reorder' => 80],
            ['sku' => 'CON-006', 'name' => 'Magic Sarap 8g Sachet',   'category' => $condiments,'unit' => $pack, 'cost' => 5,   'sell' => 8,   'stock' => 800, 'reorder' => 150],

            // Grains & Rice
            ['sku' => 'GRN-001', 'name' => 'Premium Rice 5kg',        'category' => $grains,    'unit' => $kg,   'cost' => 230, 'sell' => 290, 'stock' => 200, 'reorder' => 50],
            ['sku' => 'GRN-002', 'name' => 'Jasmine Rice 1kg',        'category' => $grains,    'unit' => $kg,   'cost' => 55,  'sell' => 72,  'stock' => 300, 'reorder' => 50],
            ['sku' => 'GRN-003', 'name' => 'Lucky Me Pancit Canton 60g','category'=> $grains,   'unit' => $pcs,  'cost' => 12,  'sell' => 18,  'stock' => 600, 'reorder' => 100],
            ['sku' => 'GRN-004', 'name' => 'Nissin Cup Noodles 40g',  'category' => $grains,    'unit' => $pcs,  'cost' => 18,  'sell' => 28,  'stock' => 400, 'reorder' => 80],
            ['sku' => 'GRN-005', 'name' => 'Eden Corned Beef Fried Rice Mix','category'=>$grains,'unit'=>$pack,  'cost' => 22,  'sell' => 32,  'stock' => 150, 'reorder' => 30],

            // Personal Care
            ['sku' => 'PER-001', 'name' => 'Safeguard Bar Soap 135g', 'category' => $personal,  'unit' => $pcs,  'cost' => 35,  'sell' => 50,  'stock' => 250, 'reorder' => 50],
            ['sku' => 'PER-002', 'name' => 'Head & Shoulders 180ml',  'category' => $personal,  'unit' => $pcs,  'cost' => 105, 'sell' => 145, 'stock' => 100, 'reorder' => 20],
            ['sku' => 'PER-003', 'name' => 'Colgate Total 150g',      'category' => $personal,  'unit' => $pcs,  'cost' => 75,  'sell' => 105, 'stock' => 150, 'reorder' => 30],
            ['sku' => 'PER-004', 'name' => 'Palmolive Shampoo Sachet 12ml','category'=>$personal,'unit'=>$pack,  'cost' => 5,   'sell' => 8,   'stock' => 1000,'reorder' => 200],
            ['sku' => 'PER-005', 'name' => 'Gillette Mach3 Razor',    'category' => $personal,  'unit' => $pcs,  'cost' => 85,  'sell' => 120, 'stock' => 60,  'reorder' => 10],
            ['sku' => 'PER-006', 'name' => 'Whisper Cottony Soft Regular','category'=>$personal,'unit'=>$pack,   'cost' => 48,  'sell' => 68,  'stock' => 120, 'reorder' => 25],

            // Household
            ['sku' => 'HHD-001', 'name' => 'Ariel Powder Detergent 2kg','category'=>$household, 'unit' => $pcs,  'cost' => 185, 'sell' => 250, 'stock' => 80,  'reorder' => 15],
            ['sku' => 'HHD-002', 'name' => 'Downy Fabric Conditioner 900ml','category'=>$household,'unit'=>$pcs, 'cost' => 90,  'sell' => 125, 'stock' => 100, 'reorder' => 20],
            ['sku' => 'HHD-003', 'name' => 'Joy Dishwashing Liquid 780ml','category'=>$household,'unit'=>$pcs,   'cost' => 78,  'sell' => 108, 'stock' => 120, 'reorder' => 25],
            ['sku' => 'HHD-004', 'name' => 'Domex Toilet Bowl Cleaner 500ml','category'=>$household,'unit'=>$pcs,'cost' => 68,  'sell' => 95,  'stock' => 80,  'reorder' => 15],
            ['sku' => 'HHD-005', 'name' => 'Mr. Clean Multi-Surface 500ml','category'=>$household,'unit'=>$pcs,  'cost' => 75,  'sell' => 105, 'stock' => 60,  'reorder' => 12],

            // Frozen
            ['sku' => 'FRZ-001', 'name' => 'CDO Pepperoni Chorizo 250g','category'=>$frozen,    'unit' => $pack, 'cost' => 85,  'sell' => 118, 'stock' => 60,  'reorder' => 10],
            ['sku' => 'FRZ-002', 'name' => 'Magnolia Chicken Hotdog 500g','category'=>$frozen,  'unit' => $pack, 'cost' => 95,  'sell' => 132, 'stock' => 50,  'reorder' => 10],
            ['sku' => 'FRZ-003', 'name' => 'Purefoods Tender Juicy Hotdog 1kg','category'=>$frozen,'unit'=>$pack,'cost'=>185,   'sell' => 255, 'stock' => 40,  'reorder' => 8],
            ['sku' => 'FRZ-004', 'name' => 'Bounty Fresh Whole Chicken 1.2kg','category'=>$frozen,'unit'=>$pcs,  'cost' => 195, 'sell' => 265, 'stock' => 30,  'reorder' => 8],
            ['sku' => 'FRZ-005', 'name' => 'Silver Swan Bangus 250g', 'category' => $frozen,    'unit' => $pcs,  'cost' => 78,  'sell' => 108, 'stock' => 45,  'reorder' => 10],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name'           => $data['name'],
                    'category_id'    => $data['category']->id,
                    'unit_id'        => $data['unit']->id,
                    'cost_price'     => $data['cost'],
                    'selling_price'  => $data['sell'],
                    'stock_quantity' => $data['stock'],
                    'reorder_level'  => $data['reorder'],
                    'is_active'      => true,
                ]
            );
        }

        $this->command->info('Seeded ' . count($products) . ' products across 9 categories.');
    }
}


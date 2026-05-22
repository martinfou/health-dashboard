<?php

namespace Database\Seeders;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use App\Models\PriceHistory;
use App\Models\PriceStat;
use Illuminate\Database\Seeder;

class GroceryDealSeeder extends Seeder
{
    public function run(): void
    {
        // Maxi
        $maxi = GroceryStore::firstOrCreate(
            ['slug' => 'maxi'],
            ['name' => 'Maxi']
        );

        // Super C
        $superC = GroceryStore::firstOrCreate(
            ['slug' => 'super-c'],
            ['name' => 'Super C']
        );

        // IGA
        $iga = GroceryStore::firstOrCreate(
            ['slug' => 'iga'],
            ['name' => 'IGA']
        );

        $deals = [
            // Maxi deals
            ['store_id' => $maxi->id, 'product' => 'Filets de porc frais',   'category' => 'viande',   'price' => 3.99,  'regular_price' => 7.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Poitrine de poulet',     'category' => 'viande',   'price' => 5.99,  'regular_price' => 9.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Boeuf haché extra-maigre','category' => 'viande',  'price' => 4.49,  'regular_price' => 6.49,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Saumon de l\'Atlantique', 'category' => 'poisson', 'price' => 8.99,  'regular_price' => 13.99, 'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Crevettes nordiques',    'category' => 'poisson',  'price' => 6.99,  'regular_price' => 10.99, 'unit' => '500g',  'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Lait 3.25% 4L',          'category' => 'laitier',  'price' => 6.49,  'regular_price' => 7.99,  'unit' => '4L',    'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Fromage cheddar fort',   'category' => 'laitier',  'price' => 4.99,  'regular_price' => 7.49,  'unit' => '400g',  'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Œufs 18 gros',           'category' => 'laitier',  'price' => 3.99,  'regular_price' => 5.49,  'unit' => '18',    'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Pommes Cortland',        'category' => 'fruits',   'price' => 1.49,  'regular_price' => 2.49,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Fraises du Québec',      'category' => 'fruits',   'price' => 2.99,  'regular_price' => 4.99,  'unit' => '1L',    'store_brand' => null, 'is_bio' => true],
            ['store_id' => $maxi->id, 'product' => 'Bananes',                'category' => 'fruits',   'price' => 0.99,  'regular_price' => 1.29,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Laitue romaine',         'category' => 'legumes',  'price' => 1.99,  'regular_price' => 3.49,  'unit' => '/unité','store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Tomates en grappe',      'category' => 'legumes',  'price' => 2.49,  'regular_price' => 3.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Concombres anglais',     'category' => 'legumes',  'price' => 1.29,  'regular_price' => 2.29,  'unit' => '/unité','store_brand' => null],
            ['store_id' => $maxi->id, 'product' => 'Riz basmati 2kg',        'category' => 'epicerie', 'price' => 4.99,  'regular_price' => 7.99,  'unit' => '2kg',   'store_brand' => 'PC'],
            ['store_id' => $maxi->id, 'product' => 'Café moulu PC 900g',     'category' => 'epicerie', 'price' => 7.99,  'regular_price' => 11.99, 'unit' => '900g',  'store_brand' => 'PC'],

            // Super C deals
            ['store_id' => $superC->id, 'product' => 'Poulet entier',         'category' => 'viande',   'price' => 1.99,  'regular_price' => 4.49,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Côtelettes de porc',    'category' => 'viande',   'price' => 3.49,  'regular_price' => 5.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Crevettes sauvages',    'category' => 'poisson',  'price' => 7.49,  'regular_price' => 12.99, 'unit' => '500g',  'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Yogourt grec 500g',     'category' => 'laitier',  'price' => 2.99,  'regular_price' => 4.99,  'unit' => '500g',  'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Bleuets frais',         'category' => 'fruits',   'price' => 3.49,  'regular_price' => 5.99,  'unit' => '170g',  'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Asperges',              'category' => 'legumes',  'price' => 2.29,  'regular_price' => 3.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Chou-fleur',            'category' => 'legumes',  'price' => 2.99,  'regular_price' => 4.49,  'unit' => '/unité','store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Chips Ketchup 235g',    'category' => 'snacks',   'price' => 1.99,  'regular_price' => 3.99,  'unit' => '235g',  'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Eau pétillante 12x355ml','category' => 'boissons','price' => 2.99,  'regular_price' => 4.99,  'unit' => '12',   'store_brand' => null],
            ['store_id' => $superC->id, 'product' => 'Savon à lessive 40 doses','category' => 'entretien','price' => 6.99, 'regular_price' => 11.99, 'unit' => '40',   'store_brand' => null],

            // IGA deals
            ['store_id' => $iga->id, 'product' => 'Boeuf haché maigre',       'category' => 'viande',   'price' => 3.99,  'regular_price' => 6.99,  'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Cubes de sauté de poulet','category' => 'viande',   'price' => 6.99,  'regular_price' => 10.99, 'unit' => '/lb',   'store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Tilapia surgelé',          'category' => 'poisson',  'price' => 5.49,  'regular_price' => 8.99,  'unit' => '500g',  'store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Mangues',                  'category' => 'fruits',   'price' => 0.99,  'regular_price' => 1.99,  'unit' => '/unité','store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Salade de chou',           'category' => 'legumes',  'price' => 1.99,  'regular_price' => 3.49,  'unit' => '/unité','store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Pâtes Barilla 500g',       'category' => 'epicerie', 'price' => 1.25,  'regular_price' => 2.49,  'unit' => '500g',  'store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Sauce tomate 700ml',       'category' => 'epicerie', 'price' => 1.99,  'regular_price' => 3.49,  'unit' => '700ml', 'store_brand' => null],
            ['store_id' => $iga->id, 'product' => 'Fromage mozzarella râpé',  'category' => 'laitier',  'price' => 3.99,  'regular_price' => 5.99,  'unit' => '450g',  'store_brand' => null],
        ];

        $now = now();
        $from = now()->startOfWeek()->subDay(); // Thursday
        $until = now()->endOfWeek()->addDay();  // next Wednesday

        foreach ($deals as $deal) {
            GroceryDeal::create([
                'grocery_store_id' => $deal['store_id'],
                'product' => $deal['product'],
                'category' => $deal['category'],
                'price' => $deal['price'],
                'unit' => $deal['unit'],
                'regular_price' => $deal['regular_price'],
                'valid_from' => $from,
                'valid_until' => $until,
                'is_bio' => $deal['is_bio'] ?? false,
                'store_brand' => $deal['store_brand'] ?? null,
            ]);
        }

        // Seed historical price stats for key products (from previous weeks)
        $historicalData = [
            // [store_id, product, category, unit, samples, avg, min, max, avg_reg, avg_savings_pct]
            [$maxi->id, 'Filets de porc frais',    'viande',   '/lb',  6, 4.49,  3.99,  5.99,  7.99,  38.5],
            [$maxi->id, 'Poitrine de poulet',      'viande',   '/lb',  8, 6.29,  5.49,  7.99,  9.99,  35.2],
            [$maxi->id, 'Saumon de l\'Atlantique', 'poisson',  '/lb',  5, 9.99,  8.49,  11.99, 13.99, 25.8],
            [$maxi->id, 'Lait 3.25% 4L',           'laitier',  '4L',   12, 6.79,  5.99,  7.49,  7.99,  12.5],
            [$maxi->id, 'Fraises du Québec',       'fruits',   '1L',   4, 3.49,  2.99,  4.49,  4.99,  30.1],
            [$maxi->id, 'Boeuf haché extra-maigre', 'viande',  '/lb',  7, 4.99,  3.99,  5.99,  6.49,  22.0],
            [$superC->id, 'Poulet entier',          'viande',  '/lb',  6, 3.29,  1.99,  4.49,  4.49,  48.5],
            [$superC->id, 'Crevettes sauvages',     'poisson', '500g', 4, 8.99,  7.49,  10.99, 12.99, 25.0],
            [$superC->id, 'Bleuets frais',          'fruits',  '170g', 3, 3.99,  3.49,  4.49,  5.99,  33.0],
            [$superC->id, 'Asperges',               'legumes', '/lb',  4, 2.79,  2.29,  3.49,  3.99,  30.0],
            [$iga->id, 'Boeuf haché maigre',        'viande',  '/lb',  5, 5.29,  3.99,  6.49,  6.99,  28.0],
            [$iga->id, 'Pâtes Barilla 500g',        'epicerie','500g', 8, 1.49,  0.99,  2.49,  2.49,  40.0],
            [$iga->id, 'Mangues',                   'fruits',  '/unité',3, 1.29,  0.99,  1.49,  1.99,  30.0],
        ];

        foreach ($historicalData as $data) {
            PriceStat::create([
                'grocery_store_id'  => $data[0],
                'product'          => $data[1],
                'category'         => $data[2],
                'unit'             => $data[3],
                'sample_count'     => $data[4],
                'avg_sale_price'   => $data[5],
                'min_sale_price'   => $data[6],
                'max_sale_price'   => $data[7],
                'avg_regular_price'=> $data[8],
                'avg_savings_pct'  => $data[9],
                'first_seen'       => now()->subMonths(3),
                'last_seen'        => now(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}

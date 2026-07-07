<?php

namespace Database\Factories;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroceryDealFactory extends Factory
{
    protected $model = GroceryDeal::class;

    public function definition(): array
    {
        $categories = ['fruits', 'legumes', 'viande', 'poisson', 'laitier', 'epicerie', 'snacks', 'boissons'];
        $cat = fake()->randomElement($categories);

        $products = [
            'fruits' => ['Pommes', 'Bananes', 'Fraises', 'Bleuets', 'Oranges', 'Raisins'],
            'legumes' => ['Laitue', 'Tomates', 'Concombres', 'Carottes', 'Brocoli', 'Asperges'],
            'viande' => ['Poulet', 'Boeuf haché', 'Porc', 'Steak', 'Saucisses'],
            'poisson' => ['Saumon', 'Crevettes', 'Tilapia', 'Morue'],
            'laitier' => ['Lait', 'Fromage', 'Yogourt', 'Œufs', 'Beurre'],
            'epicerie' => ['Riz', 'Pâtes', 'Café', 'Huile d\'olive', 'Sauce tomate'],
            'snacks' => ['Chips', 'Biscuits', 'Chocolat', 'Noix'],
            'boissons' => ['Jus', 'Eau', 'Soda', 'Bière'],
        ];

        return [
            'grocery_store_id' => GroceryStore::factory(),
            'product' => fake()->randomElement($products[$cat]),
            'category' => $cat,
            'price' => fake()->randomFloat(2, 0.99, 8.99),
            'unit' => fake()->randomElement(['/lb', '/unité', '/kg', '500g', '1L']),
            'regular_price' => fake()->randomFloat(2, 2.99, 14.99),
            'valid_from' => now()->startOfWeek()->subDay(),
            'valid_until' => now()->endOfWeek()->addDay(),
        ];
    }
}

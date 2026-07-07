<?php

namespace Database\Factories;

use App\Models\GroceryStore;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroceryStoreFactory extends Factory
{
    protected $model = GroceryStore::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Maxi', 'Super C', 'IGA', 'Costco', 'Walmart']);
        return [
            'name' => $name,
            'slug' => str($name)->slug(),
        ];
    }
}

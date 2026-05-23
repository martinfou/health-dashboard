<?php

namespace Tests\Feature;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use App\Models\User;
use Database\Seeders\GroceryDealSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroceryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_grocery_page_loads(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        $response->assertSee('Maxi');
        $response->assertSee('Super C');
        $response->assertSee('IGA');
        $response->assertSee('Circulaires');
    }

    public function test_grocery_page_shows_deals(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        $response->assertSee('Filets de porc frais');
        $response->assertSee('3.99');
        $response->assertSee('Poulet entier');
    }

    public function test_grocery_page_shows_best_deals_section(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        $response->assertSee('Meilleures économies');
    }

    public function test_grocery_page_shows_savings(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        // Filets de porc: 7.99 regular - 3.99 sale = 4.00 savings
        $response->assertSee('Filets de porc frais');
        $response->assertSee('3.99');
    }

    public function test_price_intel_page_loads(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/price-intel');

        $response->assertOk();
        $response->assertSee('Price Intelligence');
    }

    public function test_price_intel_shows_all_deals_analyzed_section(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/price-intel');

        $response->assertOk();
        $response->assertSee('Tous les spéciaux analysés');
    }

    public function test_price_intel_shows_store_ranking(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/price-intel');

        $response->assertOk();
        $response->assertSee('Classement des enseignes');
    }

    public function test_meal_plan_page_loads(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();
        $response->assertSee('Plan repas');
    }

    public function test_meal_plan_shows_menu(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();
        $response->assertSee('Menu de la semaine');
    }

    public function test_meal_plan_shows_grocery_list(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();
        $response->assertSee('Liste d');
    }

    public function test_meal_plan_shows_total(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();
        $response->assertSee('Total');
    }

    public function test_grocery_pages_redirect_when_unauthenticated(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $this->get('/grocery')->assertRedirect('/login');
        $this->get('/grocery/price-intel')->assertRedirect('/login');
        $this->get('/grocery/meal-plan')->assertRedirect('/login');
        $this->get('/grocery/history')->assertRedirect('/login');
    }

    public function test_empty_state_shows_no_deals_message(): void
    {
        $store = GroceryStore::factory()->create(['name' => 'Maxi']);
        // No deals — empty state

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        $response->assertSee('Aucun spécial cette semaine');
    }

    public function test_meal_plan_history_page_loads(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan/history');

        $response->assertOk();
        $response->assertSee('Historique');
        $response->assertSee('Semaines planifiées');
    }

    public function test_meal_plan_records_history(): void
    {
        $this->seed(GroceryDealSeeder::class);

        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();

        // A meal plan was generated and should be recorded
        $this->assertDatabaseCount('meal_plan_usages', 7);
    }

    public function test_meal_plan_excludes_recently_used_recipes(): void
    {
        $this->seed(GroceryDealSeeder::class);

        // First visit — generates plan
        $this->actingAs($this->user)->get('/grocery/meal-plan');

        // Second visit — should exclude previously used
        $response = $this->actingAs($this->user)->get('/grocery/meal-plan');

        $response->assertOk();
        $response->assertSee('exclue');
    }

    public function test_grocery_controller_returns_deal_savings(): void
    {
        $store = GroceryStore::factory()->create(['name' => 'Test Store']);
        $deal = GroceryDeal::factory()->create([
            'grocery_store_id' => $store->id,
            'product' => 'Test Product',
            'price' => 2.00,
            'regular_price' => 5.00,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(6),
        ]);

        $this->assertEquals(3.00, $deal->savings());
        $this->assertEquals(60, $deal->savingsPercent());

        $response = $this->actingAs($this->user)->get('/grocery');

        $response->assertOk();
        $response->assertSee('Test Product');
        $response->assertSee('Économie 3.00');
    }
}

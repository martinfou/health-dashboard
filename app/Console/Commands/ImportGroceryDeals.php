<?php

namespace App\Console\Commands;

use App\Models\GroceryDeal;
use App\Models\GroceryStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportGroceryDeals extends Command
{
    protected $signature = 'grocery:import {store?} {--from=} {--to=}';
    protected $description = 'Import weekly grocery deals from JSON';

    public function handle()
    {
        $storeSlug = $this->argument('store');
        $from = $this->option('from') ?? now()->startOfWeek()->format('Y-m-d');
        $to = $this->option('to') ?? now()->endOfWeek()->format('Y-m-d');

        if ($storeSlug) {
            $this->importStore($storeSlug, $from, $to);
        } else {
            foreach (['maxi', 'super-c', 'iga'] as $slug) {
                $this->importStore($slug, $from, $to);
            }
        }

        $this->info('✅ Import terminé');
        $this->table(
            ['Store', 'Deals', 'From', 'To'],
            GroceryDeal::current()->get()->groupBy('grocery_store_id')->map(function ($deals) {
                $first = $deals->first();
                return [
                    $first->store->name,
                    $deals->count(),
                    $first->valid_from->format('d/m'),
                    $first->valid_until->format('d/m'),
                ];
            })
        );
    }

    private function importStore(string $slug, string $from, string $to): void
    {
        $store = GroceryStore::firstOrCreate(
            ['slug' => $slug],
            ['name' => ucwords(str_replace('-', ' ', $slug))]
        );

        // Try to fetch from local scraper JSON
        $jsonPath = storage_path("app/grocery/{$slug}-{$from}-{$to}.json");
        if (!file_exists($jsonPath)) {
            $this->warn("  ⚠️  $slug: fichier JSON non trouvé à $jsonPath");
            $this->line("     Lancer d'abord: python3 scripts/scrape-circulaire.py --store $slug");
            return;
        }

        $deals = json_decode(file_get_contents($jsonPath), true);
        if (empty($deals)) {
            $this->warn("  ⚠️  $slug: aucun deal trouvé dans le JSON");
            return;
        }

        // Delete previous deals for this period
        GroceryDeal::where('grocery_store_id', $store->id)
            ->where('valid_from', $from)
            ->where('valid_until', $to)
            ->delete();

        foreach ($deals as $deal) {
            GroceryDeal::create([
                'grocery_store_id' => $store->id,
                'product' => $deal['product'],
                'category' => $deal['category'] ?? 'epicerie',
                'price' => $deal['price'],
                'unit' => $deal['unit'] ?? null,
                'regular_price' => $deal['regular_price'] ?? null,
                'valid_from' => $deal['valid_from'] ?? $from,
                'valid_until' => $deal['valid_until'] ?? $to,
                'is_bio' => $deal['is_bio'] ?? false,
                'store_brand' => $deal['store_brand'] ?? null,
            ]);
        }

        $this->info("  ✅ $slug: " . count($deals) . " deals importés");
    }
}

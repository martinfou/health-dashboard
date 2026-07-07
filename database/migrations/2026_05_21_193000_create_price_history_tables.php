<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prix historiques des spéciaux — pour savoir si un deal est vraiment bon
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_store_id')->constrained()->cascadeOnDelete();
            $table->string('product');              // "Filets de porc frais"
            $table->string('category');             // viande, fruits, legumes...
            $table->decimal('sale_price', 8, 2);    // Prix en spécial
            $table->decimal('regular_price', 8, 2)->nullable(); // Prix régulier (si connu)
            $table->string('unit')->nullable();      // /lb, /kg, /unité
            $table->date('valid_from');              // Début de la circulaire
            $table->date('valid_until');             // Fin de la circulaire
            $table->date('scraped_at');              // Quand on a scrapé
            $table->string('store_brand')->nullable();
            $table->boolean('is_bio')->default(false);
            $table->timestamps();

            $table->index(['grocery_store_id', 'product']);
            $table->index('scraped_at');
        });

        // Agrégations pré-calculées pour requêtes rapides
        Schema::create('price_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_store_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->string('category');
            $table->string('unit')->nullable();
            $table->integer('sample_count');         // Nombre d'observations
            $table->decimal('avg_sale_price', 8, 2); // Prix moyen en spécial
            $table->decimal('min_sale_price', 8, 2); // Meilleur prix ever
            $table->decimal('max_sale_price', 8, 2); // Pire prix ever
            $table->decimal('avg_regular_price', 8, 2)->nullable();
            $table->decimal('avg_savings_pct', 5, 1)->nullable(); // Économie moyenne en %
            $table->date('first_seen');
            $table->date('last_seen');
            $table->timestamps();

            $table->unique(['grocery_store_id', 'product', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_stats');
        Schema::dropIfExists('price_history');
    }
};

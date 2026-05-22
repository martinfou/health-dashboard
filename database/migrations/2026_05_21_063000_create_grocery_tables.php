<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grocery_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Maxi, Super C, Costco, IGA
            $table->string('slug')->unique();
            $table->string('flyer_url')->nullable();
            $table->timestamps();
        });

        Schema::create('grocery_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_store_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->string('category'); // fruits, legumes, viande, poisson, laitier, surgeles, epicerie, entretien, boissons, snacks
            $table->decimal('price', 8, 2);
            $table->string('unit')->nullable(); // /lb, /kg, /unite, /sac
            $table->decimal('regular_price', 8, 2)->nullable();
            $table->date('valid_from');
            $table->date('valid_until');
            $table->string('flyer_page')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_bio')->default(false);
            $table->string('store_brand')->nullable(); // PC, Selection, Sans Nom
            $table->timestamps();

            $table->index(['valid_from', 'valid_until']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_deals');
        Schema::dropIfExists('grocery_stores');
    }
};

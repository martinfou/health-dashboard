<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_up_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_deal_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->foreignId('grocery_store_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2);
            $table->decimal('historical_low_price', 8, 2);
            $table->decimal('savings_pct', 5, 1);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('product');
            $table->index('triggered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_up_alerts');
    }
};

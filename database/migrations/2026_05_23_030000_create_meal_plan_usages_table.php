<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_usages', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_name');
            $table->date('used_on');
            $table->string('week_label'); // e.g., "2026-W21"
            $table->json('context')->nullable(); // stores which deals were matched, cost, etc.
            $table->timestamps();

            $table->index('recipe_name');
            $table->index('used_on');
            $table->unique(['recipe_name', 'used_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_usages');
    }
};

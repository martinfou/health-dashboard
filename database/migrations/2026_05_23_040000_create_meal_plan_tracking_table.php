<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipe_name');
            $table->date('planned_date');
            $table->string('meal_slot'); // breakfast, lunch, dinner, snack
            $table->integer('calories');
            $table->decimal('protein_g', 6, 1)->default(0);
            $table->decimal('carbs_g', 6, 1)->default(0);
            $table->decimal('fat_g', 6, 1)->default(0);
            $table->decimal('fiber_g', 6, 1)->default(0);
            $table->decimal('sugar_g', 6, 1)->default(0);
            $table->string('icon')->nullable();
            $table->boolean('eaten')->default(false);
            $table->timestamp('eaten_at')->nullable();
            $table->unsignedBigInteger('nutrition_log_id')->nullable();
            $table->json('context')->nullable(); // store which deals matched
            $table->timestamps();

            $table->unique(['user_id', 'planned_date', 'recipe_name', 'meal_slot']);
            $table->index(['user_id', 'planned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_tracking');
    }
};

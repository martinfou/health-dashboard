<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nutrition_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('logged_at');
            $table->integer('calories')->nullable();
            $table->decimal('protein_g', 6, 1)->nullable();
            $table->decimal('fat_g', 6, 1)->nullable();
            $table->decimal('carbs_g', 6, 1)->nullable();
            $table->decimal('fiber_g', 6, 1)->nullable();
            $table->decimal('sugar_g', 6, 1)->nullable();
            $table->string('source')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('nutrition_logs'); }
};

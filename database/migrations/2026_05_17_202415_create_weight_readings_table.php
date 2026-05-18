<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('weight_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_at');
            $table->decimal('weight_lb', 5, 1);
            $table->decimal('weight_kg', 5, 1);
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->decimal('bmi', 4, 1)->nullable();
            $table->decimal('muscle_lb', 5, 1)->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('weight_readings'); }
};

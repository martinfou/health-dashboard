<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('activity_type')->nullable();
            $table->integer('steps')->nullable();
            $table->integer('calories_burned')->nullable();
            $table->integer('heart_rate_avg')->nullable();
            $table->integer('gym_sessions')->nullable();
            $table->string('source')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('activity_logs'); }
};

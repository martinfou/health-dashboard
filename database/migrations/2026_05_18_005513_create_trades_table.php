<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('instrument');
            $table->string('side'); // BUY/SELL
            $table->decimal('entry_price', 10, 5);
            $table->decimal('exit_price', 10, 5)->nullable();
            $table->decimal('stop_loss', 10, 5);
            $table->decimal('take_profit', 10, 5);
            $table->decimal('quantity', 8, 2); // lots
            $table->decimal('pnl', 10, 2)->nullable();
            $table->decimal('pnl_pips', 8, 1)->nullable();
            $table->string('status'); // PENDING, OPEN, CLOSED, CANCELLED
            $table->string('strategy_name')->nullable();
            $table->string('catalyst')->nullable();
            $table->integer('confidence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trades'); }
};

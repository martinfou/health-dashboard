<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trade_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('instrument');
            $table->string('side');
            $table->decimal('entry_price', 10, 5)->nullable();
            $table->decimal('stop_loss', 10, 5);
            $table->decimal('take_profit', 10, 5);
            $table->decimal('quantity', 8, 2);
            $table->integer('confidence');
            $table->string('status')->default('PENDING'); // PENDING, EXECUTED, EXPIRED
            $table->text('reason');
            $table->string('catalyst');
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trade_signals'); }
};

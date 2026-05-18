<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trading_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('broker');
            $table->string('label');
            $table->string('api_key_encrypted');
            $table->string('account_id');
            $table->boolean('is_demo')->default(true);
            $table->decimal('balance', 12, 2)->nullable();
            $table->decimal('nav', 12, 2)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trading_accounts'); }
};

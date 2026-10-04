<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liability_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('liability_id')->constrained('liabilities')->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            
            $table->string('type', 10); // 'payment' or 'increase'
            $table->bigInteger('amount');
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liability_transactions');
    }
};

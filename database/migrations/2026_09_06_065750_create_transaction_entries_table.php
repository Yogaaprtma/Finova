<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
            
            $table->foreignUuid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUuid('asset_account_id')->nullable()->constrained('asset_accounts')->nullOnDelete();
            $table->foreignUuid('liability_id')->nullable()->constrained('liabilities')->nullOnDelete();
            
            $table->string('entry_type', 6); // 'debit' or 'credit'
            $table->bigInteger('amount'); // always positive
            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
    }
};

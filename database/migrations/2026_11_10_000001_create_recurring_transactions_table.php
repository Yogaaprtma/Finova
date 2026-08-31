<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('type', 10); // 'income', 'expense'
            $table->bigInteger('amount');
            $table->string('currency', 3)->default('IDR');
            $table->string('description', 255);

            $table->string('frequency', 10); // 'daily', 'weekly', 'biweekly', 'monthly', 'yearly'
            $table->smallInteger('day_of_month')->nullable(); // 1-28 for monthly
            $table->smallInteger('day_of_week')->nullable(); // 0-6 for weekly (0=Sunday)

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('auto_confirm')->default(false);
            $table->date('last_generated_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index(['is_active', 'last_generated_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('name', 100);
            $table->string('type', 20);
            
            $table->string('currency', 3)->default('IDR');
            $table->bigInteger('initial_balance')->default(0);
            $table->bigInteger('current_balance')->default(0);
            
            $table->bigInteger('credit_limit')->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->bigInteger('minimum_payment')->nullable();
            $table->smallInteger('due_date_day')->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->smallInteger('remaining_terms')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};

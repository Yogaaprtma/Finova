<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('name', 100);
            $table->string('type', 20);
            
            $table->string('platform', 100)->nullable();
            $table->string('ticker', 20)->nullable();
            $table->string('currency', 3)->default('IDR');
            
            $table->decimal('quantity', 20, 8)->default(0);
            $table->decimal('avg_purchase_price', 20, 8)->default(0);
            $table->decimal('current_price', 20, 8)->default(0);
            
            $table->bigInteger('manual_value')->nullable();
            $table->timestamp('last_price_update')->nullable();
            
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
        Schema::dropIfExists('asset_accounts');
    }
};

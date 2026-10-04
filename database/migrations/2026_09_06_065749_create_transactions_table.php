<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            $table->string('type', 30);
            
            $table->date('date');
            $table->bigInteger('amount'); // always positive
            $table->string('currency', 3)->default('IDR');
            
            $table->string('description', 255);
            $table->text('notes')->nullable();
            $table->string('reference_number', 100)->nullable();
            
            $table->string('source', 20)->default('manual');
            
            $table->boolean('is_confirmed')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

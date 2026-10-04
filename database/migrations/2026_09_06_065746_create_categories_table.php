<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            
            $table->string('name', 100);
            $table->string('type', 10);
            $table->string('icon', 50)->default('circle');
            $table->string('color', 7)->default('#6B7280');
            
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'name', 'type', 'parent_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

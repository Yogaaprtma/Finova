<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('net_worth_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->date('date');
            $table->bigInteger('total_cash')->default(0);
            $table->bigInteger('total_investments')->default(0);
            $table->bigInteger('total_liabilities')->default(0);
            $table->bigInteger('net_worth')->default(0);
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worth_snapshots');
    }
};

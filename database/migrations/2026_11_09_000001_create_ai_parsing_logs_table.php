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
        Schema::create('ai_parsing_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('source', 20)->default('web'); // 'web', 'whatsapp'
            $table->text('raw_message');
            $table->json('parsed_data');
            $table->decimal('confidence', 3, 2);
            $table->string('ai_provider', 50);
            $table->string('ai_model', 100);
            $table->string('prompt_version', 20)->default('v1.0');
            $table->string('status', 20)->default('pending'); // 'pending', 'confirmed', 'rejected', 'error'
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_parsing_logs');
    }
};

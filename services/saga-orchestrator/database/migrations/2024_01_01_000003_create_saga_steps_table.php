<?php

declare(strict_types=1);

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
        Schema::create('saga_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('saga_transaction_id');
            $table->string('step_name', 100);
            $table->unsignedTinyInteger('step_order');
            $table->string('status', 20)->default('PENDING')->index();
            $table->json('payload')->default('{}');
            $table->json('result')->default('{}');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('saga_transaction_id')
                  ->references('id')
                  ->on('saga_transactions')
                  ->onDelete('cascade');

            $table->index(['saga_transaction_id', 'step_order']);
            $table->index(['saga_transaction_id', 'step_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saga_steps');
    }
};

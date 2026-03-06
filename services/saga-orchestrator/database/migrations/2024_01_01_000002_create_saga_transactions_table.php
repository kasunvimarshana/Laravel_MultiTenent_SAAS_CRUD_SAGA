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
        Schema::create('saga_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('saga_id')->unique();
            $table->string('tenant_id')->nullable()->index();
            $table->string('saga_type')->index();
            $table->string('current_step')->nullable();
            $table->string('status', 20)->default('PENDING')->index();
            $table->json('payload');
            $table->json('completed_steps')->default('[]');
            $table->string('failed_step')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['saga_type', 'status']);
            $table->index(['status', 'retry_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saga_transactions');
    }
};

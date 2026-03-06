<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('saga_id');
            $table->uuid('order_id');
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('status', 20)->default('PENDING');
            $table->jsonb('items');
            $table->timestamp('reserved_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('saga_id');
            $table->index('order_id');
            $table->index('status');
            $table->index(['status', 'expires_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_reservations'); }
};

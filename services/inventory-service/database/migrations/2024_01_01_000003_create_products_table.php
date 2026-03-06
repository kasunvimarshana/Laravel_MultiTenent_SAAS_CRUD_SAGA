<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('unit_of_measure', 50)->default('unit');
            $table->integer('minimum_stock_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->jsonb('attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};

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
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('database_name');
            $table->boolean('is_active')->default(true)->index();
            $table->string('api_token', 512)->nullable();
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['domain', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

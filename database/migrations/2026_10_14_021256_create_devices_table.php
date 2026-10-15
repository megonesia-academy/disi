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
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('screen_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('key')->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('app_version')->nullable();
            $table->string('status')->default('offline');
            $table->jsonb('capabilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['screen_id', 'status']);
            $table->index(['is_active', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_connections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider', 80);
            $table->text('base_url');
            $table->string('auth_type', 30)->default('none');
            $table->text('credential')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->foreignUuid('api_connection_id')->nullable()->after('template_id')->constrained('api_connections')->nullOnDelete();
            $table->index(['tenant_id', 'api_connection_id']);
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->dropForeign(['api_connection_id']);
            $table->dropIndex(['tenant_id', 'api_connection_id']);
            $table->dropColumn('api_connection_id');
        });

        Schema::dropIfExists('api_connections');
    }
};

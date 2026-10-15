<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('html');
            $table->text('css')->nullable();
            $table->jsonb('schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->foreignUuid('template_id')->nullable()->after('template')->constrained('content_templates')->nullOnDelete();
            $table->index(['tenant_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->dropForeign(['template_id']);
            $table->dropIndex(['tenant_id', 'template_id']);
            $table->dropColumn('template_id');
        });

        Schema::dropIfExists('content_templates');
    }
};

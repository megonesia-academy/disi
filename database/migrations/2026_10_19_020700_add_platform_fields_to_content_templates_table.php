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
        Schema::table('content_templates', function (Blueprint $table) {
            $table->foreignUuid('platform_template_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('platform_templates')
                ->nullOnDelete();
            $table->boolean('is_rented')->default(false)->after('is_active');
            $table->index(['tenant_id', 'platform_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_templates', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'platform_template_id']);
            $table->dropConstrainedForeignId('platform_template_id');
            $table->dropColumn('is_rented');
        });
    }
};

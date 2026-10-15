<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->dropForeign(['media_asset_id']);
            $table->uuid('media_asset_id')->nullable()->change();
            $table->foreign('media_asset_id')->references('id')->on('media_assets')->nullOnDelete();
            $table->string('template', 50)->default('media')->after('media_asset_id');
            $table->jsonb('content')->nullable()->after('template');
            $table->index(['tenant_id', 'template']);
        });
    }

    public function down(): void
    {
        Schema::table('playlist_items', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'template']);
            $table->dropForeign(['media_asset_id']);
            $table->dropColumn(['template', 'content']);
            $table->uuid('media_asset_id')->nullable(false)->change();
            $table->foreign('media_asset_id')->references('id')->on('media_assets')->restrictOnDelete();
        });
    }
};

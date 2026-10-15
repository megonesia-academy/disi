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
        Schema::create('screens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('orientation')->default('landscape');
            $table->unsignedSmallInteger('resolution_width')->default(1920);
            $table->unsignedSmallInteger('resolution_height')->default(1080);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'orientation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('player_secret')->nullable()->unique()->after('key');
        });

        DB::table('devices')
            ->whereNull('player_secret')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $device): void {
                DB::table('devices')
                    ->where('id', $device->id)
                    ->update(['player_secret' => Str::random(48)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('player_secret');
        });
    }
};

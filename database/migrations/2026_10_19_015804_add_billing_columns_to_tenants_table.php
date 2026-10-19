<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('token_balance')->default(100)->after('status');
            $table->string('subscription_status', 20)->default('active')->after('token_balance');
            $table->timestamp('subscription_renews_at')->nullable()->after('subscription_status');
        });

        DB::table('tenants')->update([
            'subscription_renews_at' => now()->addMonth(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['token_balance', 'subscription_status', 'subscription_renews_at']);
        });
    }
};

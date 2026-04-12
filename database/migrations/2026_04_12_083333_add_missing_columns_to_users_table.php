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
        Schema::table('users', function (Blueprint $table) {
            // Two-factor authentication flag
            $table->boolean('two_factor_enabled')->default(false)->after('two_factor_recovery_codes');

            // Rate limit bypass flag for admin users
            $table->boolean('rate_limit_bypass')->default(false)->after('two_factor_enabled');

            // Tenant relationship for tenant-specific users
            $table->foreignId('tenant_id')->nullable()->after('rate_limit_bypass')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor('tenants');
            $table->dropColumn(['two_factor_enabled', 'rate_limit_bypass']);
        });
    }
};

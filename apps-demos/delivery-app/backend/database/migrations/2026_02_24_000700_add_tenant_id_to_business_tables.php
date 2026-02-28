<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'products',
            'ingredients',
            'extras',
            'combos',
            'bundles',
            'orders',
            'customer_profiles',
            'audit_logs',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'products',
            'ingredients',
            'extras',
            'combos',
            'bundles',
            'orders',
            'customer_profiles',
            'audit_logs',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};


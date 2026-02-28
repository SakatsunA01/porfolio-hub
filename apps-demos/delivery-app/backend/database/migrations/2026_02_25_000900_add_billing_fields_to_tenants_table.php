<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'plan_key')) {
                $table->string('plan_key', 32)->default('full')->after('slug');
            }
            if (!Schema::hasColumn('tenants', 'billing_status')) {
                $table->string('billing_status', 32)->default('paid')->after('plan_key');
            }
            if (!Schema::hasColumn('tenants', 'monthly_fee_ars')) {
                $table->unsignedInteger('monthly_fee_ars')->default(160000)->after('billing_status');
            }
            if (!Schema::hasColumn('tenants', 'next_billing_at')) {
                $table->timestamp('next_billing_at')->nullable()->after('monthly_fee_ars');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['next_billing_at', 'monthly_fee_ars', 'billing_status', 'plan_key'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


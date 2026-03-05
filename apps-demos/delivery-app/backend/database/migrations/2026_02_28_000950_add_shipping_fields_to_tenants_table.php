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
            if (!Schema::hasColumn('tenants', 'shipping_fee_ars')) {
                $table->unsignedInteger('shipping_fee_ars')->default(0)->after('monthly_fee_ars');
            }

            if (!Schema::hasColumn('tenants', 'free_shipping_threshold_ars')) {
                $table->unsignedInteger('free_shipping_threshold_ars')->default(0)->after('shipping_fee_ars');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['free_shipping_threshold_ars', 'shipping_fee_ars'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

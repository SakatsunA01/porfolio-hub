<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('address');
            }

            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }

            if (!Schema::hasColumn('orders', 'cash_received')) {
                $table->decimal('cash_received', 10, 2)->nullable()->after('total');
            }

            if (!Schema::hasColumn('orders', 'change_amount')) {
                $table->decimal('change_amount', 10, 2)->default(0)->after('cash_received');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'change_amount')) {
                $table->dropColumn('change_amount');
            }
            if (Schema::hasColumn('orders', 'cash_received')) {
                $table->dropColumn('cash_received');
            }
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};


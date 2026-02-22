<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('description');
            }

            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->unsignedInteger('stock_quantity')->default(0)->after('prep_min');
            }

            if (!Schema::hasColumn('products', 'min_stock_quantity')) {
                $table->unsignedInteger('min_stock_quantity')->default(0)->after('stock_quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'min_stock_quantity')) {
                $table->dropColumn('min_stock_quantity');
            }

            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }

            if (Schema::hasColumn('products', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};


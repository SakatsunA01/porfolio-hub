<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ingredients')) {
            return;
        }

        Schema::table('ingredients', function (Blueprint $table) {
            if (!Schema::hasColumn('ingredients', 'stock_quantity')) {
                $table->decimal('stock_quantity', 10, 2)->default(0)->after('additional_price');
            }

            if (!Schema::hasColumn('ingredients', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0)->after('stock_quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ingredients')) {
            return;
        }

        Schema::table('ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('ingredients', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }

            if (Schema::hasColumn('ingredients', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
        });
    }
};


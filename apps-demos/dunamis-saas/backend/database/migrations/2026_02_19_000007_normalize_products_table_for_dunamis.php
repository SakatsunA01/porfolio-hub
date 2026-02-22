<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->default(1)->after('id');
            }
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->after('organization_id');
            }
            if (!Schema::hasColumn('products', 'cost_price')) {
                $table->decimal('cost_price', 14, 2)->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'cost_ars')) {
                $table->decimal('cost_ars', 14, 2)->nullable()->after('cost_price');
            }
            if (!Schema::hasColumn('products', 'cost_usd')) {
                $table->decimal('cost_usd', 14, 2)->nullable()->after('cost_ars');
            }
            if (!Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 14, 2)->default(0)->after('cost_usd');
            }
            if (!Schema::hasColumn('products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('sale_price');
            }
            if (!Schema::hasColumn('products', 'min_stock_quantity')) {
                $table->integer('min_stock_quantity')->default(0)->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'min_stock_alert')) {
                $table->integer('min_stock_alert')->default(0)->after('min_stock_quantity');
            }
            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable()->after('min_stock_alert');
            }
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('products', 'price') && Schema::hasColumn('products', 'sale_price')) {
            DB::table('products')->whereNull('sale_price')->orWhere('sale_price', 0)->update([
                'sale_price' => DB::raw('price'),
            ]);
        }

        if (Schema::hasColumn('products', 'organization_id')) {
            DB::table('products')->whereNull('organization_id')->update(['organization_id' => 1]);
        }

        if (Schema::hasColumn('products', 'sku')) {
            $rows = DB::table('products')->select('id', 'sku')->get();
            foreach ($rows as $row) {
                if (!$row->sku) {
                    DB::table('products')->where('id', $row->id)->update([
                        'sku' => 'SKU-LEGACY-'.$row->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally left without destructive rollback.
    }
};

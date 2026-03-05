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
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            if (! Schema::hasColumn('products', 'description_short')) {
                $table->text('description_short')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('products', 'description_long')) {
                $table->longText('description_long')->nullable()->after('description_short');
            }

            if (! Schema::hasColumn('products', 'type')) {
                $table->enum('type', ['permanent', 'limited', 'preorder'])
                    ->default('permanent')
                    ->after('price');
            }

            if (! Schema::hasColumn('products', 'stock_global')) {
                $table->integer('stock_global')->nullable()->after('type');
            }

            if (! Schema::hasColumn('products', 'preorder_shipping_date')) {
                $table->date('preorder_shipping_date')->nullable()->after('stock_global');
            }

            if (! Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('preorder_shipping_date');
            }

            if (! Schema::hasColumn('products', 'is_dropship')) {
                $table->boolean('is_dropship')->default(false)->after('is_active');
            }
        });

        DB::table('products')
            ->select(['id', 'name', 'description'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $baseSlug = Str::slug($product->name);
                $slug = $baseSlug === '' ? 'product-'.$product->id : $baseSlug;
                $suffix = 1;

                while (
                    DB::table('products')
                        ->where('slug', $slug)
                        ->where('id', '!=', $product->id)
                        ->exists()
                ) {
                    $slug = $baseSlug.'-'.$suffix;
                    $suffix++;
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'slug' => $slug,
                        'description_short' => $product->description,
                        'description_long' => $product->description,
                    ]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->decimal('price', 10, 2)->change();

            $table->unique('slug');
            $table->index('type');
            $table->index('is_active');
            $table->index('is_dropship');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['is_dropship']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['type']);
            $table->dropUnique(['slug']);

            $table->dropColumn([
                'slug',
                'description_short',
                'description_long',
                'type',
                'stock_global',
                'preorder_shipping_date',
                'is_active',
                'is_dropship',
            ]);

            $table->decimal('price', 8, 2)->change();
        });
    }
};

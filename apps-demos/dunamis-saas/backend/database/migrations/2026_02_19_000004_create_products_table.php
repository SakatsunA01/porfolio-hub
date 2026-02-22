<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 14, 2)->nullable();
            $table->decimal('cost_ars', 14, 2)->nullable();
            $table->decimal('cost_usd', 14, 2)->nullable();
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_quantity')->default(0);
            $table->integer('min_stock_alert')->default(0);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'sku']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::drop('products');
        }
    }
};

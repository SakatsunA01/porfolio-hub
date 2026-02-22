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
        if (Schema::hasTable('products')) {
            if (!Schema::hasColumn('products', 'base_price')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->decimal('base_price', 10, 2)->default(0)->after('description');
                });
            }

            if (!Schema::hasColumn('products', 'prep_min')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedInteger('prep_min')->default(15)->after('base_price');
                });
            }

            if (!Schema::hasColumn('products', 'image_url')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->string('image_url')->nullable()->after('prep_min');
                });
            }

            if (!Schema::hasColumn('products', 'is_active')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('image_url');
                });
            }

            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->unsignedInteger('prep_min')->default(15);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

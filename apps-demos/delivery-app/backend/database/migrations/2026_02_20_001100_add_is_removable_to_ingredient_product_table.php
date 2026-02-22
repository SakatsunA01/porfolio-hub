<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ingredient_product') || Schema::hasColumn('ingredient_product', 'is_removable')) {
            return;
        }

        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->boolean('is_removable')->default(true)->after('is_default');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ingredient_product') || !Schema::hasColumn('ingredient_product', 'is_removable')) {
            return;
        }

        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->dropColumn('is_removable');
        });
    }
};


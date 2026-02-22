<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'price')) {
            DB::statement('ALTER TABLE products MODIFY price DECIMAL(14,2) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'price')) {
            DB::statement('ALTER TABLE products MODIFY price DECIMAL(8,2) NOT NULL DEFAULT 0');
        }
    }
};

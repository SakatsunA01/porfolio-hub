<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('daily_menus')) {
            Schema::create('daily_menus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('image_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->enum('slot', ['all_day', 'lunch', 'dinner'])->default('all_day');
                $table->json('weekdays')->nullable();
                $table->dateTime('active_from')->nullable();
                $table->dateTime('active_to')->nullable();
                $table->unsignedInteger('priority')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('daily_menu_items')) {
            Schema::create('daily_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_menu_id')->constrained('daily_menus')->cascadeOnDelete();
                $table->enum('item_type', ['product', 'combo', 'bundle']);
                $table->unsignedBigInteger('item_id');
                $table->decimal('promo_price', 10, 2)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['daily_menu_id', 'item_type', 'item_id'], 'daily_menu_items_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_menu_items');
        Schema::dropIfExists('daily_menus');
    }
};


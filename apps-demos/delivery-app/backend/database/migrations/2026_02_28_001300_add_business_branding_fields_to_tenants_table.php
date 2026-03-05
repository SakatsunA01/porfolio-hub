<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('logo_url')->nullable()->after('free_shipping_threshold_ars');
            $table->string('brand_theme_key', 40)->default('green')->after('logo_url');
            $table->string('brand_primary_color', 20)->default('#10B981')->after('brand_theme_key');
            $table->string('business_phone', 40)->nullable()->after('brand_primary_color');
            $table->string('business_address', 255)->nullable()->after('business_phone');
            $table->json('business_hours_json')->nullable()->after('business_address');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_url',
                'brand_theme_key',
                'brand_primary_color',
                'business_phone',
                'business_address',
                'business_hours_json',
            ]);
        });
    }
};

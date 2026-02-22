<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@dunamis.local'],
            [
                'name' => 'Demo Dunamis',
                'password' => Hash::make('demo1234'),
                'organization_id' => 1,
                'email_verified_at' => now(),
            ]
        );

        ExchangeRate::query()->updateOrCreate(
            ['moneda' => 'USD', 'nombre' => 'blue'],
            ['compra' => 1180, 'venta' => 1210, 'fecha_actualizacion' => now()]
        );

        ExchangeRate::query()->updateOrCreate(
            ['moneda' => 'USD', 'nombre' => 'oficial'],
            ['compra' => 1020, 'venta' => 1060, 'fecha_actualizacion' => now()]
        );

        $client = Client::query()->firstOrCreate(
            ['organization_id' => 1, 'name' => 'Consumidor Demo'],
            ['email' => 'cliente@demo.local']
        );

        $productPayload = [
            'organization_id' => 1,
            'sku' => 'SKU-DEMO-001',
            'name' => 'Producto Demo',
            'description' => 'Producto de demostracion',
            'cost_price' => 10000,
            'cost_ars' => 10000,
            'cost_usd' => 8.26,
            'sale_price' => 15000,
            'stock_quantity' => 40,
            'min_stock_quantity' => 5,
            'min_stock_alert' => 1,
        ];

        $legacyCommerceId = null;
        if (Schema::hasTable('commerces')) {
            $existingCommerce = DB::table('commerces')->where('name', 'Dunamis Demo Commerce')->first();
            $legacyCommerceId = $existingCommerce?->id;
            if (!$legacyCommerceId) {
                $legacyCommerceId = DB::table('commerces')->insertGetId([
                    'name' => 'Dunamis Demo Commerce',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $legacyCategoryId = null;
        if ($legacyCommerceId && Schema::hasTable('categories')) {
            $existingCategory = DB::table('categories')
                ->where('commerce_id', $legacyCommerceId)
                ->where('name', 'General')
                ->first();
            $legacyCategoryId = $existingCategory?->id;
            if (!$legacyCategoryId) {
                $legacyCategoryId = DB::table('categories')->insertGetId([
                    'commerce_id' => $legacyCommerceId,
                    'name' => 'General',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasColumn('products', 'commerce_id')) {
            $productPayload['commerce_id'] = $legacyCommerceId;
        }
        if (Schema::hasColumn('products', 'category_id')) {
            $productPayload['category_id'] = $legacyCategoryId;
        }
        if (Schema::hasColumn('products', 'price')) {
            $productPayload['price'] = 15000;
        }

        $product = Product::query()->firstOrCreate(
            ['organization_id' => 1, 'sku' => 'SKU-DEMO-001'],
            $productPayload
        );

        $sale = Sale::query()->firstOrCreate(
            ['organization_id' => 1, 'user_id' => $user->id, 'client_id' => $client->id, 'status' => 'completed'],
            [
                'total_amount' => 30000,
                'payment_method' => 'cash',
                'moneda_cobro' => 'ARS',
            ]
        );

        SaleItem::query()->firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $product->id],
            [
                'product_name' => $product->name,
                'quantity' => 2,
                'unit_price' => 15000,
                'cost_ars' => 10000,
                'total_price' => 30000,
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\Combo;
use App\Models\CustomerProfile;
use App\Models\DailyMenu;
use App\Models\Extra;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo-delivery'],
            [
                'name' => 'Demo Delivery',
                'plan_key' => 'full',
                'billing_status' => 'paid',
                'monthly_fee_ars' => 160000,
                'shipping_fee_ars' => 2500,
                'free_shipping_threshold_ars' => 30000,
                'next_billing_at' => now()->addDays(15),
                'is_active' => true,
            ]
        );

        $roles = collect([
            ['name' => 'superadmin', 'label' => 'Super Administrador'],
            ['name' => 'admin', 'label' => 'Administrador'],
            ['name' => 'employee', 'label' => 'Empleado'],
            ['name' => 'driver', 'label' => 'Repartidor'],
            ['name' => 'client', 'label' => 'Cliente'],
        ])->mapWithKeys(function (array $role) {
            $record = Role::query()->updateOrCreate(
                ['name' => $role['name']],
                ['label' => $role['label']]
            );

            return [$role['name'] => $record];
        });

        $users = collect([
            [
                'email' => 'superadmin@dunamis.local',
                'name' => 'Super Admin Dunamis',
                'role' => 'superadmin',
                'role_id' => $roles['superadmin']->id,
                'tenant_id' => null,
            ],
            [
                'email' => 'admin@delivery.local',
                'name' => 'Admin Delivery',
                'role' => 'admin',
                'role_id' => $roles['admin']->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'email' => 'empleado@delivery.local',
                'name' => 'Empleado Delivery',
                'role' => 'employee',
                'role_id' => $roles['employee']->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'email' => 'repartidor@delivery.local',
                'name' => 'Repartidor Delivery',
                'role' => 'driver',
                'role_id' => $roles['driver']->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'email' => 'cliente@delivery.local',
                'name' => 'Cliente Delivery',
                'role' => 'client',
                'role_id' => $roles['client']->id,
                'tenant_id' => $tenant->id,
            ],
        ])->mapWithKeys(function (array $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'is_active' => true,
                    'password' => Hash::make('demo1234'),
                ]
            );

            return [$userData['email'] => $user];
        });

        DB::transaction(function () use ($tenant, $users) {
            $this->resetTenantBusinessData($tenant->id);

            $ingredients = $this->seedIngredients($tenant->id);
            $products = $this->seedProducts($tenant->id, $ingredients);
            $combos = $this->seedCombos($tenant->id, $products);
            $bundles = $this->seedBundles($tenant->id, $products);
            $this->seedDailyMenus($tenant->id, $products, $combos);
            $this->seedCustomerProfiles($tenant->id);
            $this->seedOrders($tenant->id, $users, $products, $combos, $bundles);
            $this->seedAuditLogs($tenant->id, $users);
        });
    }

    private function resetTenantBusinessData(int $tenantId): void
    {
        $productIds = Product::query()->where('tenant_id', $tenantId)->pluck('id');
        $comboIds = Combo::query()->where('tenant_id', $tenantId)->pluck('id');
        $bundleIds = Bundle::query()->where('tenant_id', $tenantId)->pluck('id');
        $menuIds = DailyMenu::query()->where('tenant_id', $tenantId)->pluck('id');
        $orderIds = Order::query()->where('tenant_id', $tenantId)->pluck('id');

        if ($menuIds->isNotEmpty()) {
            DB::table('daily_menu_items')->whereIn('daily_menu_id', $menuIds)->delete();
        }

        if ($orderIds->isNotEmpty()) {
            OrderItem::query()->whereIn('order_id', $orderIds)->delete();
            Order::query()->whereIn('id', $orderIds)->delete();
        }

        CustomerProfile::query()->where('tenant_id', $tenantId)->delete();
        AuditLog::query()->where('tenant_id', $tenantId)->delete();

        if ($comboIds->isNotEmpty()) {
            DB::table('combo_product')->whereIn('combo_id', $comboIds)->delete();
        }

        if ($bundleIds->isNotEmpty()) {
            DB::table('bundle_product')->whereIn('bundle_id', $bundleIds)->delete();
        }

        if ($productIds->isNotEmpty()) {
            DB::table('ingredient_product')->whereIn('product_id', $productIds)->delete();
            Extra::query()->whereIn('product_id', $productIds)->delete();
        }

        DailyMenu::query()->whereIn('id', $menuIds)->delete();
        Combo::query()->whereIn('id', $comboIds)->delete();
        Bundle::query()->whereIn('id', $bundleIds)->delete();
        Product::query()->whereIn('id', $productIds)->delete();
        Ingredient::query()->where('tenant_id', $tenantId)->delete();
    }

    private function seedIngredients(int $tenantId): array
    {
        $items = [
            ['name' => 'Pan brioche', 'additional_price' => 350, 'stock_quantity' => 120, 'unit_cost' => 380],
            ['name' => 'Medallon smash', 'additional_price' => 2200, 'stock_quantity' => 90, 'unit_cost' => 1800],
            ['name' => 'Queso cheddar', 'additional_price' => 650, 'stock_quantity' => 80, 'unit_cost' => 520],
            ['name' => 'Mozzarella', 'additional_price' => 700, 'stock_quantity' => 75, 'unit_cost' => 560],
            ['name' => 'Panceta crocante', 'additional_price' => 900, 'stock_quantity' => 60, 'unit_cost' => 720],
            ['name' => 'Cebolla caramelizada', 'additional_price' => 500, 'stock_quantity' => 4, 'unit_cost' => 310],
            ['name' => 'Tomate fresco', 'additional_price' => 250, 'stock_quantity' => 65, 'unit_cost' => 180],
            ['name' => 'Lechuga mantecosa', 'additional_price' => 250, 'stock_quantity' => 45, 'unit_cost' => 160],
            ['name' => 'Pepinillos', 'additional_price' => 300, 'stock_quantity' => 55, 'unit_cost' => 220],
            ['name' => 'Salsa burger', 'additional_price' => 180, 'stock_quantity' => 50, 'unit_cost' => 90],
            ['name' => 'Tortilla de trigo', 'additional_price' => 450, 'stock_quantity' => 80, 'unit_cost' => 320],
            ['name' => 'Pollo crispy', 'additional_price' => 1800, 'stock_quantity' => 70, 'unit_cost' => 1350],
            ['name' => 'Papas baston', 'additional_price' => 1200, 'stock_quantity' => 130, 'unit_cost' => 780],
            ['name' => 'Batata frita', 'additional_price' => 1350, 'stock_quantity' => 90, 'unit_cost' => 900],
            ['name' => 'Helado artesanal', 'additional_price' => 1400, 'stock_quantity' => 40, 'unit_cost' => 900],
            ['name' => 'Brownie', 'additional_price' => 1100, 'stock_quantity' => 30, 'unit_cost' => 700],
            ['name' => 'Coca Cola 500ml', 'additional_price' => 1600, 'stock_quantity' => 120, 'unit_cost' => 1200],
            ['name' => 'Limonada casera', 'additional_price' => 1500, 'stock_quantity' => 50, 'unit_cost' => 850],
            ['name' => 'Mayonesa de ajo', 'additional_price' => 200, 'stock_quantity' => 3, 'unit_cost' => 90],
            ['name' => 'Barbacoa ahumada', 'additional_price' => 240, 'stock_quantity' => 5, 'unit_cost' => 110],
        ];

        $records = [];
        foreach ($items as $item) {
            $records[$item['name']] = Ingredient::query()->create([
                'tenant_id' => $tenantId,
                ...$item,
                'is_active' => true,
            ]);
        }

        return $records;
    }

    private function seedProducts(int $tenantId, array $ingredients): array
    {
        $products = [
            [
                'name' => 'Smash Clasica',
                'description' => 'Doble medallon, cheddar, pepinillos y salsa burger.',
                'category' => 'burgers',
                'base_price' => 8900,
                'prep_min' => 18,
                'stock_quantity' => 45,
                'min_stock_quantity' => 12,
                'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Pan brioche', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Medallon smash', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Queso cheddar', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Pepinillos', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Salsa burger', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Extra cheddar', 'additional_price' => 900],
                    ['name' => 'Doble panceta', 'additional_price' => 1400],
                    ['name' => 'Medallon extra', 'additional_price' => 2200],
                ],
            ],
            [
                'name' => 'Bacon Melt',
                'description' => 'Burger con cheddar, panceta crocante y cebolla caramelizada.',
                'category' => 'burgers',
                'base_price' => 10400,
                'prep_min' => 20,
                'stock_quantity' => 35,
                'min_stock_quantity' => 10,
                'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Pan brioche', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Medallon smash', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Queso cheddar', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Panceta crocante', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Cebolla caramelizada', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Barbacoa ahumada', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Panceta extra', 'additional_price' => 1100],
                    ['name' => 'Cebolla caramelizada extra', 'additional_price' => 650],
                ],
            ],
            [
                'name' => 'Green Burger',
                'description' => 'Burger fresca con lechuga, tomate, cheddar y mayonesa de ajo.',
                'category' => 'burgers',
                'base_price' => 9300,
                'prep_min' => 16,
                'stock_quantity' => 30,
                'min_stock_quantity' => 8,
                'image_url' => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Pan brioche', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Medallon smash', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Lechuga mantecosa', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Tomate fresco', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Queso cheddar', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Mayonesa de ajo', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Tomate extra', 'additional_price' => 300],
                    ['name' => 'Cheddar extra', 'additional_price' => 900],
                ],
            ],
            [
                'name' => 'Wrap Crispy Chicken',
                'description' => 'Wrap de pollo crispy con lechuga, tomate y mayonesa de ajo.',
                'category' => 'wraps',
                'base_price' => 8600,
                'prep_min' => 14,
                'stock_quantity' => 28,
                'min_stock_quantity' => 8,
                'image_url' => 'https://images.unsplash.com/photo-1608039755401-742074f0548d?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Tortilla de trigo', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Pollo crispy', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Lechuga mantecosa', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Tomate fresco', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Mayonesa de ajo', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Pollo extra', 'additional_price' => 1800],
                    ['name' => 'Barbacoa', 'additional_price' => 300],
                ],
            ],
            [
                'name' => 'Wrap Cheddar Bacon',
                'description' => 'Wrap potente con pollo crispy, cheddar y panceta crocante.',
                'category' => 'wraps',
                'base_price' => 9400,
                'prep_min' => 15,
                'stock_quantity' => 24,
                'min_stock_quantity' => 7,
                'image_url' => 'https://images.unsplash.com/photo-1626074353765-517a681e40be?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Tortilla de trigo', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Pollo crispy', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Queso cheddar', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Panceta crocante', 'is_default' => true, 'is_removable' => true],
                    ['name' => 'Barbacoa ahumada', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Cheddar extra', 'additional_price' => 900],
                    ['name' => 'Papas al lado', 'additional_price' => 1900],
                ],
            ],
            [
                'name' => 'Papas Clasicas',
                'description' => 'Papas baston crocantes con sal especiada.',
                'category' => 'sides',
                'base_price' => 4200,
                'prep_min' => 10,
                'stock_quantity' => 60,
                'min_stock_quantity' => 18,
                'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Papas baston', 'is_default' => true, 'is_removable' => false],
                ],
                'extras' => [
                    ['name' => 'Cheddar y bacon', 'additional_price' => 1800],
                    ['name' => 'Mayonesa de ajo', 'additional_price' => 350],
                ],
            ],
            [
                'name' => 'Papas Rusticas',
                'description' => 'Batatas fritas con dip de mayonesa de ajo.',
                'category' => 'sides',
                'base_price' => 4800,
                'prep_min' => 11,
                'stock_quantity' => 42,
                'min_stock_quantity' => 14,
                'image_url' => 'https://images.unsplash.com/photo-1518013431117-eb1465fa5752?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Batata frita', 'is_default' => true, 'is_removable' => false],
                    ['name' => 'Mayonesa de ajo', 'is_default' => true, 'is_removable' => true],
                ],
                'extras' => [
                    ['name' => 'Dip barbacoa', 'additional_price' => 350],
                ],
            ],
            [
                'name' => 'Coca Cola 500',
                'description' => 'Bebida fria individual.',
                'category' => 'drinks',
                'base_price' => 2300,
                'prep_min' => 2,
                'stock_quantity' => 95,
                'min_stock_quantity' => 24,
                'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Coca Cola 500ml', 'is_default' => true, 'is_removable' => false],
                ],
                'extras' => [],
            ],
            [
                'name' => 'Limonada Menta Jengibre',
                'description' => 'Limonada casera bien fria, ideal para combo.',
                'category' => 'drinks',
                'base_price' => 2500,
                'prep_min' => 4,
                'stock_quantity' => 36,
                'min_stock_quantity' => 10,
                'image_url' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Limonada casera', 'is_default' => true, 'is_removable' => false],
                ],
                'extras' => [],
            ],
            [
                'name' => 'Brownie Tibio',
                'description' => 'Brownie humedo con porcion generosa.',
                'category' => 'postre',
                'base_price' => 3900,
                'prep_min' => 6,
                'stock_quantity' => 20,
                'min_stock_quantity' => 6,
                'image_url' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Brownie', 'is_default' => true, 'is_removable' => false],
                ],
                'extras' => [
                    ['name' => 'Bocha de helado', 'additional_price' => 1600],
                ],
            ],
            [
                'name' => 'Helado Doble',
                'description' => 'Dos bochas de helado artesanal.',
                'category' => 'postre',
                'base_price' => 4300,
                'prep_min' => 5,
                'stock_quantity' => 18,
                'min_stock_quantity' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?auto=format&fit=crop&w=900&q=80',
                'ingredients' => [
                    ['name' => 'Helado artesanal', 'is_default' => true, 'is_removable' => false],
                ],
                'extras' => [
                    ['name' => 'Salsa chocolate', 'additional_price' => 500],
                ],
            ],
        ];

        $records = [];
        foreach ($products as $item) {
            $product = Product::query()->create([
                'tenant_id' => $tenantId,
                'name' => $item['name'],
                'description' => $item['description'],
                'category' => $item['category'],
                'base_price' => $item['base_price'],
                'prep_min' => $item['prep_min'],
                'stock_quantity' => $item['stock_quantity'],
                'min_stock_quantity' => $item['min_stock_quantity'],
                'image_url' => $item['image_url'],
                'is_active' => true,
            ]);

            $product->ingredients()->sync(
                collect($item['ingredients'])->mapWithKeys(function (array $link) use ($ingredients) {
                    $ingredient = $ingredients[$link['name']];

                    return [
                        $ingredient->id => [
                            'is_default' => $link['is_default'],
                            'is_removable' => $link['is_removable'],
                            'additional_price' => (float) ($link['additional_price'] ?? 0),
                        ],
                    ];
                })->all()
            );

            foreach ($item['extras'] as $extra) {
                Extra::query()->create([
                    'tenant_id' => $tenantId,
                    'product_id' => $product->id,
                    'name' => $extra['name'],
                    'additional_price' => $extra['additional_price'],
                    'is_active' => true,
                ]);
            }

            $records[$item['name']] = $product->fresh(['ingredients', 'extras']);
        }

        return $records;
    }

    private function seedCombos(int $tenantId, array $products): array
    {
        $items = [
            [
                'name' => 'Combo Smash Night',
                'description' => 'Smash Clasica + Papas Clasicas + Coca Cola 500.',
                'base_price' => 14800,
                'image_url' => 'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=900&q=80',
                'products' => [
                    ['name' => 'Smash Clasica', 'quantity' => 1],
                    ['name' => 'Papas Clasicas', 'quantity' => 1],
                    ['name' => 'Coca Cola 500', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Combo Bacon Lovers',
                'description' => 'Bacon Melt con papas rusticas y limonada.',
                'base_price' => 17100,
                'image_url' => 'https://images.unsplash.com/photo-1561758033-d89a9ad46330?auto=format&fit=crop&w=900&q=80',
                'products' => [
                    ['name' => 'Bacon Melt', 'quantity' => 1],
                    ['name' => 'Papas Rusticas', 'quantity' => 1],
                    ['name' => 'Limonada Menta Jengibre', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Lunch Wrap',
                'description' => 'Wrap Crispy Chicken + papas clasicas.',
                'base_price' => 11800,
                'image_url' => 'https://images.unsplash.com/photo-1626074353765-517a681e40be?auto=format&fit=crop&w=900&q=80',
                'products' => [
                    ['name' => 'Wrap Crispy Chicken', 'quantity' => 1],
                    ['name' => 'Papas Clasicas', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Sweet Closing',
                'description' => 'Brownie Tibio + Helado Doble para cerrar arriba.',
                'base_price' => 7600,
                'image_url' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=900&q=80',
                'products' => [
                    ['name' => 'Brownie Tibio', 'quantity' => 1],
                    ['name' => 'Helado Doble', 'quantity' => 1],
                ],
            ],
        ];

        $records = [];
        foreach ($items as $item) {
            $combo = Combo::query()->create([
                'tenant_id' => $tenantId,
                'name' => $item['name'],
                'description' => $item['description'],
                'base_price' => $item['base_price'],
                'image_url' => $item['image_url'],
                'is_active' => true,
            ]);

            $combo->products()->sync(
                collect($item['products'])->mapWithKeys(function (array $link) use ($products) {
                    return [
                        $products[$link['name']]->id => ['quantity' => $link['quantity']],
                    ];
                })->all()
            );

            $records[$item['name']] = $combo->fresh('products');
        }

        return $records;
    }

    private function seedBundles(int $tenantId, array $products): array
    {
        $items = [
            [
                'name' => 'Pack Pareja',
                'description' => 'Dos burgers + dos papas + dos bebidas a precio fijo.',
                'pricing_mode' => 'fixed_price',
                'fixed_price' => 28900,
                'discount_percentage' => 0,
                'products' => [
                    ['name' => 'Smash Clasica', 'quantity' => 2],
                    ['name' => 'Papas Clasicas', 'quantity' => 2],
                    ['name' => 'Coca Cola 500', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Family Burger Box',
                'description' => 'Tres burgers, dos wraps y cuatro guarniciones con descuento.',
                'pricing_mode' => 'discount_percentage',
                'fixed_price' => null,
                'discount_percentage' => 14,
                'products' => [
                    ['name' => 'Smash Clasica', 'quantity' => 2],
                    ['name' => 'Bacon Melt', 'quantity' => 1],
                    ['name' => 'Wrap Crispy Chicken', 'quantity' => 2],
                    ['name' => 'Papas Clasicas', 'quantity' => 2],
                    ['name' => 'Papas Rusticas', 'quantity' => 2],
                ],
            ],
            [
                'name' => 'Postre para Compartir',
                'description' => 'Brownie + helado doble con precio especial.',
                'pricing_mode' => 'fixed_price',
                'fixed_price' => 7400,
                'discount_percentage' => 0,
                'products' => [
                    ['name' => 'Brownie Tibio', 'quantity' => 1],
                    ['name' => 'Helado Doble', 'quantity' => 1],
                ],
            ],
        ];

        $records = [];
        foreach ($items as $item) {
            $bundle = Bundle::query()->create([
                'tenant_id' => $tenantId,
                'name' => $item['name'],
                'description' => $item['description'],
                'pricing_mode' => $item['pricing_mode'],
                'fixed_price' => $item['fixed_price'],
                'discount_percentage' => $item['discount_percentage'],
                'is_active' => true,
            ]);

            $bundle->products()->sync(
                collect($item['products'])->mapWithKeys(function (array $link) use ($products) {
                    return [
                        $products[$link['name']]->id => ['quantity' => $link['quantity']],
                    ];
                })->all()
            );

            $records[$item['name']] = $bundle->fresh('products');
        }

        return $records;
    }

    private function seedDailyMenus(int $tenantId, array $products, array $combos): void
    {
        $today = now();

        $lunch = DailyMenu::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Menu ejecutivo del mediodia',
            'description' => 'Rotacion pensada para almuerzo rapido con ticket medio controlado.',
            'image_url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80',
            'is_active' => true,
            'slot' => 'lunch',
            'weekdays' => [1, 2, 3, 4, 5, 6],
            'active_from' => $today->copy()->subDays(2),
            'active_to' => $today->copy()->addDays(20),
            'priority' => 90,
        ]);

        $lunch->items()->createMany([
            [
                'item_type' => 'product',
                'item_id' => $products['Wrap Crispy Chicken']->id,
                'promo_price' => 7900,
                'sort_order' => 1,
            ],
            [
                'item_type' => 'combo',
                'item_id' => $combos['Lunch Wrap']->id,
                'promo_price' => 10800,
                'sort_order' => 2,
            ],
        ]);

        $dinner = DailyMenu::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Especial noche burger',
            'description' => 'Lo que mejor rota de noche, con foco en burgers y combos.',
            'image_url' => 'https://images.unsplash.com/photo-1520072959219-c595dc870360?auto=format&fit=crop&w=900&q=80',
            'is_active' => true,
            'slot' => 'dinner',
            'weekdays' => [4, 5, 6, 7],
            'active_from' => $today->copy()->subDays(1),
            'active_to' => $today->copy()->addDays(30),
            'priority' => 120,
        ]);

        $dinner->items()->createMany([
            [
                'item_type' => 'combo',
                'item_id' => $combos['Combo Smash Night']->id,
                'promo_price' => 13900,
                'sort_order' => 1,
            ],
            [
                'item_type' => 'product',
                'item_id' => $products['Bacon Melt']->id,
                'promo_price' => 9800,
                'sort_order' => 2,
            ],
        ]);

        $allDay = DailyMenu::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Dulce del dia',
            'description' => 'Oferta transversal para subir postres en cualquier franja.',
            'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80',
            'is_active' => true,
            'slot' => 'all_day',
            'weekdays' => null,
            'active_from' => $today->copy()->subDays(7),
            'active_to' => $today->copy()->addDays(15),
            'priority' => 80,
        ]);

        $allDay->items()->createMany([
            [
                'item_type' => 'combo',
                'item_id' => $combos['Sweet Closing']->id,
                'promo_price' => 6900,
                'sort_order' => 1,
            ],
            [
                'item_type' => 'product',
                'item_id' => $products['Brownie Tibio']->id,
                'promo_price' => 3400,
                'sort_order' => 2,
            ],
        ]);
    }

    private function seedCustomerProfiles(int $tenantId): void
    {
        $profiles = [
            [
                'customer_key' => 'cliente delivery',
                'display_name' => 'Cliente Delivery',
                'last_address' => 'Av. Corrientes 1540, CABA',
                'is_blocked' => false,
                'notes' => 'Usuario demo del frontend cliente.',
            ],
            [
                'customer_key' => 'lucia benitez',
                'display_name' => 'Lucia Benitez',
                'last_address' => 'Amenabar 2241, Belgrano',
                'is_blocked' => false,
                'notes' => 'Pide wraps y paga por Mercado Pago.',
            ],
            [
                'customer_key' => 'martin acosta',
                'display_name' => 'Martin Acosta',
                'last_address' => 'Yerbal 934, Caballito',
                'is_blocked' => false,
                'notes' => 'Cliente frecuente de combos familiares.',
            ],
            [
                'customer_key' => 'sofia lorenzo',
                'display_name' => 'Sofia Lorenzo',
                'last_address' => 'Zapiola 1782, Colegiales',
                'is_blocked' => true,
                'notes' => 'Perfil bloqueado para pruebas de soporte.',
            ],
        ];

        foreach ($profiles as $profile) {
            CustomerProfile::query()->create([
                'tenant_id' => $tenantId,
                ...$profile,
            ]);
        }
    }

    private function seedOrders(int $tenantId, \Illuminate\Support\Collection $users, array $products, array $combos, array $bundles): void
    {
        $employeeId = $users['empleado@delivery.local']->id;
        $driverId = $users['repartidor@delivery.local']->id;

        $orders = [
            [
                'customer' => 'Cliente Delivery',
                'address' => 'Av. Corrientes 1540, CABA',
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'cash_received' => 20000,
                'change_amount' => 1700,
                'status' => 'pendiente',
                'employee_id' => null,
                'driver_id' => null,
                'eta_min' => 34,
                'created_at' => now()->subMinutes(20),
                'items' => [
                    [
                        'type' => 'combo',
                        'combo_id' => $combos['Combo Smash Night']->id,
                        'name' => 'Combo Smash Night',
                        'qty' => 1,
                        'unit_base_price' => 14800,
                        'unit_extras_total' => 1500,
                        'unit_price' => 16300,
                        'line_subtotal' => 14800,
                        'line_extras_total' => 1500,
                        'line_total' => 16300,
                        'sub_items' => [
                            [
                                'product_id' => $products['Smash Clasica']->id,
                                'name' => 'Smash Clasica',
                                'qty' => 1,
                                'excluded_ingredients' => [],
                                'extras' => [
                                    ['name' => 'Extra cheddar', 'additional_price' => 900],
                                    ['name' => 'Doble panceta', 'additional_price' => 600],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'product',
                        'product_id' => $products['Brownie Tibio']->id,
                        'name' => 'Brownie Tibio',
                        'qty' => 1,
                        'unit_base_price' => 3900,
                        'unit_extras_total' => 0,
                        'unit_price' => 3900,
                        'line_subtotal' => 3900,
                        'line_extras_total' => 0,
                        'line_total' => 3900,
                        'excluded_ingredients' => [],
                        'extras' => [],
                    ],
                ],
            ],
            [
                'customer' => 'Lucia Benitez',
                'address' => 'Amenabar 2241, Belgrano',
                'payment_method' => 'mercado_pago',
                'payment_status' => 'paid',
                'cash_received' => null,
                'change_amount' => 0,
                'status' => 'preparando',
                'employee_id' => $employeeId,
                'driver_id' => null,
                'eta_min' => 26,
                'created_at' => now()->subMinutes(42),
                'items' => [
                    [
                        'type' => 'combo',
                        'combo_id' => $combos['Lunch Wrap']->id,
                        'name' => 'Lunch Wrap',
                        'qty' => 2,
                        'unit_base_price' => 11800,
                        'unit_extras_total' => 700,
                        'unit_price' => 12500,
                        'line_subtotal' => 23600,
                        'line_extras_total' => 1400,
                        'line_total' => 25000,
                        'sub_items' => [
                            [
                                'product_id' => $products['Wrap Crispy Chicken']->id,
                                'name' => 'Wrap Crispy Chicken',
                                'qty' => 1,
                                'excluded_ingredients' => [],
                                'extras' => [
                                    ['name' => 'Barbacoa', 'additional_price' => 300],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'customer' => 'Martin Acosta',
                'address' => 'Yerbal 934, Caballito',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'cash_received' => 30000,
                'change_amount' => 1100,
                'status' => 'listo',
                'employee_id' => $employeeId,
                'driver_id' => $driverId,
                'eta_min' => 18,
                'created_at' => now()->subHour(),
                'items' => [
                    [
                        'type' => 'bundle',
                        'bundle_id' => $bundles['Pack Pareja']->id,
                        'name' => 'Pack Pareja',
                        'qty' => 1,
                        'unit_base_price' => 28900,
                        'unit_extras_total' => 0,
                        'unit_price' => 28900,
                        'line_subtotal' => 28900,
                        'line_extras_total' => 0,
                        'line_total' => 28900,
                        'bundle_items' => [
                            ['product_id' => $products['Smash Clasica']->id, 'qty' => 2],
                            ['product_id' => $products['Papas Clasicas']->id, 'qty' => 2],
                            ['product_id' => $products['Coca Cola 500']->id, 'qty' => 2],
                        ],
                        'bundle_snapshot' => [
                            'pricing_mode' => 'fixed_price',
                            'fixed_price' => 28900,
                            'discount_percentage' => 0,
                        ],
                    ],
                ],
            ],
            [
                'customer' => 'Camila Sueldo',
                'address' => 'Bonpland 1445, Palermo',
                'payment_method' => 'mercado_pago',
                'payment_status' => 'paid',
                'cash_received' => null,
                'change_amount' => 0,
                'status' => 'en_camino',
                'employee_id' => $employeeId,
                'driver_id' => $driverId,
                'eta_min' => 12,
                'created_at' => now()->subHours(2),
                'items' => [
                    [
                        'type' => 'product',
                        'product_id' => $products['Bacon Melt']->id,
                        'name' => 'Bacon Melt',
                        'qty' => 1,
                        'unit_base_price' => 10400,
                        'unit_extras_total' => 1100,
                        'unit_price' => 11500,
                        'line_subtotal' => 10400,
                        'line_extras_total' => 1100,
                        'line_total' => 11500,
                        'excluded_ingredients' => [],
                        'extras' => [
                            ['name' => 'Panceta extra', 'additional_price' => 1100],
                        ],
                    ],
                    [
                        'type' => 'product',
                        'product_id' => $products['Limonada Menta Jengibre']->id,
                        'name' => 'Limonada Menta Jengibre',
                        'qty' => 1,
                        'unit_base_price' => 2500,
                        'unit_extras_total' => 0,
                        'unit_price' => 2500,
                        'line_subtotal' => 2500,
                        'line_extras_total' => 0,
                        'line_total' => 2500,
                        'excluded_ingredients' => [],
                        'extras' => [],
                    ],
                ],
            ],
            [
                'customer' => 'Cliente Delivery',
                'address' => 'Av. Corrientes 1540, CABA',
                'payment_method' => 'mercado_pago',
                'payment_status' => 'paid',
                'cash_received' => null,
                'change_amount' => 0,
                'status' => 'entregado',
                'employee_id' => $employeeId,
                'driver_id' => $driverId,
                'eta_min' => 0,
                'created_at' => now()->subDay(),
                'items' => [
                    [
                        'type' => 'product',
                        'product_id' => $products['Green Burger']->id,
                        'name' => 'Green Burger',
                        'qty' => 1,
                        'unit_base_price' => 9300,
                        'unit_extras_total' => 900,
                        'unit_price' => 10200,
                        'line_subtotal' => 9300,
                        'line_extras_total' => 900,
                        'line_total' => 10200,
                        'excluded_ingredients' => [],
                        'extras' => [
                            ['name' => 'Cheddar extra', 'additional_price' => 900],
                        ],
                    ],
                    [
                        'type' => 'product',
                        'product_id' => $products['Papas Rusticas']->id,
                        'name' => 'Papas Rusticas',
                        'qty' => 1,
                        'unit_base_price' => 4800,
                        'unit_extras_total' => 350,
                        'unit_price' => 5150,
                        'line_subtotal' => 4800,
                        'line_extras_total' => 350,
                        'line_total' => 5150,
                        'excluded_ingredients' => [],
                        'extras' => [
                            ['name' => 'Dip barbacoa', 'additional_price' => 350],
                        ],
                    ],
                ],
            ],
            [
                'customer' => 'Lucia Benitez',
                'address' => 'Amenabar 2241, Belgrano',
                'payment_method' => 'cash',
                'payment_status' => 'refunded',
                'cash_received' => 10000,
                'change_amount' => 1400,
                'status' => 'cancelado',
                'employee_id' => $employeeId,
                'driver_id' => null,
                'eta_min' => 0,
                'created_at' => now()->subDays(2),
                'items' => [
                    [
                        'type' => 'product',
                        'product_id' => $products['Helado Doble']->id,
                        'name' => 'Helado Doble',
                        'qty' => 2,
                        'unit_base_price' => 4300,
                        'unit_extras_total' => 0,
                        'unit_price' => 4300,
                        'line_subtotal' => 8600,
                        'line_extras_total' => 0,
                        'line_total' => 8600,
                        'excluded_ingredients' => [],
                        'extras' => [],
                    ],
                ],
            ],
        ];

        foreach ($orders as $payload) {
            $subtotal = (float) collect($payload['items'])->sum('line_subtotal');
            $extrasTotal = (float) collect($payload['items'])->sum('line_extras_total');
            $total = round($subtotal + $extrasTotal, 2);

            $order = Order::query()->create([
                'tenant_id' => $tenantId,
                'customer' => $payload['customer'],
                'address' => $payload['address'],
                'payment_method' => $payload['payment_method'],
                'payment_status' => $payload['payment_status'],
                'items' => $payload['items'],
                'subtotal' => $subtotal,
                'extras_total' => $extrasTotal,
                'total' => $total,
                'cash_received' => $payload['cash_received'],
                'change_amount' => $payload['change_amount'],
                'status' => $payload['status'],
                'employee_id' => $payload['employee_id'],
                'driver_id' => $payload['driver_id'],
                'eta_min' => $payload['eta_min'],
                'created_at' => $payload['created_at'],
                'updated_at' => $payload['created_at'],
            ]);

            foreach ($payload['items'] as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['type'] === 'product' ? ($item['product_id'] ?? null) : null,
                    'combo_id' => $item['type'] === 'combo' ? ($item['combo_id'] ?? null) : null,
                    'bundle_id' => $item['type'] === 'bundle' ? ($item['bundle_id'] ?? null) : null,
                    'snapshot_name' => $item['name'],
                    'qty' => $item['qty'],
                    'unit_base_price' => $item['unit_base_price'],
                    'unit_extras_total' => $item['unit_extras_total'],
                    'unit_price' => $item['unit_price'],
                    'line_subtotal' => $item['line_subtotal'],
                    'line_extras_total' => $item['line_extras_total'],
                    'line_total' => $item['line_total'],
                    'modifiers' => [
                        'type' => $item['type'],
                        'excluded_ingredients' => $item['excluded_ingredients'] ?? [],
                        'extras' => $item['extras'] ?? [],
                        'sub_items' => $item['sub_items'] ?? [],
                        'bundle_items' => $item['bundle_items'] ?? [],
                        'bundle_snapshot' => $item['bundle_snapshot'] ?? null,
                    ],
                    'created_at' => $payload['created_at'],
                    'updated_at' => $payload['created_at'],
                ]);
            }
        }
    }

    private function seedAuditLogs(int $tenantId, \Illuminate\Support\Collection $users): void
    {
        $items = [
            [
                'user_id' => $users['admin@delivery.local']->id,
                'action' => 'catalog.seeded',
                'entity_type' => 'tenant',
                'entity_id' => $tenantId,
                'metadata' => [
                    'source' => 'database_seeder',
                    'note' => 'Carga inicial demo de catalogo y operacion.',
                ],
            ],
            [
                'user_id' => $users['empleado@delivery.local']->id,
                'action' => 'order.prepared_demo',
                'entity_type' => 'order',
                'entity_id' => 2,
                'metadata' => [
                    'source' => 'database_seeder',
                    'note' => 'Pedido demo en cocina para panel de empleado.',
                ],
            ],
        ];

        foreach ($items as $item) {
            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                ...$item,
            ]);
        }
    }
}

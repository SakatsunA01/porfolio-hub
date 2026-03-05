<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Commerce;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class EcommerceDemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $commerce = Commerce::query()->updateOrCreate(
            ['name' => 'Axis Tech'],
            ['name' => 'Axis Tech']
        );

        $categories = collect([
            'Audio',
            'Carga',
            'Accesorios',
            'Huerta Interior',
            'Bioacustica',
            'Energia Suave',
        ])->mapWithKeys(function (string $name) use ($commerce) {
            $category = Category::query()->updateOrCreate(
                [
                    'commerce_id' => $commerce->id,
                    'name' => $name,
                ],
                [
                    'commerce_id' => $commerce->id,
                    'name' => $name,
                ]
            );

            return [$name => $category];
        });

        $products = [
            [
                'category' => 'Audio',
                'name' => 'Axis Noise-Cancelling Headphones',
                'slug' => 'axis-noise-cancelling-headphones',
                'description_short' => 'Auriculares inalámbricos de escucha serena y cancelación activa.',
                'description_long' => 'Diseñados para espacios de trabajo y escucha consciente, con materiales suaves, aislamiento equilibrado y una presencia discreta.',
                'description' => 'Auriculares inalámbricos de escucha serena y cancelación activa.',
                'price' => 285000,
                'type' => 'permanent',
                'stock_global' => null,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Materiales', 'description' => 'Aluminio cepillado y espuma viscoelástica.'],
                    ['name' => 'Autonomía', 'description' => 'Hasta 30 horas de reproducción.'],
                    ['name' => 'Producción', 'description' => 'Terminación mate y ensamble de baja reflexión visual.'],
                ],
                'variants' => [
                    ['size' => 'Compacto', 'stock' => 8, 'sku' => 'AX-HP-CMP'],
                    ['size' => 'Amplio', 'stock' => 14, 'sku' => 'AX-HP-AMP'],
                ],
                'images' => [
                    ['image_path' => '../assets/Axis Noise-Cancelling Headphones/auri1.png', 'position' => 0, 'is_primary' => true],
                    ['image_path' => '../assets/Axis Noise-Cancelling Headphones/Gemini_Generated_Image_1vda0l1vda0l1vda.png', 'position' => 1, 'is_primary' => false],
                    ['image_path' => '../assets/Axis Noise-Cancelling Headphones/Gemini_Generated_Image_nq8j6knq8j6knq8j.png', 'position' => 2, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Audio',
                'name' => 'Axis Bluetooth Speaker',
                'slug' => 'axis-bluetooth-speaker',
                'description_short' => 'Parlante portátil con volumen cálido y geometría contenida.',
                'description_long' => 'Un objeto de audio pensado para acompañar interiores con una presencia silenciosa, cuerpo mate y sonido 360°.',
                'description' => 'Parlante portátil con volumen cálido y geometría contenida.',
                'price' => 149000,
                'type' => 'limited',
                'stock_global' => 18,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Sonido', 'description' => 'Cobertura 360° para ambientes medianos.'],
                    ['name' => 'Batería', 'description' => 'Hasta 24 horas de reproducción.'],
                    ['name' => 'Edición', 'description' => 'Serie limitada con terminación grafito.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56tepp56tepp56t.png', 'position' => 0, 'is_primary' => true],
                    ['image_path' => '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56teqp56teqp56t.png', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Audio',
                'name' => 'Axis Desk Speaker Mono',
                'slug' => 'axis-desk-speaker-mono',
                'description_short' => 'Pieza compacta para escritorios, estudios y bibliotecas.',
                'description_long' => 'Una caja acústica de escala reducida con presencia sobria, ideal para espacios de lectura y trabajo.',
                'description' => 'Pieza compacta para escritorios, estudios y bibliotecas.',
                'price' => 96000,
                'type' => 'permanent',
                'stock_global' => 11,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Formato', 'description' => 'Cuerpo compacto para escritorio.'],
                    ['name' => 'Uso', 'description' => 'Pensado para ambientes de trabajo y lectura.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56tepp56tepp56t.png', 'position' => 0, 'is_primary' => true],
                ],
            ],
            [
                'category' => 'Carga',
                'name' => 'Axis Wireless Charger',
                'slug' => 'axis-wireless-charger',
                'description_short' => 'Base de carga inalámbrica para superficies limpias y ordenadas.',
                'description_long' => 'Una pieza de apoyo para escritorio o mesa de luz, con perfil bajo, acabado suave y carga rápida silenciosa.',
                'description' => 'Base de carga inalámbrica para superficies limpias y ordenadas.',
                'price' => 79000,
                'type' => 'permanent',
                'stock_global' => null,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Potencia', 'description' => 'Carga rápida de 15W.'],
                    ['name' => 'Material', 'description' => 'Aluminio anodizado y superficie antideslizante.'],
                ],
                'variants' => [
                    ['size' => 'Compacto', 'stock' => 16, 'sku' => 'AX-WC-CMP'],
                    ['size' => 'Extendido', 'stock' => 9, 'sku' => 'AX-WC-EXT'],
                ],
                'images' => [
                    ['image_path' => '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56tetp56tetp56t.png', 'position' => 0, 'is_primary' => true],
                    ['image_path' => '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56teup56teup56t.png', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Carga',
                'name' => 'Axis Charging Tray',
                'slug' => 'axis-charging-tray',
                'description_short' => 'Bandeja de carga para apoyar, ordenar y alimentar varios objetos a la vez.',
                'description_long' => 'Pensada para hall de entrada o escritorio, integra superficie de apoyo, carga y orden visual.',
                'description' => 'Bandeja de carga para apoyar, ordenar y alimentar varios objetos a la vez.',
                'price' => 118000,
                'type' => 'limited',
                'stock_global' => 12,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Capacidad', 'description' => 'Hasta tres dispositivos en simultáneo.'],
                    ['name' => 'Edición', 'description' => 'Producción acotada de temporada.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56teup56teup56t.png', 'position' => 0, 'is_primary' => true],
                ],
            ],
            [
                'category' => 'Accesorios',
                'name' => 'Axis Smartwatch',
                'slug' => 'axis-smartwatch',
                'description_short' => 'Reloj inteligente con cuerpo sobrio y lectura limpia.',
                'description_long' => 'Una pieza de uso diario con notificaciones discretas, materiales nobles y foco en bienestar.',
                'description' => 'Reloj inteligente con cuerpo sobrio y lectura limpia.',
                'price' => 249000,
                'type' => 'preorder',
                'stock_global' => 0,
                'preorder_shipping_date' => '2026-08-15',
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Pantalla', 'description' => 'AMOLED 1.4".'],
                    ['name' => 'Despacho', 'description' => 'Preventa activa con entrega estimada en agosto.'],
                ],
                'variants' => [
                    ['size' => '38 mm', 'stock' => 0, 'sku' => 'AX-SW-38'],
                    ['size' => '42 mm', 'stock' => 0, 'sku' => 'AX-SW-42'],
                ],
                'images' => [
                    ['image_path' => '../assets/Axis Smartwatch/Gemini_Generated_Image_p56temp56temp56t.png', 'position' => 0, 'is_primary' => true],
                    ['image_path' => '../assets/Axis Smartwatch/Gemini_Generated_Image_p56teop56teop56t.png', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Accesorios',
                'name' => 'Axis Desk Stand',
                'slug' => 'axis-desk-stand',
                'description_short' => 'Soporte de apoyo para teléfono o tableta, pensado para mesas serenas.',
                'description_long' => 'Una estructura sobria para elevar la mirada, ordenar la superficie y acompañar rutinas de trabajo.',
                'description' => 'Soporte de apoyo para teléfono o tableta, pensado para mesas serenas.',
                'price' => 54000,
                'type' => 'permanent',
                'stock_global' => 22,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Material', 'description' => 'Aluminio satinado y base antideslizante.'],
                    ['name' => 'Uso', 'description' => 'Compatible con teléfono y tableta compacta.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Smartwatch/Gemini_Generated_Image_p56teop56teop56t.png', 'position' => 0, 'is_primary' => true],
                ],
            ],
            [
                'category' => 'Accesorios',
                'name' => 'Axis Cable Set',
                'slug' => 'axis-cable-set',
                'description_short' => 'Set de cables textiles para carga y escritorio.',
                'description_long' => 'Una selección breve de cables revestidos para acompañar objetos cotidianos con mejor tacto y menos ruido.',
                'description' => 'Set de cables textiles para carga y escritorio.',
                'price' => 38000,
                'type' => 'limited',
                'stock_global' => 26,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Contenido', 'description' => 'USB-C, Lightning y cable de carga corta.'],
                    ['name' => 'Serie', 'description' => 'Lote acotado de tono arena.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Wireless Charger/Gemini_Generated_Image_p56tetp56tetp56t.png', 'position' => 0, 'is_primary' => true],
                ],
            ],
            [
                'category' => 'Huerta Interior',
                'name' => 'Luma Glasshouse Mini',
                'slug' => 'luma-glasshouse-mini',
                'description_short' => 'Mini invernadero de interior con sensores de humedad y luz ambiente.',
                'description_long' => 'Pensado para cocinas, estudios y escritorios, combina estructura liviana, ventilacion manual y seguimiento basico de humedad para hierbas y brotes de uso diario.',
                'description' => 'Mini invernadero de interior con sensores de humedad y luz ambiente.',
                'price' => 172000,
                'type' => 'preorder',
                'stock_global' => 0,
                'preorder_shipping_date' => '2026-09-10',
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Cultivo', 'description' => 'Ideal para brotes, aromaticas y hojas pequenas.'],
                    ['name' => 'Sistema', 'description' => 'Sensor interno de humedad con aviso visual discreto.'],
                    ['name' => 'Materiales', 'description' => 'Vidrio, aluminio mate y base de madera sellada.'],
                ],
                'variants' => [
                    ['size' => '2 bandejas', 'stock' => 0, 'sku' => 'LGM-2B'],
                    ['size' => '4 bandejas', 'stock' => 0, 'sku' => 'LGM-4B'],
                ],
                'images' => [
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Greenhouse%20%28Unsplash%29.jpg', 'position' => 0, 'is_primary' => true],
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cozy%20greenhouse%20%28Unsplash%29.jpg', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Huerta Interior',
                'name' => 'Nido Terrarium Sensor',
                'slug' => 'nido-terrarium-sensor',
                'description_short' => 'Terrario con cupula de vidrio y lectura ambiental para espacios pequenos.',
                'description_long' => 'Un objeto decorativo y funcional para mesas serenas, con seguimiento simple de temperatura y humedad para suculentas, musgo y especies de bajo mantenimiento.',
                'description' => 'Terrario con cupula de vidrio y lectura ambiental para espacios pequenos.',
                'price' => 89000,
                'type' => 'permanent',
                'stock_global' => 16,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Lectura', 'description' => 'Indicador silencioso de humedad y temperatura.'],
                    ['name' => 'Uso', 'description' => 'Pensado para suculentas, musgos y esquejes pequenos.'],
                    ['name' => 'Formato', 'description' => 'Cupula compacta para bibliotecas, mesas y recibidores.'],
                ],
                'variants' => [
                    ['size' => 'Compacto', 'stock' => 10, 'sku' => 'NTS-CMP'],
                    ['size' => 'Mediano', 'stock' => 6, 'sku' => 'NTS-MED'],
                ],
                'images' => [
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Glass%20terrarium%20and%20succulent%20%28Unsplash%29.jpg', 'position' => 0, 'is_primary' => true],
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Succulent%20plant%20Terrarium.jpg', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Huerta Interior',
                'name' => 'Terra Pulse Planter',
                'slug' => 'terra-pulse-planter',
                'description_short' => 'Maceta de autorriego con lectura tactil de humedad y deposito interno.',
                'description_long' => 'Un contenedor sobrio para balcones o interiores luminosos, con reserva de agua, cuerpo mineral y una pequena pieza tactil para controlar el nivel sin recurrir a pantallas.',
                'description' => 'Maceta de autorriego con lectura tactil de humedad y deposito interno.',
                'price' => 64000,
                'type' => 'permanent',
                'stock_global' => 28,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Riego', 'description' => 'Deposito oculto con autonomia de varios dias.'],
                    ['name' => 'Material', 'description' => 'Terracota porosa con base sellada.'],
                    ['name' => 'Lectura', 'description' => 'Indicador mecanico para revisar humedad sin app.'],
                ],
                'variants' => [
                    ['size' => '20 cm', 'stock' => 16, 'sku' => 'TPP-20'],
                    ['size' => '28 cm', 'stock' => 12, 'sku' => 'TPP-28'],
                ],
                'images' => [
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Terracotta%20Pots%20%28Unsplash%29.jpg', 'position' => 0, 'is_primary' => true],
                ],
            ],
            [
                'category' => 'Bioacustica',
                'name' => 'Grove Bamboo Speaker',
                'slug' => 'grove-bamboo-speaker',
                'description_short' => 'Parlante de mesa con carcasa de bambu tejido y sonido cercano.',
                'description_long' => 'Una pieza acustica pensada para lectura, cocina o estudio, con cuerpo de bambu, controles reducidos y una respuesta amable para escuchar sin invadir el ambiente.',
                'description' => 'Parlante de mesa con carcasa de bambu tejido y sonido cercano.',
                'price' => 158000,
                'type' => 'limited',
                'stock_global' => 14,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Cuerpo', 'description' => 'Exterior en bambu tejido y base amortiguada.'],
                    ['name' => 'Audio', 'description' => 'Perfil calido para voz, jazz y ambient.'],
                    ['name' => 'Serie', 'description' => 'Lote corto con terminacion natural.'],
                ],
                'variants' => [],
                'images' => [
                    ['image_path' => '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56tepp56tepp56t.png', 'position' => 0, 'is_primary' => true],
                    ['image_path' => '../assets/Axis Bluetooth Speaker/Gemini_Generated_Image_p56teqp56teqp56t.png', 'position' => 1, 'is_primary' => false],
                ],
            ],
            [
                'category' => 'Energia Suave',
                'name' => 'Helio Panel Desk',
                'slug' => 'helio-panel-desk',
                'description_short' => 'Panel solar compacto para alimentar luces suaves y pequenos accesorios.',
                'description_long' => 'Pensado para balcones, terrazas y talleres pequenos, ofrece carga auxiliar para lamparas, hubs o sensores de interior con una presencia sobria y facil de mover.',
                'description' => 'Panel solar compacto para alimentar luces suaves y pequenos accesorios.',
                'price' => 132000,
                'type' => 'permanent',
                'stock_global' => 19,
                'preorder_shipping_date' => null,
                'is_active' => true,
                'is_dropship' => false,
                'details' => [
                    ['name' => 'Salida', 'description' => 'USB-C y salida auxiliar para bajo consumo.'],
                    ['name' => 'Uso', 'description' => 'Ideal para sensores, luces y carga de apoyo.'],
                    ['name' => 'Montaje', 'description' => 'Base inclinable para escritorio o baranda.'],
                ],
                'variants' => [
                    ['size' => '30 W', 'stock' => 11, 'sku' => 'HPD-30'],
                    ['size' => '45 W', 'stock' => 8, 'sku' => 'HPD-45'],
                ],
                'images' => [
                    ['image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Portable%20solar%20panel.jpg', 'position' => 0, 'is_primary' => true],
                ],
            ],
        ];

        $seededProducts = collect($products)->map(function (array $productData) use ($commerce, $categories) {
            $category = $categories[$productData['category']];

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'commerce_id' => $commerce->id,
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'description_short' => $productData['description_short'],
                    'description_long' => $productData['description_long'],
                    'price' => $productData['price'],
                    'type' => $productData['type'],
                    'stock_global' => $productData['stock_global'],
                    'preorder_shipping_date' => $productData['preorder_shipping_date'],
                    'is_active' => $productData['is_active'],
                    'is_dropship' => $productData['is_dropship'],
                    'details' => $productData['details'],
                ]
            );

            ProductVariant::query()->where('product_id', $product->id)->delete();
            ProductImage::query()->where('product_id', $product->id)->delete();

            collect($productData['variants'])->each(function (array $variant) use ($product) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'size' => $variant['size'],
                    'stock' => $variant['stock'],
                    'sku' => $variant['sku'],
                ]);
            });

            collect($productData['images'])->each(function (array $image) use ($product) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'image_path' => $image['image_path'],
                    'position' => $image['position'],
                    'is_primary' => $image['is_primary'],
                ]);
            });

            return $product->fresh(['variants']);
        })->keyBy('slug');

        $orders = [
            [
                'order_number' => 'ORD-00031',
                'user_name' => 'Lucía Fernández',
                'user_email' => 'lucia@example.com',
                'user_phone' => '+54 11 4567 8890',
                'shipping_address' => 'Av. Libertador 2450',
                'shipping_city' => 'Buenos Aires',
                'shipping_postal_code' => '1425',
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
                'mercado_pago_id' => 'MP-00031',
                'items' => [
                    [
                        'product_slug' => 'axis-noise-cancelling-headphones',
                        'variant_size' => 'Amplio',
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'order_number' => 'ORD-00032',
                'user_name' => 'Martín Suárez',
                'user_email' => 'martin@example.com',
                'user_phone' => '+54 11 4199 2200',
                'shipping_address' => 'Arcos 1930',
                'shipping_city' => 'Buenos Aires',
                'shipping_postal_code' => '1426',
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'mercado_pago_id' => 'MP-00032',
                'items' => [
                    [
                        'product_slug' => 'axis-bluetooth-speaker',
                        'variant_size' => null,
                        'quantity' => 1,
                    ],
                    [
                        'product_slug' => 'axis-cable-set',
                        'variant_size' => null,
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'order_number' => 'ORD-00033',
                'user_name' => 'Paula Ríos',
                'user_email' => 'paula@example.com',
                'user_phone' => '+54 11 4988 1100',
                'shipping_address' => 'Migueletes 1500',
                'shipping_city' => 'Buenos Aires',
                'shipping_postal_code' => '1426',
                'payment_status' => 'paid',
                'order_status' => 'shipped',
                'mercado_pago_id' => 'MP-00033',
                'items' => [
                    [
                        'product_slug' => 'axis-smartwatch',
                        'variant_size' => '42 mm',
                        'quantity' => 1,
                    ],
                ],
            ],
        ];

        collect($orders)->each(function (array $orderData) use ($seededProducts) {
            $lineItems = collect($orderData['items'])->map(function (array $item) use ($seededProducts) {
                /** @var \App\Models\Product $product */
                $product = $seededProducts[$item['product_slug']];
                $variant = $item['variant_size']
                    ? $product->variants->firstWhere('size', $item['variant_size'])
                    : null;

                $subtotal = (float) $product->price * $item['quantity'];

                return [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];
            });

            $order = Order::query()->updateOrCreate(
                ['order_number' => $orderData['order_number']],
                [
                    'user_name' => $orderData['user_name'],
                    'user_email' => $orderData['user_email'],
                    'user_phone' => $orderData['user_phone'],
                    'shipping_address' => $orderData['shipping_address'],
                    'shipping_city' => $orderData['shipping_city'],
                    'shipping_postal_code' => $orderData['shipping_postal_code'],
                    'total' => $lineItems->sum('subtotal'),
                    'payment_status' => $orderData['payment_status'],
                    'order_status' => $orderData['order_status'],
                    'mercado_pago_id' => $orderData['mercado_pago_id'],
                ]
            );

            OrderItem::query()->where('order_id', $order->id)->delete();

            $lineItems->each(function (array $item) use ($order) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'variant_id' => $item['variant']?->id,
                    'product_name_snapshot' => $item['product']->name,
                    'price_snapshot' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            });
        });
    }
}

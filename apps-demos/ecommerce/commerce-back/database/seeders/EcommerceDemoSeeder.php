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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcommerceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $commerce = Commerce::query()->updateOrCreate(
            ['name' => 'Axis Tech'],
            ['name' => 'Axis Tech']
        );

        $this->resetCatalogData();

        $categories = collect([
            'Ropa',
            'Tecnologia',
            'Herramientas',
            'Jardin Tech',
            'Accesorios',
        ])->mapWithKeys(function (string $name) use ($commerce) {
            $category = Category::query()->create([
                'commerce_id' => $commerce->id,
                'name' => $name,
            ]);

            return [$name => $category];
        });

        $products = $this->catalogProducts();

        $seededProducts = collect($products)->map(function (array $productData) use ($commerce, $categories) {
            $category = $categories[$productData['category']];

            $product = Product::query()->create([
                'commerce_id' => $commerce->id,
                'category_id' => $category->id,
                'name' => $productData['name'],
                'slug' => $productData['slug'],
                'description' => $productData['description_short'],
                'description_short' => $productData['description_short'],
                'description_long' => $productData['description_long'],
                'price' => $productData['price'],
                'type' => $productData['type'],
                'stock_global' => $productData['stock_global'],
                'preorder_shipping_date' => $productData['preorder_shipping_date'],
                'is_active' => true,
                'is_dropship' => false,
                'details' => $productData['details'],
            ]);

            collect($productData['variants'])->each(function (array $variant) use ($product) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'size' => $variant['size'],
                    'stock' => $variant['stock'],
                    'sku' => $variant['sku'],
                ]);
            });

            collect($productData['images'])->values()->each(function (string $image, int $index) use ($product) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'image_path' => $image,
                    'position' => $index,
                    'is_primary' => $index === 0,
                ]);
            });

            return $product->fresh(['variants']);
        })->keyBy('slug');

        $this->seedDemoOrders($seededProducts);
    }

    protected function resetCatalogData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        OrderItem::query()->delete();
        Order::query()->delete();
        ProductImage::query()->delete();
        ProductVariant::query()->delete();
        Product::query()->withTrashed()->forceDelete();
        Category::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function catalogProducts(): array
    {
        return [
            // ROPA
            $this->makeProduct('Ropa', 'Remera Base Essential', 28900, 'permanent', [
                'Remera premium de algodon peinado para uso diario.',
                'Corte regular con costuras reforzadas, tacto suave y colores neutros para combinar con cualquier look urbano.',
                ['Negro', 'Blanco', 'Gris'], ['S', 'M', 'L', 'XL'], 'RM-BAS',
                [
                    'https://images.pexels.com/photos/9558583/pexels-photo-9558583.jpeg',
                    'https://images.pexels.com/photos/6311392/pexels-photo-6311392.jpeg',
                ],
                [
                    ['name' => 'Material', 'description' => 'Algodon 24/1 de alta durabilidad.'],
                    ['name' => 'Calce', 'description' => 'Regular fit unisex.'],
                ],
            ]),
            $this->makeProduct('Ropa', 'Remera Oversize Mono', 34900, 'limited', [
                'Remera oversize minimal con cuello reforzado.',
                'Pensada para un estilo relajado, con trama compacta y caida amplia para climas templados.',
                ['Arena', 'Negro', 'Azul Noche'], ['M', 'L', 'XL'], 'RM-OVR',
                [
                    'https://images.pexels.com/photos/6311584/pexels-photo-6311584.jpeg',
                    'https://images.pexels.com/photos/6311393/pexels-photo-6311393.jpeg',
                ],
                [
                    ['name' => 'Serie', 'description' => 'Edicion limitada por temporada.'],
                    ['name' => 'Corte', 'description' => 'Oversize con hombro caido.'],
                ],
            ]),
            $this->makeProduct('Ropa', 'Gorra Street Cap', 19900, 'permanent', [
                'Gorra urbana con visera curva y cierre regulable.',
                'Diseño sobrio con panel frontal estructurado y bordado discreto para uso diario.',
                ['Negro', 'Beige', 'Verde Musgo'], ['Unico'], 'GOR-ST',
                [
                    'https://images.pexels.com/photos/1124468/pexels-photo-1124468.jpeg',
                    'https://images.pexels.com/photos/844867/pexels-photo-844867.jpeg',
                ],
                [
                    ['name' => 'Formato', 'description' => 'Snapback ajustable.'],
                    ['name' => 'Uso', 'description' => 'Interior y exterior.'],
                ],
            ]),
            $this->makeProduct('Ropa', 'Pantalon Cargo Urban', 59900, 'permanent', [
                'Pantalon cargo elastizado para movilidad diaria.',
                'Incluye bolsillos funcionales, tejido resistente y ajuste comodo para uso urbano o taller.',
                ['Negro', 'Oliva', 'Grafito'], ['S', 'M', 'L', 'XL'], 'PAN-CAR',
                [
                    'https://images.pexels.com/photos/1598505/pexels-photo-1598505.jpeg',
                    'https://images.pexels.com/photos/4041682/pexels-photo-4041682.jpeg',
                ],
                [
                    ['name' => 'Tejido', 'description' => 'Gabardina con elastano.'],
                    ['name' => 'Bolsillos', 'description' => '6 compartimentos utilitarios.'],
                ],
            ]),
            $this->makeProduct('Ropa', 'Pantalon Chino Tech', 55900, 'preorder', [
                'Pantalon chino con tela tecnica antiarrugas.',
                'Prenda versatil de oficina a calle con cintura comoda y secado rapido.',
                ['Arena', 'Azul Marino'], ['M', 'L', 'XL'], 'PAN-TEC',
                [
                    'https://images.pexels.com/photos/45055/pexels-photo-45055.jpeg',
                ],
                [
                    ['name' => 'Tecnologia', 'description' => 'Tela antiarrugas de secado rapido.'],
                    ['name' => 'Preventa', 'description' => 'Envio estimado en 15 dias.'],
                ],
                '2026-04-20'
            ]),
            $this->makeProduct('Ropa', 'Gorra Tech Mesh', 21900, 'permanent', [
                'Gorra respirable con panel trasero microperforado.',
                'Ideal para uso diario y movilidad urbana, con visera firme y ajuste suave.',
                ['Negro', 'Gris'], ['Unico'], 'GOR-MSH',
                [
                    'https://images.pexels.com/photos/1464625/pexels-photo-1464625.jpeg',
                ],
                [
                    ['name' => 'Tejido', 'description' => 'Mesh tecnico de secado rapido.'],
                    ['name' => 'Cierre', 'description' => 'Regulable posterior.'],
                ],
            ]),
            $this->makeProduct('Ropa', 'Pantalon Jogger Utility', 61900, 'limited', [
                'Jogger tecnico con bolsillos funcionales para uso activo.',
                'Combina comodidad y resistencia para jornadas de trabajo, estudio o taller.',
                ['Negro', 'Oliva'], ['S', 'M', 'L', 'XL'], 'PAN-JOG',
                [
                    'https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg',
                ],
                [
                    ['name' => 'Serie', 'description' => 'Lote de temporada con stock acotado.'],
                    ['name' => 'Calce', 'description' => 'Slim relajado con cintura elastica.'],
                ],
            ]),

            // TECNOLOGIA
            $this->makeProduct('Tecnologia', 'Smartwatch Pulse Pro', 189000, 'permanent', [
                'Smartwatch de salud y productividad con pantalla AMOLED.',
                'Monitoreo de actividad, notificaciones y bateria de larga duracion con diseño sobrio.',
                ['Negro', 'Plata'], ['42mm', '46mm'], 'SW-PRO',
                [
                    'https://images.pexels.com/photos/437037/pexels-photo-437037.jpeg',
                    'https://images.pexels.com/photos/267394/pexels-photo-267394.jpeg',
                ],
                [
                    ['name' => 'Pantalla', 'description' => 'AMOLED de alto brillo.'],
                    ['name' => 'Bateria', 'description' => 'Hasta 7 dias de autonomia.'],
                ],
            ]),
            $this->makeProduct('Tecnologia', 'Smartwatch Active Mini', 149000, 'limited', [
                'Smartwatch compacto orientado a entrenamiento diario.',
                'Ligero, resistente al agua y con medicion continua de ritmo cardiaco.',
                ['Negro', 'Coral'], ['40mm'], 'SW-MIN',
                [
                    'https://images.pexels.com/photos/393047/pexels-photo-393047.jpeg',
                ],
                [
                    ['name' => 'Serie', 'description' => 'Lote acotado deportivo.'],
                ],
            ]),
            $this->makeProduct('Tecnologia', 'Tablet Nova 11', 329000, 'permanent', [
                'Tablet de 11 pulgadas para estudio y trabajo remoto.',
                'Pantalla nítida, audio stereo y compatibilidad con stylus para toma de notas.',
                ['Grafito', 'Plata'], ['128GB', '256GB'], 'TAB-N11',
                [
                    'https://images.pexels.com/photos/1334597/pexels-photo-1334597.jpeg',
                    'https://images.pexels.com/photos/4348404/pexels-photo-4348404.jpeg',
                ],
                [
                    ['name' => 'Pantalla', 'description' => '11" IPS con baja reflexion.'],
                    ['name' => 'Almacenamiento', 'description' => '128GB y 256GB.'],
                ],
            ]),
            $this->makeProduct('Tecnologia', 'Tablet Nova Air 8', 259000, 'preorder', [
                'Tablet compacta para lectura, consumo y movilidad.',
                'Formato portable de 8 pulgadas con bateria optimizada para jornada completa.',
                ['Grafito', 'Beige'], ['64GB', '128GB'], 'TAB-A8',
                [
                    'https://images.pexels.com/photos/5082566/pexels-photo-5082566.jpeg',
                ],
                [
                    ['name' => 'Portabilidad', 'description' => 'Peso reducido para transporte diario.'],
                    ['name' => 'Preventa', 'description' => 'Envio estimado en 20 dias.'],
                ],
                '2026-04-28'
            ]),
            $this->makeProduct('Tecnologia', 'Auriculares Sonic Air', 119000, 'permanent', [
                'Auriculares inalambricos con cancelacion de ruido hibrida.',
                'Audio claro para llamadas y musica, con estuche compacto de carga rapida.',
                ['Negro', 'Blanco', 'Azul'], ['Unico'], 'AU-SAIR',
                [
                    'https://images.pexels.com/photos/3394650/pexels-photo-3394650.jpeg',
                    'https://images.pexels.com/photos/3780681/pexels-photo-3780681.jpeg',
                ],
                [
                    ['name' => 'Audio', 'description' => 'Drivers de alta definicion.'],
                    ['name' => 'Microfono', 'description' => 'Cancelacion de ruido ambiente.'],
                ],
            ]),
            $this->makeProduct('Tecnologia', 'Tablet Studio Pro 13', 489000, 'limited', [
                'Tablet de alto rendimiento para diseno, dibujo y productividad.',
                'Pantalla amplia de 13 pulgadas, excelente respuesta tactil y compatibilidad con lapiz digital.',
                ['Grafito', 'Plata'], ['256GB', '512GB'], 'TAB-S13',
                [
                    'https://images.pexels.com/photos/356056/pexels-photo-356056.jpeg',
                ],
                [
                    ['name' => 'Pantalla', 'description' => '13" IPS de alta definicion.'],
                    ['name' => 'Serie', 'description' => 'Edicion limitada de lanzamiento.'],
                ],
            ]),
            $this->makeProduct('Tecnologia', 'Auriculares Studio Bamboo', 169000, 'preorder', [
                'Auriculares over-ear con carcasas de bambu y perfil sonoro calido.',
                'Pensados para escucha prolongada, con almohadillas suaves y conectividad multipunto.',
                ['Bambu Natural', 'Negro Mate'], ['Unico'], 'AU-BMB',
                [
                    'https://images.pexels.com/photos/1649771/pexels-photo-1649771.jpeg',
                ],
                [
                    ['name' => 'Material', 'description' => 'Carcasa compuesta con acabado bambu.'],
                    ['name' => 'Preventa', 'description' => 'Ingreso de lote premium en abril.'],
                ],
                '2026-04-25'
            ]),

            // HERRAMIENTAS TECH
            $this->makeProduct('Herramientas', 'Kit Precision 58 en 1', 45900, 'permanent', [
                'Kit de destornilladores de precision para reparacion electronica.',
                'Incluye puntas magneticas, mango ergonomico y estuche compacto para servicio tecnico.',
                ['Negro', 'Gris'], ['58 piezas'], 'KT-58',
                [
                    'https://images.pexels.com/photos/577210/pexels-photo-577210.jpeg',
                    'https://images.pexels.com/photos/162553/keys-workshop-mechanic-tools-162553.jpeg',
                ],
                [
                    ['name' => 'Uso', 'description' => 'Celulares, tablets y notebooks.'],
                    ['name' => 'Puntas', 'description' => 'Torx, Philips, Pentalobe, Triwing.'],
                ],
            ]),
            $this->makeProduct('Herramientas', 'Estacion Soldadura Micro SMD', 189000, 'limited', [
                'Estacion de soldadura digital para microsoldado SMD.',
                'Control de temperatura estable y punta de alta precision para placas moviles.',
                ['Negro'], ['60W', '90W'], 'SMD-EST',
                [
                    'https://images.pexels.com/photos/3825581/pexels-photo-3825581.jpeg',
                ],
                [
                    ['name' => 'Control', 'description' => 'Temperatura digital calibrable.'],
                    ['name' => 'Serie', 'description' => 'Stock técnico limitado.'],
                ],
            ]),
            $this->makeProduct('Herramientas', 'Multimetro TechLab X', 87900, 'permanent', [
                'Multimetro avanzado para diagnostico de placas y fuentes.',
                'Lectura precisa de voltaje, continuidad y resistencia para laboratorio técnico.',
                ['Negro', 'Amarillo'], ['Unico'], 'MLT-X',
                [
                    'https://images.pexels.com/photos/257736/pexels-photo-257736.jpeg',
                ],
                [
                    ['name' => 'Precision', 'description' => 'Rango automatico con backlight.'],
                ],
            ]),
            $this->makeProduct('Herramientas', 'Pistola Calor Compact', 69900, 'permanent', [
                'Pistola de calor para despegado de pantallas y componentes.',
                'Flujo de aire regulable y control de temperatura para trabajo delicado.',
                ['Negro'], ['1200W'], 'CAL-CMP',
                [
                    'https://images.pexels.com/photos/162568/drill-mill-milling-machine-162568.jpeg',
                ],
                [
                    ['name' => 'Aplicacion', 'description' => 'Pantallas, adhesivos y termocontraibles.'],
                ],
            ]),
            $this->makeProduct('Herramientas', 'Microscopio USB Repair', 134000, 'preorder', [
                'Microscopio USB para inspeccion de soldaduras y pistas.',
                'Zoom digital con base estable para tareas de microsoldado y QA.',
                ['Negro'], ['1080p', '2K'], 'MIC-USB',
                [
                    'https://images.pexels.com/photos/2280568/pexels-photo-2280568.jpeg',
                ],
                [
                    ['name' => 'Preventa', 'description' => 'Ingreso de lote tecnico en abril.'],
                ],
                '2026-04-15'
            ]),
            $this->makeProduct('Herramientas', 'Kit Apertura Pantallas Pro', 38900, 'permanent', [
                'Set de apertura para celulares y tablets sin marcar superficies.',
                'Incluye plectros, palancas de nylon, ventosa y pinzas antiestaticas.',
                ['Negro', 'Azul'], ['Kit Completo'], 'KIT-OPN',
                [
                    'https://images.pexels.com/photos/4792509/pexels-photo-4792509.jpeg',
                ],
                [
                    ['name' => 'Aplicacion', 'description' => 'Apertura segura de equipos moviles.'],
                    ['name' => 'Contenido', 'description' => 'Herramientas no conductivas.'],
                ],
            ]),
            $this->makeProduct('Herramientas', 'Fuente DC Bench Repair', 174000, 'limited', [
                'Fuente regulable para diagnostico y reparacion de placas.',
                'Control preciso de voltaje y corriente para pruebas en telefonia y electronica ligera.',
                ['Negro'], ['30V 5A'], 'FUE-DC',
                [
                    'https://images.pexels.com/photos/3846021/pexels-photo-3846021.jpeg',
                ],
                [
                    ['name' => 'Control', 'description' => 'Regulacion fina de salida DC.'],
                    ['name' => 'Serie', 'description' => 'Stock reducido para laboratorio.'],
                ],
            ]),

            // JARDIN TECH
            $this->makeProduct('Jardin Tech', 'Maceta Inteligente Flora One', 79000, 'permanent', [
                'Maceta con sensor de humedad y riego asistido.',
                'Ideal para interior, con alertas simples y deposito de agua interno.',
                ['Terracota', 'Blanco'], ['Pequena', 'Mediana'], 'MAC-FL1',
                [
                    'https://images.pexels.com/photos/2123482/pexels-photo-2123482.jpeg',
                    'https://images.pexels.com/photos/4505165/pexels-photo-4505165.jpeg',
                ],
                [
                    ['name' => 'Sensor', 'description' => 'Humedad y temperatura ambiente.'],
                ],
            ]),
            $this->makeProduct('Jardin Tech', 'Maceta Inteligente Terra Sync', 99000, 'limited', [
                'Maceta premium con app de seguimiento de riego.',
                'Diseño minimal con conectividad bluetooth para recordatorios y estados.',
                ['Gris', 'Arena'], ['Mediana', 'Grande'], 'MAC-TSY',
                [
                    'https://images.pexels.com/photos/1407305/pexels-photo-1407305.jpeg',
                ],
                [
                    ['name' => 'Conectividad', 'description' => 'Sincronizacion por bluetooth.'],
                ],
            ]),
            $this->makeProduct('Jardin Tech', 'Invernadero Smart Grow Mini', 229000, 'permanent', [
                'Invernadero domestico con luz LED y control de humedad.',
                'Modulo compacto para hierbas y plantines con ciclo automatizado.',
                ['Negro', 'Blanco'], ['Mini'], 'INV-MIN',
                [
                    'https://images.pexels.com/photos/6231998/pexels-photo-6231998.jpeg',
                ],
                [
                    ['name' => 'Control', 'description' => 'Humedad y luz programable.'],
                ],
            ]),
            $this->makeProduct('Jardin Tech', 'Invernadero Urban Lab', 349000, 'preorder', [
                'Invernadero vertical para balcones y terrazas tech.',
                'Sistema modular con ventilacion activa y monitoreo por panel frontal.',
                ['Negro'], ['2 Niveles', '3 Niveles'], 'INV-URL',
                [
                    'https://images.pexels.com/photos/772803/pexels-photo-772803.jpeg',
                ],
                [
                    ['name' => 'Preventa', 'description' => 'Entrega estimada en mayo.'],
                ],
                '2026-05-10'
            ]),
            $this->makeProduct('Jardin Tech', 'Maceta BioSense Duo', 119000, 'permanent', [
                'Doble maceta inteligente con control de humedad independiente.',
                'Pensada para escritorios y livings, con alertas visuales y recarga simple.',
                ['Arena', 'Terracota', 'Gris'], ['Doble'], 'MAC-BIO',
                [
                    'https://images.pexels.com/photos/1084199/pexels-photo-1084199.jpeg',
                ],
                [
                    ['name' => 'Sensores', 'description' => 'Monitoreo dual por compartimento.'],
                    ['name' => 'Uso', 'description' => 'Ideal para hierbas aromaticas y suculentas.'],
                ],
            ]),
            $this->makeProduct('Jardin Tech', 'Invernadero Cube One', 279000, 'limited', [
                'Invernadero modular cubico para cultivo indoor tecnologico.',
                'Incluye iluminacion LED y ventilacion interna automatizada para ciclos estables.',
                ['Blanco', 'Grafito'], ['Cube'], 'INV-CB1',
                [
                    'https://images.pexels.com/photos/4750270/pexels-photo-4750270.jpeg',
                ],
                [
                    ['name' => 'Iluminacion', 'description' => 'LED de espectro balanceado.'],
                    ['name' => 'Serie', 'description' => 'Produccion corta por lote.'],
                ],
            ]),
            $this->makeProduct('Accesorios', 'Sensor Kit Cultivo', 42000, 'permanent', [
                'Kit de sensores para macetas e invernaderos inteligentes.',
                'Permite monitorear humedad, luz y temperatura de forma simple.',
                ['Negro'], ['Pack x3', 'Pack x5'], 'SN-KIT',
                [
                    'https://images.pexels.com/photos/3913025/pexels-photo-3913025.jpeg',
                ],
                [
                    ['name' => 'Compatibilidad', 'description' => 'Macetas e invernaderos bluetooth.'],
                ],
            ]),
            $this->makeProduct('Accesorios', 'Modulo Riego Link', 69900, 'permanent', [
                'Modulo de riego programable para macetas inteligentes.',
                'Permite automatizar ciclos cortos y mantener trazabilidad basica de consumo.',
                ['Negro', 'Blanco'], ['Unico'], 'RIE-LNK',
                [
                    'https://images.pexels.com/photos/409127/pexels-photo-409127.jpeg',
                ],
                [
                    ['name' => 'Conectividad', 'description' => 'Control por app de cultivo.'],
                    ['name' => 'Compatibilidad', 'description' => 'Macetas y microinvernaderos.'],
                ],
            ]),
        ];
    }

    /**
     * @param array{0:string,1:string,2:array<int,string>,3:array<int,string>,4:string,5:array<int,string>,6:array<int,array{name:string,description:string}>,7?:string} $setup
     * @return array<string, mixed>
     */
    protected function makeProduct(string $category, string $name, float $price, string $type, array $setup): array
    {
        [$shortDescription, $longDescription, $colors, $sizes, $skuPrefix, $images, $details, $preorderDate] = array_pad($setup, 8, null);

        $slug = Str::slug($name);
        $variants = [];
        $stockTotal = 0;

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $stock = $type === 'preorder' ? 0 : random_int(3, 16);
                $stockTotal += $stock;
                $variants[] = [
                    'size' => "{$color} / {$size}",
                    'stock' => $stock,
                    'sku' => "{$skuPrefix}-" . Str::upper(Str::slug($color, '')) . '-' . Str::upper(Str::slug($size, '')),
                ];
            }
        }

        return [
            'category' => $category,
            'name' => $name,
            'slug' => $slug,
            'description_short' => $shortDescription,
            'description_long' => $longDescription,
            'price' => $price,
            'type' => $type,
            'stock_global' => $variants === [] ? $stockTotal : null,
            'preorder_shipping_date' => $type === 'preorder' ? ($preorderDate ?? now()->addDays(20)->toDateString()) : null,
            'details' => $details,
            'variants' => $variants,
            'images' => $images,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<string, \App\Models\Product> $seededProducts
     */
    protected function seedDemoOrders($seededProducts): void
    {
        $orders = [
            [
                'order_number' => 'ORD-01001',
                'user_name' => 'Sofia Morales',
                'user_email' => 'sofia@example.com',
                'user_phone' => '+54 11 4411 0099',
                'shipping_address' => 'Av. Cabildo 2100',
                'shipping_city' => 'Buenos Aires',
                'shipping_postal_code' => '1428',
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
                'mercado_pago_id' => 'MP-1001',
                'items' => [
                    ['product_slug' => 'remera-base-essential', 'variant_size' => 'Negro / M', 'quantity' => 2],
                    ['product_slug' => 'auriculares-sonic-air', 'variant_size' => 'Negro / Unico', 'quantity' => 1],
                ],
            ],
            [
                'order_number' => 'ORD-01002',
                'user_name' => 'Lucas Vidal',
                'user_email' => 'lucas@example.com',
                'user_phone' => '+54 11 5533 1020',
                'shipping_address' => 'Calle 52 #120',
                'shipping_city' => 'La Plata',
                'shipping_postal_code' => '1900',
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'mercado_pago_id' => 'MP-1002',
                'items' => [
                    ['product_slug' => 'kit-precision-58-en-1', 'variant_size' => 'Negro / 58 piezas', 'quantity' => 1],
                    ['product_slug' => 'multimetro-techlab-x', 'variant_size' => 'Amarillo / Unico', 'quantity' => 1],
                ],
            ],
            [
                'order_number' => 'ORD-01003',
                'user_name' => 'Valentina Ruiz',
                'user_email' => 'valentina@example.com',
                'user_phone' => '+54 11 4700 9800',
                'shipping_address' => 'Mitre 877',
                'shipping_city' => 'Rosario',
                'shipping_postal_code' => '2000',
                'payment_status' => 'paid',
                'order_status' => 'shipped',
                'mercado_pago_id' => 'MP-1003',
                'items' => [
                    ['product_slug' => 'maceta-inteligente-flora-one', 'variant_size' => 'Terracota / Mediana', 'quantity' => 1],
                    ['product_slug' => 'invernadero-smart-grow-mini', 'variant_size' => 'Blanco / Mini', 'quantity' => 1],
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

            $order = Order::query()->create([
                'order_number' => $orderData['order_number'],
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
            ]);

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

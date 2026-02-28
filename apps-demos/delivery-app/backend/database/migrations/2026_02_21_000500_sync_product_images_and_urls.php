<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $assets = [
            'Burger Clasica' => [
                'file' => 'burger-clasica.svg',
                'title' => 'Burger Clasica',
                'subtitle' => 'Carne 100% · Pan de papa',
                'c1' => '#f97316',
                'c2' => '#fb7185',
            ],
            'Milanesa Completa' => [
                'file' => 'milanesa-completa.svg',
                'title' => 'Milanesa Completa',
                'subtitle' => 'Lechuga · Tomate · Huevo',
                'c1' => '#22c55e',
                'c2' => '#10b981',
            ],
            'Papas Crocantes' => [
                'file' => 'papas-crocantes.svg',
                'title' => 'Papas Crocantes',
                'subtitle' => 'Doble fritura',
                'c1' => '#f59e0b',
                'c2' => '#f97316',
            ],
            'Coca-Cola 500ml' => [
                'file' => 'coca-cola-500.svg',
                'title' => 'Coca-Cola 500ml',
                'subtitle' => 'Bebida fria',
                'c1' => '#ef4444',
                'c2' => '#b91c1c',
            ],
            'Limonada Natural' => [
                'file' => 'limonada-natural.svg',
                'title' => 'Limonada Natural',
                'subtitle' => 'Menta + limon',
                'c1' => '#84cc16',
                'c2' => '#22c55e',
            ],
            'Cheesecake Frutos Rojos' => [
                'file' => 'cheesecake-frutos-rojos.svg',
                'title' => 'Cheesecake Frutos Rojos',
                'subtitle' => 'Postre de la casa',
                'c1' => '#a855f7',
                'c2' => '#ec4899',
            ],
        ];

        $dir = public_path('images/products');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $baseUrl = rtrim((string) env('DELIVERY_ASSET_BASE_URL', config('app.url', 'http://127.0.0.1:8010')), '/');

        foreach ($assets as $productName => $asset) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $asset['file'];
            File::put($filePath, $this->svgTemplate(
                (string) $asset['title'],
                (string) $asset['subtitle'],
                (string) $asset['c1'],
                (string) $asset['c2']
            ));

            DB::table('products')
                ->where('name', $productName)
                ->update([
                    'image_url' => $baseUrl . '/images/products/' . $asset['file'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $files = [
            'burger-clasica.svg',
            'milanesa-completa.svg',
            'papas-crocantes.svg',
            'coca-cola-500.svg',
            'limonada-natural.svg',
            'cheesecake-frutos-rojos.svg',
        ];

        DB::table('products')
            ->whereIn('name', [
                'Burger Clasica',
                'Milanesa Completa',
                'Papas Crocantes',
                'Coca-Cola 500ml',
                'Limonada Natural',
                'Cheesecake Frutos Rojos',
            ])
            ->update([
                'image_url' => null,
                'updated_at' => now(),
            ]);

        $dir = public_path('images/products');
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    private function svgTemplate(string $title, string $subtitle, string $c1, string $c2): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c1}"/>
      <stop offset="100%" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="900" fill="url(#g)"/>
  <circle cx="1040" cy="140" r="210" fill="rgba(255,255,255,0.12)"/>
  <circle cx="150" cy="760" r="250" fill="rgba(255,255,255,0.10)"/>
  <rect x="80" y="640" width="1040" height="180" rx="0" fill="rgba(15,23,42,0.26)"/>
  <text x="120" y="720" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="64" font-weight="800">{$title}</text>
  <text x="120" y="776" fill="rgba(255,255,255,0.92)" font-family="Inter, Arial, sans-serif" font-size="36" font-weight="500">{$subtitle}</text>
</svg>
SVG;
    }
};


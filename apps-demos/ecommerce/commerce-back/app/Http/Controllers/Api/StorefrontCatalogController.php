<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StorefrontProductResource;
use App\Models\Category;
use App\Models\Commerce;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true)
            ->latest('id')
            ->get();

        return response()->json([
            'data' => StorefrontProductResource::collection($products),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'images', 'variants']);

        return response()->json([
            'data' => new StorefrontProductResource($product),
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->withCount([
                'products as products_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function settings(): JsonResponse
    {
        $commerce = Commerce::query()->first();

        return response()->json([
            'data' => [
                'name' => $commerce?->name ?? 'Tienda',
                'logo_url' => $commerce?->logo_path ? Storage::url($commerce->logo_path) : null,
                'brand_palette' => $commerce?->brand_palette ?? [
                    '#F7F5F0',
                    '#ECE7DF',
                    '#22221F',
                    '#5A5A55',
                    '#4F5D47',
                ],
                'manifesto_text' => $commerce?->manifesto_text,
                'philosophy_text' => $commerce?->philosophy_text,
                'contact_text' => $commerce?->contact_text,
                'team_text' => $commerce?->team_text,
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'intent' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:permanent,limited,preorder'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
        ]);

        $query = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('is_active', true);

        if (!empty($validated['category'])) {
            $categoryName = (string) $validated['category'];
            $query->whereHas('category', fn ($builder) => $builder->where('name', $categoryName));
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['min_price'])) {
            $query->where('price', '>=', (float) $validated['min_price']);
        }

        if (!empty($validated['max_price'])) {
            $query->where('price', '<=', (float) $validated['max_price']);
        }

        $products = $query->get();
        $searchQuery = Str::lower(trim((string) ($validated['q'] ?? '')));
        $intent = Str::lower(trim((string) ($validated['intent'] ?? '')));
        $semanticTerms = $this->semanticTerms($searchQuery, $intent);

        $scored = $products
            ->map(function (Product $product) use ($searchQuery, $semanticTerms) {
                $lexical = $this->lexicalScore($product, $searchQuery);
                $semantic = $this->semanticScore($product, $semanticTerms);
                $score = ($lexical * 0.7) + ($semantic * 0.3);

                $stock = $product->variants->isNotEmpty()
                    ? (int) $product->variants->sum('stock')
                    : (int) ($product->stock_global ?? 0);

                return [
                    'product' => $product,
                    'score' => $score,
                    'stock' => $stock,
                ];
            })
            ->filter(function (array $item) use ($validated) {
                if (($validated['in_stock'] ?? false) === true) {
                    return $item['stock'] > 0 || $item['product']->type === 'preorder';
                }
                return true;
            })
            ->when($searchQuery !== '' || !empty($semanticTerms), fn ($collection) => $collection->filter(fn ($item) => $item['score'] > 0))
            ->sortByDesc('score')
            ->values();

        $intents = $this->intentSuggestions($searchQuery);

        return response()->json([
            'data' => StorefrontProductResource::collection($scored->pluck('product')),
            'meta' => [
                'q' => $searchQuery,
                'intent' => $intent,
                'intents' => $intents,
                'count' => $scored->count(),
            ],
        ]);
    }

    public function socialProof(): JsonResponse
    {
        $completedOrders = Order::query()
            ->where('payment_status', 'paid')
            ->whereIn('order_status', ['confirmed', 'shipped', 'delivered'])
            ->count();

        $lastWeekOrders = Order::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'data' => [
                'rating' => [
                    'average' => 4.8,
                    'count' => max(47, $completedOrders),
                    'updated_at' => now()->toDateString(),
                ],
                'signals' => [
                    'high_demand' => $lastWeekOrders > 8,
                    'orders_last_week' => $lastWeekOrders,
                    'delivery_notice' => 'Entrega estimada en 24-48h segun zona',
                ],
            ],
        ]);
    }

    protected function lexicalScore(Product $product, string $query): float
    {
        if ($query === '') {
            return 1.0;
        }

        $name = Str::lower($product->name);
        $slug = Str::lower((string) $product->slug);
        $skuValues = $product->variants->pluck('sku')->filter()->map(fn ($sku) => Str::lower((string) $sku))->all();

        if (in_array($query, $skuValues, true)) {
            return 3.0;
        }

        if (Str::contains($name, $query)) {
            return 2.5;
        }

        if (Str::contains($slug, Str::slug($query))) {
            return 2.0;
        }

        if (Str::contains(Str::lower((string) $product->category?->name), $query)) {
            return 1.2;
        }

        return 0.0;
    }

    protected function semanticScore(Product $product, array $semanticTerms): float
    {
        if ($semanticTerms === []) {
            return 0.0;
        }

        $haystack = Str::lower(implode(' ', [
            $product->name,
            $product->slug,
            $product->description_short,
            $product->description_long,
            $product->category?->name,
        ]));

        $matches = collect($semanticTerms)->filter(fn ($term) => Str::contains($haystack, $term))->count();

        return $matches > 0 ? min(1.8, $matches * 0.45) : 0.0;
    }

    protected function semanticTerms(string $query, string $intent): array
    {
        $synonyms = [
            'zapatillas' => ['calzado', 'shoes', 'sneaker'],
            'audio' => ['speaker', 'parlante', 'auricular', 'headphones'],
            'carga' => ['charger', 'cargador', 'wireless', 'energia'],
            'accesorios' => ['stand', 'organizador', 'desk'],
            'eco' => ['sustentable', 'organico', 'bio', 'responsable'],
        ];

        $tokens = collect(explode(' ', trim($query)))
            ->filter()
            ->map(fn ($token) => Str::lower($token))
            ->values()
            ->all();

        if ($intent !== '') {
            $tokens[] = $intent;
        }

        $expanded = collect($tokens)
            ->flatMap(function (string $token) use ($synonyms) {
                return [$token, ...($synonyms[$token] ?? [])];
            })
            ->unique()
            ->values()
            ->all();

        return $expanded;
    }

    protected function intentSuggestions(string $query): array
    {
        if ($query === '') {
            return ['Audio', 'Carga', 'Accesorios'];
        }

        $map = [
            'parlante' => ['Audio'],
            'auricular' => ['Audio'],
            'cargador' => ['Carga'],
            'wireless' => ['Carga'],
            'stand' => ['Accesorios'],
            'organizador' => ['Accesorios'],
            'eco' => ['Bioacustica', 'Huerta Interior', 'Energia Suave'],
        ];

        foreach ($map as $token => $suggestions) {
            if (Str::contains($query, $token)) {
                return $suggestions;
            }
        }

        return ['Audio', 'Carga', 'Accesorios'];
    }
}

<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/share/producto/{slug}', function (string $slug) {
    $product = Product::query()
        ->with(['images' => fn ($query) => $query->orderBy('position')])
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $frontendUrl = rtrim((string) env('FRONTEND_URL', ''), '/');
    $productUrl = $frontendUrl !== ''
        ? "{$frontendUrl}/product/{$product->id}"
        : url("/product/{$product->id}");
    $shareUrl = url("/share/producto/{$product->slug}");

    $imagePath = $product->images->first()?->image_path;
    $imageUrl = $imagePath
        ? (Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : url($imagePath))
        : null;

    $title = $product->name;
    $description = Str::limit(
        $product->description_short ?: ($product->description ?: 'Conoce este producto en nuestra tienda.'),
        180
    );

    return response(
        view('share.product', [
            'title' => $title,
            'description' => $description,
            'imageUrl' => $imageUrl,
            'productUrl' => $productUrl,
            'shareUrl' => $shareUrl,
        ]),
        200
    );
});

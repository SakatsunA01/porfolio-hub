<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;

class ProductStockService
{
    /**
     * @throws InsufficientStockException
     */
    public function checkAvailability(Product $product, ?ProductVariant $variant, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw new InsufficientStockException('Quantity must be greater than zero.');
        }

        if ($product->type === 'preorder') {
            if (! $product->preorder_shipping_date) {
                throw new InsufficientStockException('Preorder products require a shipping date.');
            }

            return true;
        }

        if ($this->productHasVariants($product)) {
            $resolvedVariant = $this->resolveVariant($product, $variant);

            if ($resolvedVariant->stock < $quantity) {
                throw new InsufficientStockException('Insufficient stock for the selected variant.');
            }

            return true;
        }

        $availableStock = $product->stock_global ?? 0;

        if ($availableStock < $quantity) {
            throw new InsufficientStockException('Insufficient stock for this product.');
        }

        return true;
    }

    /**
     * @throws InsufficientStockException
     */
    public function decreaseStock(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $this->checkAvailability($product, $variant, $quantity);

        if ($product->type === 'preorder') {
            return;
        }

        if ($this->productHasVariants($product)) {
            $resolvedVariant = $this->resolveVariant($product, $variant);
            $newStock = $resolvedVariant->stock - $quantity;

            if ($newStock < 0) {
                throw new InsufficientStockException('Variant stock cannot be negative.');
            }

            $resolvedVariant->forceFill([
                'stock' => $newStock,
            ])->save();

            return;
        }

        $currentStock = $product->stock_global ?? 0;
        $newStock = $currentStock - $quantity;

        if ($newStock < 0) {
            throw new InsufficientStockException('Product stock cannot be negative.');
        }

        $product->forceFill([
            'stock_global' => $newStock,
        ])->save();
    }

    protected function productHasVariants(Product $product): bool
    {
        if ($product->relationLoaded('variants')) {
            return $product->variants->isNotEmpty();
        }

        return $product->variants()->exists();
    }

    /**
     * @throws InsufficientStockException
     */
    protected function resolveVariant(Product $product, ?ProductVariant $variant): ProductVariant
    {
        if (! $variant) {
            throw new InsufficientStockException('A product variant is required for this product.');
        }

        if ((int) $variant->product_id !== (int) $product->id) {
            throw new InsufficientStockException('The selected variant does not belong to the product.');
        }

        return $variant;
    }
}

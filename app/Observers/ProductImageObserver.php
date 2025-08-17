<?php

namespace App\Observers;

use App\Models\ProductImage;

class ProductImageObserver
{
    /**
     * Handle the ProductImage "created" event.
     */
    public function created(ProductImage $productImage): void
    {
        // Si se marca como principal, desmarcar las demás
        if ($productImage->is_primary) {
            $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->update(['is_primary' => false]);
        }

        // Si no tiene orden, asignar el siguiente disponible
        if (is_null($productImage->sort_order)) {
            $maxOrder = $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->max('sort_order') ?? 0;
            $productImage->update(['sort_order' => $maxOrder + 1]);
        }
    }

    /**
     * Handle the ProductImage "updated" event.
     */
    public function updated(ProductImage $productImage): void
    {
        // Si se marcó como principal, desmarcar las demás
        if ($productImage->wasChanged('is_primary') && $productImage->is_primary) {
            $productImage->product->images()
                ->where('id', '!=', $productImage->id)
                ->update(['is_primary' => false]);
        }
    }

    /**
     * Handle the ProductImage "deleted" event.
     */
    public function deleted(ProductImage $productImage): void
    {
        // Si era la imagen principal, asignar la primera disponible como principal
        if ($productImage->is_primary) {
            $firstImage = $productImage->product->images()
                ->orderBy('sort_order')
                ->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }
    }
} 
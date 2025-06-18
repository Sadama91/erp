<?php

namespace App\Observers;

use App\Models\PurchaseOrderItem;
use App\Models\ProductStock;

class PurchaseOrderItemObserver
{
    /**
     * Handle the PurchaseOrderItem "updated" event.
     *
     * @param  PurchaseOrderItem  $item
     * @return void
     */
    public function updated(PurchaseOrderItem $item)
    {
        // Stel dat we het verschil tussen de oude en nieuwe hoeveelheden willen verwerken:
        $originalQuantity = $item->getOriginal('quantity');
        $newQuantity = $item->quantity;
        $difference = $newQuantity - $originalQuantity;

        // Update de on_the_way_quantity afhankelijk van het verschil
        $stock = ProductStock::firstOrNew(['product_id' => $item->product_id]);
        $newOnTheWay = $stock->on_the_way_quantity + $difference;
        ProductStock::updateStock(
            $item->product_id,
            $newOnTheWay,
            'on_the_way_quantity',
            "PurchaseOrderItem aangepast (verschil: {$difference})",
            auth()->id(),
            ($difference >= 0 ? 'IN' : 'OUT')
        );
    }

    /**
     * Handle the PurchaseOrderItem "deleted" event.
     *
     * @param  PurchaseOrderItem  $item
     * @return void
     */
    public function deleted(PurchaseOrderItem $item)
    {
        // Als een item wordt verwijderd, verminderen we de on_the_way_quantity
        $stock = ProductStock::firstOrNew(['product_id' => $item->product_id]);
        $newOnTheWay = $stock->on_the_way_quantity - $item->quantity;
        ProductStock::updateStock(
            $item->product_id,
            $newOnTheWay,
            'on_the_way_quantity',
            "PurchaseOrderItem verwijderd",
            auth()->id(),
            'OUT'
        );
    }
}

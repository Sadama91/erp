<?php

namespace App\Listeners;

use App\Events\PurchaseOrderPlaced;
use App\Models\ProductStock;

class HandlePurchaseOrderPlaced
{
    public function handle(PurchaseOrderPlaced $event)
    {
        $order = $event->purchaseOrder;

        if ($order->status == 1) {
            foreach ($order->purchaseOrderItems as $item) {
                ProductStock::updateStock(
                    $item->product_id,
                    $item->quantity,
                    'on_the_way_quantity',
                    "Inkooporder #{$order->id} aangemaakt",
                    auth()->id(),
                    'IN'
                );
            }
            // Log de voorraadreservering als audit
            activity()
                ->performedOn($order)
                ->withProperties(['action' => 'reserve_stock'])
                ->log("Voorraad gereserveerd voor inkooporder #{$order->id}");
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\PurchaseOrderProcessed;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Log;

class HandlePurchaseOrderInventoryUpdate
{
    /**
     * Verwerkt de voorraadupdates na ontvangst van een inkooporder.
     *
     * @param  PurchaseOrderProcessed  $event
     * @return void
     */
    public function handle(PurchaseOrderProcessed $event)
    {
        $order = $event->purchaseOrder;
        $countedData = $event->countedData; // Gecontroleerde aantallen per orderitem

        foreach ($order->purchaseOrderItems as $item) {
            if (isset($countedData[$item->id])) {
                $countedQty = (int)$countedData[$item->id];
                $stock = ProductStock::firstOrNew(['product_id' => $item->product_id]);

                // Bereken de nieuwe voorraadwaarden:
                $newOnTheWay = $stock->on_the_way_quantity - $item->quantity;
                $newCurrent  = $stock->current_quantity + $countedQty;

                // Werk de voorraad bij
                ProductStock::updateStock(
                    $item->product_id,
                    $newOnTheWay,
                    'on_the_way_quantity',
                    "Inkooporder {$order->id} verwerkt (onderweg aangepast)",
                    auth()->id(),
                    'OUT'
                );
                ProductStock::updateStock(
                    $item->product_id,
                    $newCurrent,
                    'current_quantity',
                    "Inkooporder {$order->id} verwerkt (ontvangen voorraad)",
                    auth()->id(),
                    'IN'
                );

                // Pas orderitem gegevens aan
                $item->description = "Controle: aangepast van {$item->quantity} naar {$countedQty}";
                $item->quantity = $countedQty;
                $item->save();

                if ($item->product) {
                    $item->product->updateStatusAutomatically();
                }
            }
        }

        // Zet de status van de inkooporder op 'Ontvangen/Compleet'
        $order->status = 2; // 2 = Ontvangen/Compleet
        $order->save();

        Log::info("Voorraadupdate uitgevoerd voor inkooporder #{$order->id}");
    }
}

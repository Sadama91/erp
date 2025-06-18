<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderProcessed
{
    use Dispatchable, SerializesModels;

    public $purchaseOrder;
    public $countedData; // Een array met de getelde hoeveelheden

    /**
     * Create a new event instance.
     *
     * @param PurchaseOrder $purchaseOrder
     * @param array $countedData
     */
    public function __construct(PurchaseOrder $purchaseOrder, array $countedData)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->countedData   = $countedData;
    }
}

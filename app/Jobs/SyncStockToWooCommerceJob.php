<?php

namespace App\Jobs;

use App\Models\ProductStock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncStockToWooCommerceJob extends Job
{
    protected $stock;

    public function __construct(ProductStock $stock)
    {
        $this->stock = $stock;
    }

    public function handle()
    {
        // Haal de voorraad van het product
        $csvContent = "{$this->stock->product->sku},{$this->stock->current_quantity}\n";

        // Verstuur de voorraad naar WooCommerce
        //$response = Http::post('https://yourstore.com/wp-json/woocommerce/v1/csv-upload', [
         //   'csv_content' => $csvContent,
       // ]);

        if ($response->successful()) {
            Log::info('Voorraad succesvol gesynchroniseerd naar WooCommerce voor SKU: ' . $this->stock->product->sku);
        } else {
            Log::error('Fout bij synchronisatie naar WooCommerce voor SKU: ' . $this->stock->product->sku);
        }
    }
}

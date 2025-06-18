<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'product_stock'; // Zorg ervoor dat de tabelnaam correct is.

    protected $fillable = [
        'product_id',
        'current_quantity',
        'reserved_quantity',
        'blocked_quantity',
        'on_the_way_quantity',
    ];

    // Zorg ervoor dat dit de juiste gebeurtenissen zijn
    protected static function booted()
    {
        static::updated(function ($stock) {
            // Wanneer een voorraad wordt bijgewerkt, controleer of het relevante veld is gewijzigd
            if ($stock->isDirty('current_quantity') || $stock->isDirty('reserved_quantity')) {
                // Log de voorraadupdate
              
                // Roep de functie aan om de CSV te genereren
                //$stock->exportStockToCSV();
            }
        });
    }

    // Functie om voorraad naar CSV te exporteren
    public static function exportStockToCSV()
    {
        // Haal alle voorraadgegevens op
        $stocks = ProductStock::with('product')->get();
        
        // Voorlopige buffer voor de voorraad
        $csvContent = "SKU,Voorraad\n";

        // Verzamelen van voorraadgegevens in CSV-indeling
        foreach ($stocks as $stock) {
            if ($stock->product) {
                $sku = $stock->product->sku;
                $currentQuantity = $stock->current_quantity;
                $csvContent .= "{$sku},{$currentQuantity}\n";
            }
        }

        // Geef de bestandsnaam voor het CSV-bestand
        $fileName = 'stock_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // Sla het CSV-bestand lokaal op in storage/app
        Storage::disk('local')->put($fileName, $csvContent);

        // Log de exportactie
        Log::info('Voorraad CSV succesvol gegenereerd: ' . $fileName);

        // Geef een succesbericht terug
        return response()->json([
            'success' => true,
            'message' => 'CSV-bestand succesvol gegenereerd en lokaal opgeslagen.',
            'file' => $fileName,
        ]);
    }
    
    // Voorbeeld velden: product_id, available_quantity, reserved_quantity, etc.

    /**
     * Centrale methode om voorraad te updaten.
     *
     * @param  int    $productId
     * @param  int    $newValue
     * @param  string $stockType
     * @param  string $reason
     * @param  int    $userId
     * @return array
     */
    public static function updateStock($productId, $newValue, $stockType, $reason, $userId,$action)
    {
        // 1. Haal of maak de stock voor deze productId
        $productStock = self::firstOrNew(['product_id' => $productId]);

        $oldValue = (int)$productStock->$stockType;
        $difference = $newValue - $oldValue;

        if ($difference !== 0) {
            // 2. Pas de voorraad aan
            $productStock->$stockType = $newValue;
            $productStock->save();

            // 3. Log de wijziging
            ProductStockHistory::create([
                'product_id'   => $productId,
                'quantity'     => $difference,
                'stock_action' => $action,
                'reason'       => $reason . ' op kolom: '. $stockType,
                'user_id'      => $userId,
                'changed_at'   => now(),
            ]);
            //dd($stockType,$difference,$oldValue,$newValue);
            // 4. Check "back in stock"
            if ($stockType === 'current_quantity' && $oldValue == 0 && $newValue >= 1) {
                $product = Product::find($productId);
                   if ($product) {
                    $product->back_in_stock = true;
                    $product->save();
                }
            }
            if($newValue == 0){
                $product = Product::find($productId);
                if ($product) {
                    $product->back_in_stock = false;
                    $product->save();
                }   
            }
        }

        // 5. Return ruwe data
        return [
            'success'    => true,
            'message'    => 'Voorraad is bijgewerkt',
            'data'       => [
                'product_id' => $productId,
                'old_value'  => $oldValue,
                'new_value'  => $newValue,
                'difference' => $difference,
                'reason'     => $reason,
            ],
        ];
    }



    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(ProductStockHistory::class);
    }
}

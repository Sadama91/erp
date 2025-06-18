<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseOrderItem;  
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class WooCommerceController extends Controller
{
    public function manualImportForm()
    {
        return view('orders.import_manual');
    }
    public function manualImportSubmit(Request $request)
    {
        $request->validate([
            'json' => 'required|json',
        ]);
    
        $wooData = json_decode($request->input('json'), true);
    
        $missingSkus = [];
        $extraNotes = [];
    
        $payload = [
            'date' => $wooData['created_at'],
            'shipping_method' => $wooData['shipping_method'] ?: 'PostNL',
            'order_source' => 'Website',
            'shipping_cost' => $wooData['shipping_total'] ?? 0,
            'customer_name' => $wooData['customer']['first_name'] . ' ' . $wooData['customer']['last_name'],
            'username' => $wooData['customer']['email'],
            'customer_address' => $wooData['customer']['address_1'],
            'postal_code' => $wooData['customer']['postcode'],
            'city' => $wooData['customer']['city'],
            'country' => $wooData['customer']['country'],
            'notes' => "WOO Order ID: " . $wooData['id'],
            'products' => [],
            'definitief_opslaan' => 1,
        ];
    
        $verkoopTotaal = 0;
        $inkoopTotaal = 0;
    
        foreach ($wooData['line_items'] as $item) {
            if (empty($item['sku'])) {
                $missingSkus[] = $item['name'] . ' x' . $item['quantity'];
                continue;
            }
    
            $product = Product::where('sku', $item['sku'])->first();
            if (!$product) {
                $missingSkus[] = $item['sku'] . ' (' . $item['name'] . ')';
                continue;
            }
    
            $qty = $item['quantity'];
            $subtotal = floatval($item['total']);
            $purchase = $product->latestPurchaseItem->price_incl_unit ?? 0;
    
            $payload['products'][] = [
                'id' => $product->id,
                'quantity' => $qty,
                'lastPurchaseUnitPrice' => $purchase,
                'originalSellPrice' => $subtotal / $qty,
                'subtotal' => $subtotal,
            ];
    
            $verkoopTotaal += $subtotal;
            $inkoopTotaal += $purchase * $qty;
        }
    
        // Transactiekosten
        $idealKosten = floatval($wooData['transaction_fee'] ?? 0);
        if ($idealKosten > 0) {
            $kostenProduct = Product::where('name', 'like', '%IDEAL kosten%')->first();
            if ($kostenProduct) {
                $payload['products'][] = [
                    'id' => $kostenProduct->id,
                    'quantity' => 1,
                    'lastPurchaseUnitPrice' => 0,
                    'originalSellPrice' => $idealKosten,
                    'subtotal' => $idealKosten,
                ];
                $verkoopTotaal += $idealKosten;
            } else {
                $extraNotes[] = 'IDEAL kosten niet als product gevonden.';
            }
        }
    
        // Gratis verzending → korting
        if (floatval($wooData['shipping_total']) == 0) {
            $kortingBedrag = $wooData['shipping_method'] === 'PostNL' ? 6.95 : 4.25;
            $kortingProduct = Product::where('name', 'like', '%Korting%')->first();
            if ($kortingProduct) {
                $payload['products'][] = [
                    'id' => $kortingProduct->id,
                    'quantity' => 1,
                    'lastPurchaseUnitPrice' => 0,
                    'originalSellPrice' => -$kortingBedrag,
                    'subtotal' => -$kortingBedrag,
                ];
                $verkoopTotaal -= $kortingBedrag;
            } else {
                $extraNotes[] = 'Korting product niet gevonden.';
            }
        }
    
        // Notes verder aanvullen
        if (!empty($missingSkus)) {
            $extraNotes[] = 'Missende producten: ' . implode(', ', $missingSkus);
        }
        if (!empty($extraNotes)) {
            $payload['notes'] .= "\n" . implode("\n", $extraNotes);
        }
    
       
        try {
            $orderController = App::make(OrderController::class);
            $storeRequest = new Request($payload);
            $storeRequest->setUserResolver(fn() => auth()->user());
    
            return $orderController->store($storeRequest);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['json' => 'Fout bij importeren: ' . $e->getMessage()]);
        }
    }
    
    
    

    public function updateOrder(Request $request) {
        $data = $request->all();
    
        // (Tijdelijk) log of toon in de UI
        \Log::info('Woo order ontvangen via web:', ['order' => $data]);
    
        // Optioneel tijdelijk opslaan in een tabel (zoals woo_raw_orders)
        // of direct verwerken indien gewenst...
    
        return back()->with('success', 'Order ontvangen en opgeslagen');
    }
    
    public function updateProduct(Request $request) {
        // Ontvang productdata en werk de productgegevens bij
    }
    
    public function updateStock(Request $request) {
        // Ontvang voorraadinformatie en werk deze bij
    }
    
}

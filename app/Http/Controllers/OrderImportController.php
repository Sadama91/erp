<?php

namespace App\Http\Controllers;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderImportController extends Controller
{
    public function store(Request $request)
    {
        Log::info('OrderImportController::store()', ['request' => $request->all()]);

        // ──────────────────────────────────────────────────────────────────────────────
        // 1) Idempotency-Key ophalen & check
        //    We verwachten hem in header "Idempotency-Key" of body "idempotency_key"
        $idemKey = $request->header('Idempotency-Key')
                 ?? $request->input('idempotency_key');

        if (! $idemKey) {
            return response()->json([
                'message' => 'Idempotency-Key ontbreekt',
            ], 422);
        }

        // 2) Bestaande order met precies díe key?
        $already = Order::whereJsonContains('meta->idempotency_key', $idemKey)->first();
        if ($already) {
            // Return 200 want we hebben 'm al geïmporteerd
            return response()->json([
                'message'  => 'Order al geïmporteerd',
                'order_id' => $already->id,
            ], 200);
        }
        // ──────────────────────────────────────────────────────────────────────────────

        // 3) Authenticatie API-key
        if ($request->header('X-API-KEY') !== config('api.api_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 4) Log je raw payload
        $ts  = Carbon::now()->format('YmdHis');
        $dir = storage_path("logs/orders");
        File::ensureDirectoryExists($dir, 0755);
        File::put("$dir/order-{$ts}.json", $request->getContent());

        // 5) Valideer Woo payload (nieuw formaat met shipping/net/tax/gross)
        $validated = $request->validate([
            'id'                      => 'required|integer',
            'created_at'              => 'required|date_format:Y-m-d H:i:s',
            'shipping.method'         => 'nullable|string',
            'shipping.net_amount'     => 'nullable|numeric',
            'shipping.tax_amount'     => 'nullable|numeric',
            'shipping.gross_amount'   => 'nullable|numeric',
            'customer.first_name'     => 'nullable|string',
            'customer.last_name'      => 'nullable|string',
            'customer.email'          => 'nullable|email',
            'customer.address_1'      => 'nullable|string',
            'customer.postcode'       => 'nullable|string',
            'customer.city'           => 'nullable|string',
            'customer.country'        => 'nullable|string|size:2',
            'customer_note'           => 'nullable|string',
            'line_items'              => 'required|array|min:1',
            'line_items.*.product_id' => 'required|integer',
            'line_items.*.sku'        => 'nullable|string',
            'line_items.*.quantity'   => 'required|integer|min:1',
            'line_items.*.net_price'  => 'required|numeric',
            'line_items.*.tax_amount' => 'required|numeric',
            'line_items.*.gross_price'=> 'nullable|numeric',
        ]);

        $wooId = $validated['id'];

        // 6) Al bestaat er een order met dit woo_id? Dan werken we alleen bij
        $existing = Order::whereJsonContains('meta->woo_id', $wooId)->first();
        if ($existing) {
            $existing->update([
                'shipping_method'  => $validated['shipping']['method'] ?? $existing->shipping_method,
                'customer_name'    => trim(($validated['customer']['first_name'] ?? '') . ' ' . ($validated['customer']['last_name'] ?? '')) 
                                      ?: $existing->customer_name,
                'username'         => $validated['customer']['email'] ?? $existing->username,
                'customer_address' => $validated['customer']['address_1'] ?? $existing->customer_address,
                'postal_code'      => $validated['customer']['postcode'] ?? $existing->postal_code,
                'city'             => $validated['customer']['city'] ?? $existing->city,
                'country'          => $validated['customer']['country'] ?? $existing->country,
                'notes'            => $validated['customer_note'] ?? $existing->notes,
            ]);

            // Update meta
            $meta = $existing->meta ?? [];
            $meta['last_update_woo']     = now()->toDateTimeString();
            $meta['idempotency_key']     = $idemKey;
            $existing->meta = $meta;
            $existing->save();

            // Regel-items bijwerken
            $this->updateOrderItemsFromWoo($existing, $validated['line_items']);

            Log::info('Order bijgewerkt via WooSync', ['order_id' => $existing->id]);
            return response()->json([
                'message'  => 'Order bijgewerkt.',
                'order_id' => $existing->id,
            ], 200);
        }

        // 7) Nieuwe order: map line_items → producten
        $products = collect($validated['line_items'])->map(function($item) {
            $sku     = trim($item['sku'] ?? '');
            $product = $sku
                ? Product::where('sku', $sku)->first()
                : null;

            if (! $product) {
                $product = Product::where('woo_id', $item['product_id'])->first();
            }

            if (! $product) {
                throw ValidationException::withMessages([
                    'products' => "Geen product voor SKU '{$sku}' of Woo-ID {$item['product_id']}.",
                ]);
            }

            // Bereken bruto regelbedrag: gebruik payload of net+tax
            $net   = $item['net_price'];
            $tax   = $item['tax_amount'];
            $gross = $item['gross_price'] ?? round($net + $tax, 2);
            $gross = round($gross, 2);

            // unit price
            $unitGross = round($gross / $item['quantity'], 2);

            // laatste inkoopprijs
            $lastUnitPrice = DB::table('purchase_order_items')
                ->where('product_id', $product->id)
                ->orderByDesc('created_at')
                ->value('price_incl_unit') ?? 0;

            return [
                'id'                    => $product->id,
                'quantity'              => $item['quantity'],
                'lastPurchaseUnitPrice' => $lastUnitPrice,
                'originalSellPrice'     => $unitGross,
                'subtotal'              => $gross,
            ];
        })->toArray();

        // 8) Bouw interne request payload voor OrderController@store
        $input = [
            'date'               => Carbon::createFromFormat('Y-m-d H:i:s', $validated['created_at'])
                                         ->format('d-m-Y'),
            'shipping_method'    => $validated['shipping']['method'] ?? 'webshop',
            'order_source'       => 'woocommerce',
            'shipping_cost'      => round($validated['shipping']['gross_amount'] ?? 0, 2),
            'customer_name'      => trim(($validated['customer']['first_name'] ?? '') . ' ' . ($validated['customer']['last_name'] ?? '')),
            'username'           => $validated['customer']['email'] ?? null,
            'customer_address'   => $validated['customer']['address_1'] ?? null,
            'postal_code'        => $validated['customer']['postcode'] ?? null,
            'city'               => $validated['customer']['city'] ?? null,
            'country'            => $validated['customer']['country'] ?? null,
            'notes'              => $validated['customer_note'] ?? null,
            'products'           => $products,
            'definitief_opslaan' => true,
            'meta'               => [
                'woo_id'            => $wooId,
                'last_update_woo'   => now()->toDateTimeString(),
                'woo_created_at'    => $validated['created_at'],
                'idempotency_key'   => $idemKey,
                'invoice_number'    => null,
                // optioneel: tel alle totals mee
                'totals'            => $request->input('totals', []),
            ],
        ];

        // 9) Fake interne Request en forward naar OrderController
        $fake = HttpRequest::create(route('orders.store'), 'POST', $input);
        $fake->setLaravelSession(app('session.store'));

        Log::info('OrderImportController → nieuwe order aanmaken', ['mapped_data' => $input]);
        return app(OrderController::class)->store($fake);
    }

    /**
     * Werk alle orderregels bij, op basis van netto/tax/bruto uit Woo.
     */
    private function updateOrderItemsFromWoo(Order $order, array $wooLineItems)
    {
        // 1) Verwijder bestaande
        $order->items()->delete();

        // 2) Voeg nieuwe toe
        foreach ($wooLineItems as $item) {
            $sku     = trim($item['sku'] ?? '');
            $product = $sku
                ? Product::where('sku', $sku)->first()
                : null;

            if (! $product) {
                $product = Product::where('woo_id', $item['product_id'])->first();
            }
            if (! $product) {
                Log::warning('Product niet gevonden bij updateOrderItemsFromWoo', [
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'sku'        => $sku,
                ]);
                continue;
            }

            $net   = $item['net_price'];
            $tax   = $item['tax_amount'];
            $gross = isset($item['gross_price'])
                ? round($item['gross_price'], 2)
                : round($net + $tax, 2);

            $quantity  = $item['quantity'];
            $unitGross = round($gross / $quantity, 2);

            $lastUnitPrice = DB::table('purchase_order_items')
                ->where('product_id', $product->id)
                ->orderByDesc('created_at')
                ->value('price_incl_unit') ?? 0;

            // BTW‐informatie
            $vatRateId = $product->vat_rate_id ?? 7;
            $vatAmount = $tax; // rechtstreeks uit payload

            OrderItem::create([
                'order_id'               => $order->id,
                'product_id'             => $product->id,
                'quantity'               => $quantity,
                'purchase_price'         => $lastUnitPrice,
                'original_sales_price'   => $unitGross,
                'calculated_sales_price' => $gross,
                'vat_amount'             => $vatAmount,
                'vat_rate_id'            => $vatRateId,
            ]);
        }

        Log::info('Orderitems bijgewerkt vanuit Woo', ['order_id' => $order->id]);
    }
}

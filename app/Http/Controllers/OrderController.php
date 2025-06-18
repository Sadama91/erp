<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Parameter;
use App\Models\Prices;
use App\Models\ProductStock;
use App\Models\ProductStockHistory;
use App\Models\Setting;
use App\Models\UserSetting;
use App\Models\FinanceTransaction;

// supporting laravel features
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        // Haal de parameters op met de juiste pluck methode
        $salesMethods = Parameter::where('key', 'sales_chanel')->pluck('name', 'value');
        $orderStatuses = Parameter::where('key', 'order_status')->pluck('name', 'value');

        // Filteren
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->filled('shipping_method')) {
            $query->where('shipping_method', $request->shipping_method);
        }
        if ($request->filled('order_source')) {
            $query->where('order_source', $request->order_source);
        }

        if ($request->filled('order_status')) {
            $query->where('status', $request->order_status);
        }
        if ($request->filled('product_id')) {
            $query->whereHas('orderItems', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }
        // Zoekfunctie voor klantnaam en gebruikersnaam
        if ($request->filled('customer_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->customer_name . '%')
                    ->orWhere('username', 'like', '%' . $request->customer_name . '%');
            });
        }

        // Sorteren
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'customer_name':
                    $query->orderBy('customer_name', 'asc');
                    break;
                case 'shipping_method':
                    $query->orderBy('shipping_method', 'asc');
                    break;
                case 'status':
                    $query->orderBy('status', 'asc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default sortering
        }

        // Voeg metingen toe voor het aantal artikelen en totale waarde
        $orders = $query->withCount('orderItems')
            ->withSum('orderItems as total_value', 'calculated_sales_price')
            ->withSum('orderItems as total_quantity', 'quantity') // Totaal aantal producten
            ->paginate($request->input('results_per_page', 15));
            $orders->appends($request->except('page'));  // Voeg alle queryparameters toe behalve de 'page'

        return view('orders.index', compact('orders', 'orderStatuses', 'salesMethods'));
    }

    public function show($id)
    {

        $order = Order::with('items.product')->findOrFail($id);
        // Voeg de locaties toe aan de orderitems
        foreach ($order->items as $item) {
            // Haal de locatie naam op voor elk product
            $item->product->locationName = Parameter::where('value', $item->product->location)->value('name');
        }
        // Haal de verzendmethoden, verkoopkanalen en orderstatus op
        $shippingMethods = Parameter::where('key', 'shipping_method')->get(['name', 'value']);
        $salesChannels = Parameter::where('key', 'sales_chanel')->get(['name', 'value']);
        $orderStatuses = Parameter::where('key', 'order_status')->get(['name', 'value']);
        // Totale inkoopwaarde berekenen
        $totalValue = $order->orderItems->sum(function ($item) {
            return $item->quantity * $item->purchase_price;
        });

        // Totale verkoopwaarde berekenen
        $totalSalesIncl = $order->orderItems->sum(function ($item) {
            return $item->calculated_sales_price;
        });

        // Totale marge berekenen (enkel voor de weergave, niet opslaan in DB)
        $totalVat = $order->orderItems->sum('vat_amount');
        $totalValue = $order->total_purchase_price;
        if($totalValue == 0){
            $totalValue = 0.01;
        }
        $totalSalesIncl = $order->total_sales_price;
        $totalMargin = $totalSalesIncl - $totalValue;
        
        
        $totalMarginPercent = $totalValue ? ($totalMargin / $totalValue) * 100 : 0;

        // Voorbeeld van het ophalen van producten en verrijken met relevante informatie

        return view('orders.show', compact('order', 'shippingMethods', 'salesChannels', 'orderStatuses', 'totalValue', 'totalSalesIncl', 'totalMargin', 'totalMarginPercent'));
    }

    public function create()
    {
        $shippingMethods = Parameter::where('key', 'shipping_method')->get(['name', 'value']);
        $salesChannels = Parameter::where('key', 'sales_chanel')->get(['name', 'value']);

        $brands = Brand::all();
        return view('orders.create', compact('brands', 'shippingMethods', 'salesChannels'));
    }

    public function edit($id)
    {
        // Haal de order op, inclusief gerelateerde items en producten
        $order = Order::with(['orderItems.product'])->findOrFail($id);

        // Voeg benodigde data toe, zoals verzendmethoden of statussen
        $orderStatuses = Parameter::where('key', 'order_status')->get(['name', 'value']);
        $shippingMethods = Parameter::where('key', 'shipping_method')->get(['name', 'value']);
        $salesChannels = Parameter::where('key', 'sales_chanel')->get(['name', 'value']);

        $selectedProducts = $order->orderItems->map(function ($item) {
            $product = $item->product; // Haal het product op
        
            return [
                'id' => $item->product_id,
                'quantity' => $item->quantity,
                'lastPurchaseUnitPrice' => $item->purchase_price,
                'sell_price' => $item->original_sales_price,
                'subtotal' => $item->calculated_sales_price,
                'vat' => $item->vat_amount,
          ];
        });
        
        

        return view('orders.edit', compact('order', 'shippingMethods', 'salesChannels', 'orderStatuses', 'selectedProducts'));
    }
    
    public function store(Request $request)
    {
        // Merge flags en meta
        $request->merge([
            'definitief_opslaan' => $request->input('definitief_opslaan', false),
            'meta'               => $request->input('meta', []),
        ]);

        $validated = $request->validate([
            'date'                           => 'required|date',
            'shipping_method'                => 'required|string|not_in:Selecteer een optie',
            'order_source'                   => 'required|string|not_in:Selecteer een optie',
            'shipping_cost'                  => 'nullable|numeric',
            'customer_name'                  => 'required|string',
            'username'                       => 'nullable|string',
            'customer_address'               => 'nullable|string',
            'postal_code'                    => 'nullable|string',
            'city'                           => 'nullable|string',
            'country'                        => 'nullable|string',
            'notes'                          => 'nullable|string',
            'products'                       => 'required|array|min:1',
            'products.*.id'                  => 'required|integer',
            'products.*.quantity'            => 'required|integer|min:1',
            'products.*.lastPurchaseUnitPrice'=> 'required|numeric',
            'products.*.originalSellPrice'   => 'required|numeric',
            'products.*.subtotal'            => 'required|numeric',
        ]);

        // Format datum
        $validated['date'] = Carbon::createFromFormat('d-m-Y', $validated['date'])->format('Y-m-d');

        $userId       = auth()->id() ?: config('api.import_user_id');
        $isDefinitief = $request->boolean('definitief_opslaan');
        $products     = $validated['products'];
        $shippingCost = $validated['shipping_cost'] ?? 0;

        $order = DB::transaction(function () use ($validated, $products, $userId, $isDefinitief, $shippingCost, $request) {
            // 1) Maak order aan
            $order = Order::create([
                'date'             => $validated['date'], 
                'order_source'     => $validated['order_source'] === 'woocommerce' ? 'website' : $validated['order_source'],
                'shipping_method'  => $validated['shipping_method'],
                'shipping_cost'    => $shippingCost,
                'customer_name'    => $validated['customer_name'],
                'username'         => $validated['username'],
                'customer_address' => $validated['customer_address'],
                'postal_code'      => $validated['postal_code'],
                'city'             => $validated['city'],
                'country'          => $validated['country'],
                'notes'            => $validated['notes'],
                'status'           => $isDefinitief ? 1 : 0,
                'meta'             => $request->input('meta', []),
                'user_id'          => $userId,
            ]);

            // 2) Verwerk orderitems + voorraad
            foreach ($products as $item) {
                $product = Product::findOrFail($item['id']);
                $stock   = ProductStock::where('product_id', $product->id)->firstOrFail();
                $vatRate = Parameter::where('id', $product->vat_rate_id ?? 7)->value('value') ?? 0.21;
                $vatAmount = $item['subtotal'] * $vatRate;

                $orderItem = OrderItem::create([
                    'order_id'               => $order->id,
                    'product_id'             => $product->id,
                    'quantity'               => $item['quantity'],
                    'purchase_price'         => $item['lastPurchaseUnitPrice'],
                    'original_sales_price'   => $item['originalSellPrice'],
                    'calculated_sales_price' => $item['subtotal'],
                    'vat_amount'             => $vatAmount,
                    'vat_rate_id'            => $product->vat_rate_id,
                ]);

                // Boek netto op tussenrekening (uit voorraad altijd OUT)
                ProductStock::updateStock(
                    $product->id,
                    $stock->current_quantity - $item['quantity'],
                    'current_quantity',
                    "Order {$order->id} orderitem",
                    $userId,
                    'OUT'
                );

                // Voor reservering geen extra nemen, want status>=1

                $product->updateStatusAutomatically();
            }

            // 3) Boek financiële transactie voor status 1-2
            // netto bedrag incl. BTW
            $totalIncl = $shippingCost + collect($products)->sum('subtotal');
            $src = strtolower($order->order_source);
            $acctKey = match($src) {
                'vinted'  => 'on_the_way_account_vinted',
                'website' => 'on_the_way_account_webshop',
                default   => 'on_the_way_account_overig',
            };
            $betweenAcc = (int) Setting::get($acctKey, null, 'financeaccount');
            FinanceTransaction::create([
                'order_id'         => $order->id,
                'account_id'       => $betweenAcc,
                'debit_credit'     => 'bij',
                'amount'           => $totalIncl,
                'description'      => "Order #{$order->id} op tussenrekening",
                'transaction_date' => now(),
            ]);

            ProductStock::exportStockToCSV();

            return $order;
        });

        if ($request->expectsJson() || ! $request->hasSession()) {
            return response()->json(['order_id' => $order->id, 'message' => 'Order aangemaakt'], 201);
        }

        return redirect()->route('orders.index')->with('success', 'Order succesvol aangemaakt.');
    }


    public function update(Request $request, $id)
    {
        // Merge flags en meta
        $request->merge([
            'definitief_opslaan' => $request->input('definitief_opslaan', false),
            'meta'               => $request->input('meta', []),
        ]);

        $validated = $request->validate([
            'date'                           => 'required|date',
            'shipping_method'                => 'required|string|not_in:Selecteer een optie',
            'order_source'                   => 'required|string|not_in:Selecteer een optie',
            'shipping_cost'                  => 'nullable|numeric',
            'customer_name'                  => 'required|string',
            'username'                       => 'nullable|string',
            'customer_address'               => 'nullable|string',
            'postal_code'                    => 'nullable|string',
            'city'                           => 'nullable|string',
            'country'                        => 'nullable|string',
            'notes'                          => 'nullable|string',
            'products'                       => 'required|array|min:1',
            'products.*.id'                  => 'required|integer',
            'products.*.quantity'            => 'required|integer|min:1',
            'products.*.lastPurchaseUnitPrice'=> 'required|numeric',
            'products.*.originalSellPrice'   => 'required|numeric',
            'products.*.subtotal'            => 'required|numeric',
            'new_status'                     => 'required|integer|in:0,1,2,3',
        ]);

        $userId       = auth()->id() ?: config('api.import_user_id');
        $products     = $validated['products'];
        $order        = Order::with('orderItems')->findOrFail($id);
        $prevItems    = $order->orderItems->keyBy('product_id');
        $shippingCost = $validated['shipping_cost'] ?? 0;

        DB::transaction(function () use ($order, $validated, $products, $prevItems, $userId, $shippingCost,$request) {
            // 1) Update hoofdgegevens
            $order->update([
                'date'             => Carbon::createFromFormat('d-m-Y', $validated['date'])->format('Y-m-d'),
                'shipping_method'  => $validated['shipping_method'],
                'order_source'     => $validated['order_source'],
                'shipping_cost'    => $shippingCost,
                'customer_name'    => $validated['customer_name'],
                'username'         => $validated['username'],
                'customer_address' => $validated['customer_address'],
                'postal_code'      => $validated['postal_code'],
                'city'             => $validated['city'],
                'country'          => $validated['country'],
                'notes'            => $validated['notes'],
                'meta'             => $request->input('meta', []),
            ]);

            // 2) Sync items + voorraad
            foreach ($products as $item) {
                $pid    = $item['id'];
                $qty    = $item['quantity'];
                $existing = $prevItems->get($pid);
                $prod   = Product::findOrFail($pid);
                $stock  = ProductStock::where('product_id',$pid)->firstOrFail();
                $vatRate = Parameter::where('id',$prod->vat_rate_id ?? 7)->value('value') ?? 0.21;
                $vatAmt   = $item['subtotal'] * $vatRate;

                if ($existing) {
                    $diff = $qty - $existing->quantity;
                    $existing->update([
                        'quantity'               => $qty,
                        'purchase_price'         => $item['lastPurchaseUnitPrice'],
                        'original_sales_price'   => $item['originalSellPrice'],
                        'calculated_sales_price' => $item['subtotal'],
                        'vat_amount'             => $vatAmt,
                    ]);
                    if ($diff !== 0) {
                        // Boek verschil op tussenrekening
                        ProductStock::updateStock(
                            $pid,
                            $stock->current_quantity - $diff,
                            'current_quantity',
                            "Order update #{$order->id}",
                            $userId,
                            'OUT'
                        );
                    }
                } else {
                    OrderItem::create([
                        'order_id'               => $order->id,
                        'product_id'             => $pid,
                        'quantity'               => $qty,
                        'purchase_price'         => $item['lastPurchaseUnitPrice'],
                        'original_sales_price'   => $item['originalSellPrice'],
                        'calculated_sales_price' => $item['subtotal'],
                        'vat_amount'             => $vatAmt,
                        'vat_rate_id'            => $prod->vat_rate_id,
                    ]);
                    ProductStock::updateStock(
                        $pid,
                        $stock->current_quantity - $qty,
                        'current_quantity',
                        "Nieuw item update order #{$order->id}",
                        $userId,
                        'OUT'
                    );
                }
                $prod->updateStatusAutomatically();
            }

            // 3) Verwijder oude items
            $newIds = collect($products)->pluck('id')->all();
            foreach ($prevItems as $old) {
                if (!in_array($old->product_id,$newIds)) {
                    $s = ProductStock::where('product_id',$old->product_id)->first();
                    ProductStock::updateStock(
                        $old->product_id,
                        $s->current_quantity + $old->quantity,
                        'current_quantity',
                        "Order #{$order->id} item verwijderd",
                        $userId,
                        'IN'
                    );
                    $old->delete();
                }
            }

            // 4) Boek financiële mutatie op tussenrekening (incl. BTW)
            $totalIncl = $order->shipping_cost + $order->orderItems()->sum('calculated_sales_price');
            $src = strtolower($order->order_source);
            $acctKey = match($src) {
                'vinted'  => 'on_the_way_account_vinted',
                'website' => 'on_the_way_account_webshop',
                default   => 'on_the_way_account_overig',
            };
            $betweenAcc = (int) Setting::get($acctKey,null,'financeaccount');
            $trans = FinanceTransaction::firstOrNew(['order_id'=>$order->id]);
            $trans->fill([
                'account_id'       => $betweenAcc,
                'debit_credit'     => 'bij',
                'amount'           => $totalIncl,
                'description'      => "Order update #{$order->id} tussenrekening",
                'transaction_date' => now(),
            ])->save();

            ProductStock::exportStockToCSV();

            // 5) Status via setStatus (evt. naar 3)
            $this->setStatus($order->id, $validated['new_status'], $userId);
        });

        return redirect()->route('orders.show',$order->id)->with('success','Order succesvol bijgewerkt.');
    }

    public function updateStatus(Request $request, $id)
    {
        // Valideer de nieuwe status
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3',
        ]);
    
        $newStatus = (int) $request->status;
    
        // Roep de setStatus functie aan, die alle logica afhandelt
        $this->setStatus($id, $newStatus, auth()->id());
    
        return redirect()->route('orders.show', $id)
                         ->with('success', 'Status is succesvol bijgewerkt.');
    }
    
    
public function destroy($id)
{
    $order = Order::findOrFail($id);

    return DB::transaction(function () use ($order) {
          // Verwijder gekoppelde finance transactions op basis van order_id
          $financeTransactions = FinanceTransaction::where('order_id', $order->id)->get();
          foreach ($financeTransactions as $transaction) {
              // Verwijder de finance transaction zelf
              $transaction->delete();
          }
  
          // Verwijder de order
          $order->delete();
  
          // Exporteer de voorraadgegevens
          ProductStock::exportStockToCSV();

        return redirect()->route('orders.index')->with('success', 'Order succesvol verwijderd.');
    });
}


    public function saveSelectedItems(Request $request)
    {
        // Valideer de aanvraag
        $request->validate([
            'order_id' => 'required|integer',
            'channel_key' => 'required|string',
            'selected_items' => 'array',
            'selected_items.*' => 'integer' // Geen validatie op bestendigheid van items
        ]);


        // Sla de instellingen van de gebruiker op
        $user = auth()->user();
        $page = 'orders.selectOrderListProducts';

        UserSetting::updateOrCreate(
            [
                'user_id' => $user->id,
                'page' => $page,
            ],
            [
                'settings' => [
                    'order_id' => $request->order_id,
                    'channel' => $request->channel_key,
                    'selected_items' => $request->selected_items,
                ]
            ]
        );

        return response()->json(['success' => true]); // Geef succes terug
    }

    public function showSoldSinceAndItems(Request $request)
{
    $user = auth()->user();
    $page = 'orders.selectOrderListProducts';

    // Haal user settings op
    $settings = UserSetting::where('user_id', $user->id)->where('page', $page)->first();
    $settingsData = $settings->settings ?? [];

    // Validatie
    $validated = $request->isMethod('POST') ? $request->validate([
        'order_id' => 'required|integer',
        'order_id_end' => 'nullable|integer',
        'channel_key' => 'nullable|string',
        'selected_items' => 'nullable|array',
    ]) : [];

    $orderId = $validated['order_id'] ?? $settingsData['order_id'] ?? null;
    $orderIdEnd = $validated['order_id_end'] ?? $settingsData['order_id_end'] ?? null;
    $channel = $validated['channel_key'] ?? $settingsData['channel'] ?? null;
    $selectedItems = $validated['selected_items'] ?? $settingsData['selected_items'] ?? [];

    // Opslaan van instellingen (alleen bij POST)
    if ($request->isMethod('POST')) {
        UserSetting::updateOrCreate(
            ['user_id' => $user->id, 'page' => $page],
            ['settings' => compact('orderId', 'orderIdEnd', 'channel', 'selectedItems')]
        );
    }

    $orders = Order::latest()->paginate(20);
    $channels = Parameter::where('key', 'sales_chanel')->get(['name', 'value']);

    $items = collect();

    if ($orderId) {
        $orderQuery = Order::where('id', '>=', $orderId);
        if ($orderIdEnd) {
            $orderQuery->where('id', '<=', $orderIdEnd);
        }
        if ($channel && $channel !== 'all') {
            $orderQuery->where('order_source', $channel);
        }

        $ordersInRange = $orderQuery->pluck('id');

        // Producten en hoeveel verkocht
        $items = OrderItem::with(['product.brand', 'product.subgroup', 'product.prices', 'product.stock'])
            ->whereIn('order_id', $ordersInRange)
            ->get()
            ->groupBy('product_id')
            ->map(function ($groupedItems) {
                $first = $groupedItems->first();
                $product = $first->product;

                $product->activePrices = $product->prices->filter(function ($price) {
                    return is_null($price->valid_till); // Alleen actieve prijzen zonder einddatum
                });
                $regularPrice = $product->prices->where('type', 'regular')->first();
                $product->regularPrice = $regularPrice ? $regularPrice->price : 0;
                
                $vintedPrice = $product->prices->where('type', 'vinted')->first();
                $product->vintedPrice = $vintedPrice ? $vintedPrice->price : $product->regularPrice;
                 
                $total_quantity = $groupedItems->sum('quantity');
                return (object) [
                    'product' => $product,
                    'total_quantity' => $total_quantity,
                    'current_quantity' => $product->stock->current_quantity ?? 0,
                    'on_the_way_quantity' => $product->stock->on_the_way_quantity ?? 0,
                ];
            });
        // Resold producten markeren
        $resoldProductIds = OrderItem::whereIn('order_id', Order::where('id', '>', $orderIdEnd ?? $orderId)->pluck('id'))
            ->pluck('product_id')
            ->unique();

        foreach ($items as $item) {
            $item->resold = $resoldProductIds->contains($item->product->id);
        }
    }

    $ordersJson = $orders->map(fn($order) => [
        'id' => $order->id,
        'customer_name' => $order->customer_name,
        'order_source' => $order->order_source,
        'created_at' => $order->created_at->toDateTimeString(),
    ])->values();

    if ($items->isEmpty()) {
        $items = "Geen producten gevonden op dit filter.";
    }

    return view('orders.selectOrderListProducts', compact('orders', 'channels', 'items', 'channel', 'orderId', 'ordersJson', 'orderIdEnd', 'selectedItems'));
}

    public function showPickList(Request $request)
    {
        $selectedOrders = $request->input('selected_orders', []);
        $selectedOrdersArray = is_string($selectedOrders) ? explode(',', $selectedOrders) : $selectedOrders;
    
        $orders = Order::with([
            'orderItems.product.brand',
            'orderItems.product.imageLinks.image',
            'orderItems.product.stock'
        ])->whereIn('id', $selectedOrdersArray)->get();
    
        $pickList = [];
    
        foreach ($orders as $order) {
            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;
                if (!$product) continue;
    
                $locationValue = $product->location;
                $locationName = Parameter::where('value', $locationValue)->value('name') ?? 'Onbekende locatie';
    
                $stockData = json_decode($product->stock, true);
                $currentStock = $stockData['current_quantity'] ?? 0;
                $reserved = $stockData['reserved_quantity'] ?? 0;
                $onTheWay = $stockData['on_the_way_quantity'] ?? 0;
    
                $stockInfo = "Voorraad: $currentStock";
                if ($reserved > 0) $stockInfo .= ", Gereserveerd: $reserved";
                if ($onTheWay > 0) $stockInfo .= ", Onderweg: $onTheWay";
    
                $pickList[$locationName][] = [
                    'product' => $product,
                    'quantity' => $orderItem->quantity,
                    'stock_info' => $stockInfo,
                ];
            }
        }
    
        // Sorteer binnen elke locatie op merknaam, daarna op SKU
        foreach ($pickList as &$products) {
            usort($products, function ($a, $b) {
                $brandA = $a['product']->brand->name ?? '';
                $brandB = $b['product']->brand->name ?? '';
                if ($brandA === $brandB) {
                    return strcmp($a['product']->sku, $b['product']->sku);
                }
                return strcmp($brandA, $brandB);
            });
        }
    
        return view('orders.picklist', compact('pickList', 'selectedOrdersArray', 'selectedOrders'));
    }
    
    public function packOrders(Request $request, $selectedOrders)
    {
        $selectedOrdersArray = explode(',', $selectedOrders);
    
        $orders = Order::with([
            'orderItems.product.brand',
            'orderItems.product.imageLinks'
        ])->whereIn('id', $selectedOrdersArray)->get();
    
        // Sorteer de orderItems per order op merknaam en vervolgens op SKU
        foreach ($orders as $order) {
            $order->orderItems = $order->orderItems->sort(function ($a, $b) {
                $brandA = $a->product->brand->name ?? '';
                $brandB = $b->product->brand->name ?? '';
                if ($brandA === $brandB) {
                    return strcmp($a->product->sku, $b->product->sku);
                }
                return strcmp($brandA, $brandB);
            })->values(); // reset keys
        }
    
        return view('orders.pack', compact('orders'));
    }
    
    /**
 * Update order status en verwerk voorraad + boekingen.
 *
 * @param  int  $orderId
 * @param  int  $newStatus
 * @param  int|null  $userId  // expliciet meegeven
 * @return \App\Models\Order
 */
public function setStatus(int $orderId, int $newStatus, ?int $userId = null): Order
{
    // fallback op ingelogde user
    $userId = $userId ?? auth()->id();

    if (! in_array($newStatus, [0,1,2,3], true)) {
        throw new \InvalidArgumentException("Ongeldige status opgegeven.");
    }

    $order = Order::findOrFail($orderId);
    $old   = $order->status;

    // niets doen als status gelijk blijft
    if ($old === $newStatus) {
        return $order;
    }

    // 1) update status
    $order->status = $newStatus;
    $order->save();

    // 2) voorraad: alleen 0 → ≥1
    if ($old === 0 && $newStatus >= 1) {
        foreach ($order->orderItems as $item) {
            $stock = ProductStock::where('product_id', $item->product_id)->firstOrFail();
            $qty   = $item->quantity;

            // reservering vrijmaken
            ProductStock::updateStock(
                $item->product_id,
                $stock->reserved_quantity - $qty,
                'reserved_quantity',
                "Order {$order->id} definitief (reservering opgeheven)",
                $userId,
                'IN'
            );

            // definitief uit voorraad
            ProductStock::updateStock(
                $item->product_id,
                $stock->current_quantity - $qty,
                'current_quantity',
                "Order {$order->id} definitief (uit voorraad)",
                $userId,
                'IN'
            );

            $stock->product->updateStatusAutomatically();
        }
    }

    // 3) financiële boeking: alleen bij overgang naar status 3
    if ($old < 3 && $newStatus === 3) {
        // a) bepaal tussenrekening
        $src = strtolower($order->order_source);
        $between = match($src) {
            'vinted'  => (int) Setting::get('on_the_way_account_vinted', null, 'financeaccount'),
            'website' => (int) Setting::get('on_the_way_account_webshop', null, 'financeaccount'),
            default   => (int) Setting::get('on_the_way_account_overig',  null, 'financeaccount'),
        };

        // b) reversale tussenrekening
        $prev = FinanceTransaction::where('order_id', $order->id)
            ->first();
        $ammount = $prev->amount * -1;
        if ($prev) {
            FinanceTransaction::create([
                'account_id'       => $between,
                'debit_credit'     => 'af',
                'amount'           => $ammount,
                'description'      => "Reversal tussenrekening Order #{$order->id}",
                'order_id'         => $order->id,
                'transaction_date' => now(),
                'is_booked'        => true,
            ]);

            log::info("Reversal tussenrekening Order #{$order->id} met bedrag {$prev->amount} van {$between}");
        }

        // c) bereken nettobedrag & BTW
        $totalInclVat = $order->shipping_cost + $order->orderItems->sum('calculated_sales_price');
        
        // d) netto naar bank
        $bank = (int) Setting::get('bank_account', null, 'financeaccount');
        FinanceTransaction::create([
            'account_id'       => $bank,
            'debit_credit'     => 'bij',
            'amount'           => $totalInclVat,
            'description'      => "Order #{$order->id} afgerond: netto ontvangen",
            'order_id'         => $order->id,
            'transaction_date' => now(),
            'is_booked'        => true,
        ]);
        
        log::info("Order #{$order->id} afgerond: netto ontvangen {$totalInclVat} op {$bank}");
    }

    // 4) Exporteer voorraad
    ProductStock::exportStockToCSV();

    return $order;
}


    public function bulkSetStatus(array $orderUpdates)
{
    $results = [
        'processed' => [],
        'errors'    => []
    ];

    foreach ($orderUpdates as $update) {
        // Controleer of orderId en newStatus aanwezig zijn
        if (!isset($update['orderId'], $update['newStatus'])) {
            $results['errors'][] = [
                'orderId' => $update['orderId'] ?? null,
                'error'   => 'Zowel orderId als newStatus moeten worden opgegeven.'
            ];
            continue;
        }

        $orderId   = $update['orderId'];
        $newStatus = $update['newStatus'];

        try {
            // Roep de setStatus functie aan voor deze bestelling
            $order = $this->setStatus($orderId, $newStatus);
            $results['processed'][] = $order;
        } catch (\Exception $e) {
            // Foutmelding vastleggen
            $results['errors'][] = [
                'orderId' => $orderId,
                'error'   => $e->getMessage()
            ];
        }
    }

    return $results;
}

    public function sendOrder(Request $request, $id)
    {
        // Zoek de bestelling op
        $order = Order::findOrFail($id);

        // Haal de in te pakken artikelen op
        $packedItems = $request->input('packed_items', []);

        // Controleer of er artikelen zijn geselecteerd
        if (empty($packedItems)) {
            return redirect()->back()->withErrors(['packed_items' => 'Gelieve ten minste één artikel te selecteren om in te pakken.']);
        }

        // Verwerk de verzending
        foreach ($order->orderItems as $orderItem) {
            if (in_array($orderItem->product_id, $packedItems)) {
                $stock = ProductStock::where('product_id', $orderItem->product_id)->first();

                if ($stock) {
                    // Boek de voorraad uit
                    $stock->decrement('current_quantity', $orderItem->quantity);
                    // Registreer de mutatie in ProductStockHistory
                    ProductStockHistory::create([
                        'product_id' => $orderItem->product_id,
                        'quantity' => $orderItem->quantity,
                        'stock_action' => 'OUT',
                        'reason' => 'Bestelling ' . $order->id . ' verzonden',
                        'user_id' => auth()->id(),
                        'changed_at' => now(),
                    ]);
                }
            }
        }

        // Update de status van de bestelling naar 'verzonden' (status 2)
        $order->status = 2;
        $order->save();

        // Zoek de volgende bestelling om in te pakken
        $nextOrder = Order::where('status', 1)->first(); // Hier kun je aanpassen hoe je de volgende bestelling bepaalt

        if ($nextOrder) {
            return redirect()->route('orders.pack', $nextOrder->id)->with('success', 'Bestelling is succesvol verzonden. Ga verder met de volgende bestelling.');
        } else {
            return redirect()->route('orders.index')->with('success', 'Alle bestellingen zijn succesvol verzonden.');
        }
    }


    private function getStockInfo(array $stock): ?string
    {
        $onTheWay = $stock['on_the_way_quantity'] ?? 0;
        if ($onTheWay > 0) {
            return "onderweg: $onTheWay";
        }

        return null;
    }
}

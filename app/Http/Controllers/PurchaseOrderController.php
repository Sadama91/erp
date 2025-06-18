<?php

namespace App\Http\Controllers;

use App\Events\PurchaseOrderPlaced;
use App\Events\PurchaseOrderProcessed;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Price;
use App\Models\Brand;
use App\Models\Parameter;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    /**
     * Toon de lijst met inkooporders met filtering, sortering en paginatie.
     */
    public function index(Request $request)
    {
        $supplierFilter = $request->input('supplier') ?? $request->input('supplier_id');
        $resultsPerPage = $request->input('results_per_page', 15);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = PurchaseOrder::with('supplier')
            ->withCount(['purchaseOrderItems as total_quantity' => function ($query) {
                $query->select(DB::raw('SUM(quantity)'));
            }])
            ->withSum('purchaseOrderItems', 'total');

        if ($supplierFilter) {
            $query->where('supplier_id', $supplierFilter);
        }
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // Sorteerlogica
        $sort = $request->input('sort');
        if ($sort === null || $sort === 'newest') {
            $query->orderBy('date', 'desc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('date', 'asc');
        } elseif ($sort === 'az') {
            $query->orderBy('supplier_id', 'asc');
        } elseif ($sort === 'za') {
            $query->orderBy('supplier_id', 'desc');
        }

        $purchases = $query->paginate($resultsPerPage);
        $suppliers = Supplier::all();
        $statuses = Parameter::where('key', 'purchase_order_statuses')->pluck('name', 'value');

        return view('purchase_orders.index', compact('purchases', 'suppliers', 'statuses'));
    }

    /**
     * Toon het formulier voor het aanmaken van een nieuwe inkooporder.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $brands = Brand::all();
        return view('purchase_orders.create', compact('brands', 'suppliers'));
    }

    /**
     * Sla een nieuwe inkooporder op en dispatch een event als deze definitief is.
     */
    public function store(Request $request)
    {
       // dd($request);
        $validatedData = $request->validate([
            'date'        => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'remarks'     => 'nullable|string',
            'products'    => 'required|json',
        ]);
 
        // Gebruik $formattedDate bij het opslaan

        return DB::transaction(function () use ($request, $validatedData) {
            // Maak de inkooporder aan
            $purchaseOrder = new PurchaseOrder();
            $purchaseOrder->supplier_id = $validatedData['supplier_id'];

            $dateInput = $request->input('date'); 
            $purchaseOrder->date = Carbon::createFromFormat('d-m-Y', $dateInput)->format('Y-m-d');

            // Status: 0 = Concept, 1 = Definitief
            if ($request->has('concept_opslaan')) {
                $purchaseOrder->status = 0;
            } elseif ($request->has('definitief_opslaan')) {
                $purchaseOrder->status = 1;
            }

            if (!$purchaseOrder->save()) {
                throw new \Exception('Kon de inkooporder niet opslaan.');
            }

            // Audit log voor aanmaken
            activity()
                ->performedOn($purchaseOrder)
                ->withProperties(['action' => 'created'])
                ->log("Inkooporder aangemaakt");

            // Decodeer de JSON-productdata
            $selectedProducts = json_decode($validatedData['products'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ongeldige JSON voor producten.');
            }

            foreach ($selectedProducts as $product) {
                $productModel = Product::find($product['id']);
                if (!$productModel) {
                    throw new \Exception('Product niet gevonden.');
                }
               // dd($request);
                $purchasePriceIncl = $product['priceIncl'] * $product['quantity'];
                $saleFactor = $productModel->sale_quantity / $productModel->purchase_quantity; // Correctie: verkoopfactor
                $vatRateParameter = Parameter::find($productModel->vat_rate_id);
                $taxRate = $vatRateParameter ? (float)$vatRateParameter->value : 0.21;
                $purchasePriceExcl = $purchasePriceIncl / (1 + $taxRate); // Totale prijs exclusief BTW voor alle aankoopunits
                $priceExclUnit = ($purchasePriceExcl / $saleFactor)/$product['quantity'];        // Exclusieve prijs per verkoopunit
                $priceInclUnit = $priceExclUnit * (1 + $taxRate);           // Inclusieve prijs per verkoopunit
                $quantitySales = $product['quantity'] * $saleFactor;        // Aantal verkoopunits (stock)
                $total = $priceInclUnit * $quantitySales;                   // Totaalbedrag (incl. BTW) voor de verkoopunits
                              // Maak het orderitem aan
                $purchaseOrder->purchaseOrderItems()->create([
                    'product_id'      => $product['id'],
                    'sku'             => $product['sku'],
                    'description'     => $product['description'] ?? null,
                    'price_excl_bulk' => round($purchasePriceExcl, 4),
                    'price_excl_unit' => round($priceExclUnit, 4),
                    'price_incl_bulk' => round($purchasePriceIncl, 4),
                    'price_incl_unit' => round($priceInclUnit, 4),
                    'quantity'        => $quantitySales,
                    'total'           => $total,
                ]);
                $purchaseOrder->total_amount += $total;

                // Update de voorraad (on_the_way)
                $productStock = ProductStock::firstOrNew(['product_id' => $product['id']]);
                $newOnTheWay = $productStock->on_the_way_quantity + $quantitySales;
                ProductStock::updateStock(
                    $product['id'],
                    $newOnTheWay,
                    'on_the_way_quantity',
                    "Inkooporder #{$purchaseOrder->id} aangemaakt",
                    auth()->id(),
                    'IN'
                );
                $productModel->updateStatusAutomatically();
                ProductStock::exportStockToCSV();
            }

            return redirect()->route('purchases.index')
                ->with('success', 'Inkooporder succesvol opgeslagen.');
        });
    }

    /**
     * Verwerk de ontvangst van een inkooporder.
     * Hierbij worden voorraadmutaties uitgevoerd en wordt een event gedispatched
     * voor verdere financiële boekingen.
     */
    public function process($id)
    {
        $purchaseOrder = PurchaseOrder::with('purchaseOrderItems.product')->findOrFail($id);
        $countedData = request('counted', []); // Ontvangen hoeveelheden
        $create_invoice = request('create_invoice');
        
        return DB::transaction(function () use ($purchaseOrder, $countedData) {
            foreach ($purchaseOrder->purchaseOrderItems as $item) {
                if (isset($countedData[$item->id])) {
                    $countedQuantity = (int)$countedData[$item->id];
                    $stock = ProductStock::firstOrNew(
                        ['product_id' => $item->product_id],
                        ['on_the_way_quantity' => 0, 'current_quantity' => 0]
                    );
                    $newOnTheWay = $stock->on_the_way_quantity - $item->quantity;
                    $newCurrent  = $stock->current_quantity + $countedQuantity;

                    ProductStock::updateStock(
                        $item->product_id,
                        $newOnTheWay,
                        'on_the_way_quantity',
                        "Inkooporder #{$purchaseOrder->id} verwerkt (onderweg aangepast)",
                        auth()->id(),
                        'OUT'
                    );
                    ProductStock::updateStock(
                        $item->product_id,
                        $newCurrent,
                        'current_quantity',
                        "Inkooporder #{$purchaseOrder->id} verwerkt (ontvangen voorraad)",
                        auth()->id(),
                        'IN'
                    );

                    $item->description = "Controle: aangepast van {$item->quantity} naar {$countedQuantity}";
                    $item->quantity = $countedQuantity;
                    $item->save();

                    if ($item->product) {
                        $item->product->updateStatusAutomatically();
                    }
                }
            }

            // Update de status van de inkooporder naar 'verwerkt' (bijv. status 3)
            $purchaseOrder->status = 3;
            
            $purchaseOrder->save();
            ProductStock::exportStockToCSV();
            if(request('create_invoice') === "1"){
                return redirect()->route('financial.invoices.create', ['from_purchase_order' => $purchaseOrder->id])
                    ->with('success', 'Inkooporder succesvol verwerkt.');
            }
            
            
            // Audit log voor verwerking
            activity()
                ->performedOn($purchaseOrder)
                ->withProperties(['action' => 'processed'])
                ->log("Inkooporder verwerkt en ontvangst geregistreerd");

            return redirect()->route('purchases.show', $purchaseOrder->id)
                ->with('success', 'Inkooporder succesvol verwerkt.');
        });
    }

    /**
     * Toon de details van een specifieke inkooporder.
     */
    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with(['purchaseOrderItems.product', 'purchaseOrderItems.price', 'supplier','activities'])
            ->findOrFail($id);

        $statuses = Parameter::where('key', 'purchase_order_statuses')->pluck('name', 'value');
        $totalSalesPrice = 0;
        foreach ($purchaseOrder->purchaseOrderItems as $item) {
            $price = Price::where('product_id', $item->product_id)
                ->where('type', 'regular')
                ->whereNull('valid_till')
                ->first();
            $itemPrice = $price ? $price->price : 0;
            $product = $item->product;
            if ($product) {
                $item->purchase_quantity = $product->purchase_quantity;
                $item->sale_quantity = $product->sale_quantity;
                $item->purchaseFactor = $item->purchase_quantity / $item->sale_quantity;
            }
            $totalSalesPrice += ($itemPrice) * $item->quantity;
        }

        return view('purchase_orders.show', compact('purchaseOrder', 'statuses', 'totalSalesPrice'));
    }

    /**
     * Toon het controleformulier van een inkooporder.
     */
    public function control($id)
    {
        $purchaseOrder = PurchaseOrder::with(['supplier', 'purchaseOrderItems.product', 'purchaseOrderItems.price'])
            ->findOrFail($id);
        $statuses = Parameter::where('key', 'purchase_order_statuses')->pluck('name', 'value');

        return view('purchase_orders.control', compact('purchaseOrder', 'statuses'));
    }

    /**
     * Toon het formulier voor het bewerken van een inkooporder.
     */
    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $statuses = Parameter::where('key', 'purchase_order_statuses')->pluck('name', 'value');
        $suppliers = Supplier::all();
        $editable = in_array($purchaseOrder->status, [0, 1]);

        return view('purchase_orders.edit', compact('purchaseOrder', 'statuses', 'suppliers', 'editable'));
    }

    /**
     * Werk een inkooporder bij. Hierbij worden bestaande orderitems aangepast of verwijderd
     * en nieuwe items toegevoegd. Financiële correcties worden in de listeners afgehandeld.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date'        => 'required|date',
            'status'      => 'required|string',
            'products'    => 'required|json',
        ]);
    
        $dateInput = $request->input('date');
        $validatedData['date'] = Carbon::createFromFormat('d-m-Y', $dateInput)->format('Y-m-d');
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->date = $validatedData['date'];
        return DB::transaction(function () use ($request, $purchaseOrder) {
            // Werk basisgegevens bij
            $purchaseOrder->update($request->only(['supplier_id', 'status']));

            // Audit log voor update
            activity()
                ->performedOn($purchaseOrder)
                ->withProperties(['action' => 'updated'])
                ->log("Inkooporder bijgewerkt");

            // Decodeer de JSON-productdata
            $selectedProducts = json_decode($request->input('products'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ongeldige JSON voor producten.');
            }
            $incomingProductIds = collect($selectedProducts)
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            // Verwijder orderitems die niet meer voorkomen
            $purchaseOrder->purchaseOrderItems()->whereNotIn('product_id', $incomingProductIds)->delete();

            $totalAmount = 0;
            foreach ($selectedProducts as $product) {
                $productModel = Product::find($product['id']);
                if (!$productModel) {
                    continue;
                }

                $purchasePriceIncl = $product['priceIncl'];
                $quantity = (int)$product['quantity'];
                $purchaseFactor = $productModel->purchase_quantity / $productModel->sale_quantity;
                $vatRate = optional(Parameter::find($productModel->vat_rate_id))->value ?? 0.21;
                $purchasePriceExcl = $purchasePriceIncl / (1 + $vatRate);
                $priceExclUnit = $purchasePriceExcl / $purchaseFactor;
                $priceInclUnit = $priceExclUnit * (1 + $vatRate);
                $total = $purchasePriceIncl * $quantity;
                $quantitySales = $quantity * $purchaseFactor;

                // Update of maak het orderitem aan
                $purchaseOrder->purchaseOrderItems()->updateOrCreate(
                    ['product_id' => $product['id']],
                    [
                        'sku'             => $product['sku'] ?? $productModel->sku,
                        'description'     => $product['description'] ?? null,
                        'price_excl_bulk' => round($purchasePriceExcl, 2),
                        'price_excl_unit' => round($priceExclUnit, 2),
                        'price_incl_bulk' => round($purchasePriceIncl, 2),
                        'price_incl_unit' => round($priceInclUnit, 2),
                        'quantity'        => $quantitySales,
                        'total'           => $total,
                    ]
                );

                // Verwerk de voorraadmutatie: verhoog on_the_way_quantity
                $productStock = ProductStock::firstOrNew(['product_id' => $product['id']]);
                $newOnTheWay = $productStock->on_the_way_quantity + $quantity;
                ProductStock::updateStock(
                    $product['id'],
                    $newOnTheWay,
                    'on_the_way_quantity',
                    "Nieuw of aangepast item (order {$purchaseOrder->id})",
                    auth()->id(),
                    'IN'
                );
                $productModel->updateStatusAutomatically();

                $totalAmount += $total;
            }

            // Audit log voor financiële update
            activity()
                ->performedOn($purchaseOrder)
                ->withProperties(['action' => 'financial_update', 'total_amount' => $totalAmount])
                ->log("Financiële gegevens van inkooporder bijgewerkt");

            // ProductStock exporteren (indien van toepassing)
            ProductStock::exportStockToCSV();

            return redirect()->route('purchases.index')
                ->with('success', 'Inkooporder succesvol bijgewerkt.');
        });
    }

    
    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::with('purchaseOrderItems.product')->findOrFail($id);

        // Controleer of er een gekoppelde factuur is
        $invoice = Invoice::where('purchase_order_id', $purchaseOrder->id)->first();
        $hasInvoiceNotice = null;
        if ($invoice) {
            $hasInvoiceNotice = "Let op: deze inkooporder is gekoppeld aan factuur #{$invoice->number}.";
        }

        return DB::transaction(function () use ($purchaseOrder, $hasInvoiceNotice) {
            $status = $purchaseOrder->status;

            foreach ($purchaseOrder->purchaseOrderItems as $item) {
                $productStock = ProductStock::where('product_id', $item->product_id)->first();
                if ($productStock) {
                    if ($status <= 1) {
                        $newOnTheWay = $productStock->on_the_way_quantity - $item->quantity;
                        ProductStock::updateStock(
                            $item->product_id,
                            $newOnTheWay,
                            'on_the_way_quantity',
                            "Inkooporder {$purchaseOrder->id} geannuleerd (on_the_way verminderd)",
                            auth()->id(),
                            'OUT'
                        );
                    } else {
                        $newCurrent = $productStock->current_quantity - $item->quantity;
                        ProductStock::updateStock(
                            $item->product_id,
                            $newCurrent,
                            'current_quantity',
                            "Inkooporder {$purchaseOrder->id} geannuleerd (current verminderd)",
                            auth()->id(),
                            'OUT'
                        );
                    }
                }
                $item->delete();
            }

            activity()
                ->performedOn($purchaseOrder)
                ->withProperties(['action' => 'deleted'])
                ->log("Inkooporder verwijderd");

            $purchaseOrder->delete();

            ProductStock::exportStockToCSV();

            $message = 'Inkooporder succesvol verwijderd.';
            if ($hasInvoiceNotice) {
                $message .= ' ' . $hasInvoiceNotice;
            }

            return redirect()->route('purchases.index')
                ->with('success', $message);
        });
    }
    public function items($purchaseOrderId)
    {
        // Eerst de PurchaseOrder als model-instantie ophalen
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
    
        // Nu kun je de relatie 'purchaseOrderItems' aanroepen op de instantie
        $items = $purchaseOrder->purchaseOrderItems()
            ->with('product')
            ->get()
            ->map(function ($item) {
                // Zoek de parameter voor de BTW op basis van het product's vat_rate_id
                $vatParameter = \App\Models\Parameter::where('key', 'vat_rate')
                    ->where('id', $item->product->vat_rate_id)
                    ->first();
    
                // Gebruik de waarde uit de parameter of een fallback (bijvoorbeeld 21)
                $item->tax_rate = $vatParameter ? $vatParameter->value : 21;
                return $item;
            });
    
        return response()->json([
            'date'  => $purchaseOrder->date,
            'items' => $items,
        ]);
    }
    
    
    
    /**
     * Hulpfunctie voor afronden.
     */
    public function roundPrice($value)
    {
        return round($value, 2);
    }
}

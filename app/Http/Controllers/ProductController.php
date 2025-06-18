<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Subgroup;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Price;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;


class ProductController extends Controller
{
    /**
    * Woo logica
    */

    protected Client $wc;

    public function __construct()
    {
        // Voor dry-run export gebruiken we Woo API-client alleen voor structuur/api credentials
        $this->wc = new Client(
            config('woocommerce.url'),
            config('woocommerce.consumer_key'),
            config('woocommerce.consumer_secret'),
            [
                'version'           => config('woocommerce.version'),
                'verify_ssl'        => config('woocommerce.verify_ssl'),
                'query_string_auth' => config('woocommerce.query_string_auth'),
            ]
        );
    }

    
    /**
     * Overzicht van producten met filters en sortering.
     */
    public function index(Request $request)
    {
        $selectedProductIds   = $request->get('product_ids', []);
        $search               = $request->get('search');
        $subgroupId           = (array)$request->get('subgroup', []);
        $brandId              = (array)$request->get('brand', []);
        $stockStatus          = $request->get('stock_status');
        $articleStatusesFilter= (array)$request->get('status', []);
        $locationFilter       = (array)$request->get('location', []);
        $sortField            = $request->get('sort_field', 'id');
        $sortDirection        = $request->get('sort_direction', 'desc');
        $perPage              = $request->get('per_page', 25);

        // Ophalen van filters en parameters
        $locations      = Product::select('location')->distinct()->whereNotNull('location')->pluck('location')->toArray();
        $locationIds    = Parameter::where('key', 'location')->get();
        $articleStatuses= Parameter::where('key', 'article_status')->get();
        $subgroups      = Subgroup::all();
        $brands         = Brand::all();

        $query = Product::query()
            ->with(['prices' => function ($query) {
                $query->orderBy('valid_from', 'desc');
            }, 'brand', 'subgroup', 'stock']);

        // Zoekterm
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%");
            });
        }

        // Filter op subgroep en merk
        if (!empty($subgroupId)) {
            $query->whereIn('subgroup_id', $subgroupId);
        }
        if (!empty($brandId)) {
            $query->whereIn('brand_id', $brandId);
        }

        // Filter op artikelstatus
        if (!empty($articleStatusesFilter)) {
            $query->whereIn('status', $articleStatusesFilter);
        }

        // Filter op locatie
        $filterValue = !empty($locationFilter) ? $locationFilter[0] : null;
        if ($filterValue === 'none') {
            $query->whereNull('location');
        } elseif ($filterValue === 'allocated') {
            $query->whereNotNull('location');
        } elseif ($filterValue && $filterValue !== 'all') {
            $query->whereIn('location', $locationFilter);
        }

        // Filter op voorraadstatus
        if ($stockStatus) {
            $query->whereHas('stock', function ($q) use ($stockStatus) {
                if ($stockStatus == 'none') {
                    $q->where('current_quantity', 0);
                } elseif ($stockStatus == 'low') {
                    $q->where('current_quantity', '<', 3);
                } elseif ($stockStatus == 'available') {
                    $q->where('current_quantity', '>=', 3);
                }
            });
        }

        // Specifieke sortering (prijs, SKU, voorraad, etc.)
        if ($sortField === 'price') {
            $query->leftJoin('prices', function ($join) {
                $join->on('products.id', '=', 'prices.product_id')
                     ->where('prices.type', '=', 'regular')
                     ->whereNull('prices.valid_till');
            })->selectRaw('products.*, prices.price as price_sort')
              ->orderBy('price_sort', $sortDirection);
        } elseif ($sortField === 'sku') {
            $query->orderByRaw('CAST(sku AS UNSIGNED) ' . $sortDirection);
        } elseif ($sortField === 'stock') {
            $query->leftJoin('product_stock', 'products.id', '=', 'product_stock.product_id')
                  ->selectRaw('products.*, product_stock.current_quantity as stock_sort')
                  ->orderBy('stock_sort', $sortDirection);
        } elseif ($sortField === 'brand_id') {
            $query->join('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $sortDirection);
        } elseif ($sortField === 'subgroup_id') {
            $query->join('subgroups', 'products.subgroup_id', '=', 'subgroups.id')
                  ->orderBy('subgroups.name', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $products = $query->paginate($perPage)->appends($request->query());

        return view('products.index', compact(
            'products', 'subgroups', 'brands', 'locations', 'locationIds', 'sortField',
            'sortDirection', 'perPage', 'articleStatuses', 'selectedProductIds'
        ));
    }

    /**
     * Toon het bewerkformulier voor een product.
     */
    public function edit(Product $product)
    {
        // Door beide datalagen te mergen krijgen we alle benodigde gegevens
        return view('products.edit', array_merge($this->getProductData($product), $this->getFormData()));
    }

    /**
     * Toon het formulier voor een nieuw product.
     */
    public function create()
    {
        return view('products.create', $this->getFormData());
    }

    /**
     * Toon de bulk-edit view voor een selectie van producten.
     */
    public function bulkEdit(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        activity()->log('BulkEdit view geopend voor producten: ' . implode(',', $ids));
        
        $products = Product::whereIn('id', $ids)->get();
        $vatRates = Parameter::where('key', 'vat_rate')->get();
        if ($products->isEmpty()) {
            return redirect()->route('products.index')
                ->with('error', 'Geen producten geselecteerd.');
        }
    
        $productsData = $products->map(fn($product) => $this->getProductData($product, true));
        
        return view('products.bulkUpdate', array_merge($this->getFormData(), ['productsData' => $productsData], ['vatRates' => $vatRates]));
    }
    
    /**
     * Verwerk de bulk-update van producten.
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getBulkUpdateValidationRules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $webChannel = Setting::where('key','sales_channel_web')->value('value');
        
        DB::transaction(function () use ($request, $webChannel){
            foreach ($request->products as $productData) {
                $product = Product::where('sku', $productData['sku'])->first();
                if (!$product) {
                    continue;
                }
                
                // Als primary_category_id niet is ingesteld, haal de eerste categorie uit de array
                if (empty($productData['primary_category_id']) && !empty($productData['categories'])) {
                    $productData['primary_category_id'] = $productData['categories'][0];
                }
                // Doe hetzelfde voor category_id
                if (empty($productData['category_id']) && !empty($productData['categories'])) {
                    $productData['category_id'] = $productData['categories'][0];
                }
                
                $product->update($productData);
                
                // Als prijsvelden aanwezig zijn, update of maak nieuwe prijsrecord(s)
                if (isset($productData['regular_price'])) {
                    Price::updateOrCreate(
                        ['product_id' => $product->id, 'type' => 'regular'],
                        ['price' => $productData['regular_price'], 'valid_from' => now()]
                    );
                }
                if (isset($productData['vinted_price'])) {
                    Price::updateOrCreate(
                        ['product_id' => $product->id, 'type' => 'vinted'],
                        ['price' => $productData['vinted_price'], 'valid_from' => now()]
                    );
                }
                activity()->performedOn($product)
                    ->log("BulkUpdate: Product bijgewerkt. SKU: {$product->sku}");

                     // trigger Woo‐sync als hij voor “web” beschikbaar is
                if ($product->available_for_web
                && in_array($webChannel, (array)$product->sales_chanel)
                ) {
                    $this->syncProductToWoo($product);
                }
            }
        });
        
        activity()->log('BulkUpdate uitgevoerd voor producten.');
        
        return redirect()->route('products.index')
            ->with('success', 'Producten succesvol bijgewerkt.');
    }

    
    /**
     * Maak een nieuw product aan.
     */
    public function store(Request $request)
    {
        $request['available_for_web'] = $request->has('available_for_web');

        $validated = $request->validate($this->getProductValidationRules('store'));

        // Vul primary_category_id (en category_id) automatisch in op basis van de eerste categorie
        $validated['primary_category_id'] = $validated['categories'][0] ?? null;
        $validated['category_id'] = $validated['categories'][0] ?? null;

        // Genereer de SKU indien nodig
        $sku = $this->generateSKU();
        $validated['sku'] = $sku;

        // Creëer het product
        $product = Product::create($validated);
        // Creëer de reguliere prijs, indien meegegeven; anders stel standaard 0 in
        $regularPrice = isset($validated['regular_price']) ? $validated['regular_price'] : 0;
        Price::create([
            'product_id' => $product->id,
            'price'      => $regularPrice,
            'type'       => 'regular',
            'valid_from' => now(),
        ]);

        // Indien een vinted prijs is meegegeven en deze verschilt van de reguliere prijs:
        if (isset($validated['vinted_price'])) {
            Price::create([
                'product_id' => $product->id,
                'price'      => $validated['vinted_price'],
                'type'       => 'vinted',
                'valid_from' => now(),
            ]);
        }else{
            Price::create([
                'product_id' => $product->id,
                'price'      => $regularPrice,
                'type'       => 'vinted',
                'valid_from' => now(),
            ]);
        }

        // Controleer sales channel 'web'
        $webChannel = Setting::where('key', 'sales_channel_web')->value('value');
        $channels   = is_string($product->sales_chanel)
                      ? json_decode($product->sales_chanel, true)
                      : $product->sales_chanel;
        if (in_array($webChannel, (array)$channels)) {
            $this->dryRunExport($product, 'update');
        }
        // Audit trail voor productcreatie (en prijscreatie gebeurt via Price model indien dat is ingesteld)
        activity()->performedOn($product)
            ->log('Product en prijzen aangemaakt via store functie');

            return redirect()->route('products.index')
            ->with('success', 'Product is succesvol toegevoegd.');
    }
    /**
     * Werk een bestaand product bij.
     */
    public function update(Request $request, Product $product)
    {
        $request['available_for_web'] = $request->has('available_for_web');
    
        $validated = $request->validate($this->getProductValidationRules('update'));
    
        // Stel de primaire categorie in op basis van de eerste categorie in de array
        $validated['primary_category_id'] = $validated['categories'][0] ?? null;
        $request['category_id'] = $validated['categories'][0] ?? null;
    
        $product->update(array_merge($validated, [
            'categories' => $validated['categories'],
            'primary_category_id' => $validated['primary_category_id'],
        ]));
    
        if ($request->tags) {
            $this->syncTags($product, $request->tags);
        }
        // Indien prijsvelden worden meegegeven in de update (optioneel):
        if (isset($validated['regular_price'])) {
            // Update of maak een nieuwe prijs, bijvoorbeeld via Price::updateOrCreate(...)
            Price::updateOrCreate(
                ['product_id' => $product->id, 'type' => 'regular'],
                ['price' => $validated['regular_price'], 'valid_from' => now()]
            );
        }
        if (isset($validated['vinted_price'])) {
            Price::updateOrCreate(
                ['product_id' => $product->id, 'type' => 'vinted'],
                ['price' => $validated['vinted_price'], 'valid_from' => now()]
            );
        }

        // Controleer sales channel 'web'
        $webChannel = Setting::where('key', 'sales_channel_web')->value('value');
        $channels   = is_string($product->sales_chanel)
                      ? json_decode($product->sales_chanel, true)
                      : $product->sales_chanel;
        if (in_array($webChannel, (array)$channels)) {
            $this->dryRunExport($product, 'update');
        }

        activity()->performedOn($product)
            ->log('Product bijgewerkt via update functie');
    
        return redirect()->route('products.index')
            ->with('success', 'Product succesvol bijgewerkt.');
    }
    
    /**
     * Genereer een nieuwe SKU. Let op: dit voorbeeld gaat ervan uit dat SKU's numeriek zijn.
     */  
    /*
     private function generateSKU(): string
     {
         // Haal alle SKU's op
         $skus = Product::pluck('sku')->toArray();
         $maxNum = 0;
         $bestPrefix = '';
 
         foreach ($skus as $sku) {
             // Zoek naar prefix en laatst nummer
             if (preg_match('/^(.*?)(\d+)$/', $sku, $matches)) {
                 $prefix = $matches[1];
                 $num    = intval($matches[2]);
 
                 if ($num > $maxNum) {
                     $maxNum     = $num;
                     $bestPrefix = $prefix;
                 }
             }
         }
 
         // Bepaal volgende
         $nextNum = $maxNum + 1;
         return $bestPrefix . $nextNum;
     }*/
     private function generateSKU(): string
{
    // 1) Bij écht geen producten: eerste SKU = "1"
    if (Product::count() === 0) {
        return '1';
    }

    // 2) Bepaal hoogste pure numerieke SKU (geen prefix, geen negatief)
    $maxNumeric = (int) Product::query()
        ->whereRaw('sku REGEXP "^[0-9]+$"')
        ->selectRaw('MAX(CAST(sku AS UNSIGNED)) as max_num')
        ->value('max_num');

    // 3) Bepaal hoogste prefixed SKU (letterprefix + cijfers)
    $lastPrefixed = Product::query()
        ->whereRaw('sku REGEXP "^[A-Za-z]+[0-9]+$"')
        ->orderByRaw('CAST(REGEXP_REPLACE(sku, "^[A-Za-z]+", "") AS UNSIGNED) DESC')
        ->value('sku');

    $bestPrefix = '';
    $maxPrefNum = 0;
    if ($lastPrefixed && preg_match('/^([A-Za-z]+)(\d+)$/', $lastPrefixed, $m)) {
        $bestPrefix = $m[1];
        $maxPrefNum  = (int) $m[2];
    }

    // 4) Als beide 0 zijn: ongeldige data, gooi exception
    if ($maxNumeric === 0 && $maxPrefNum === 0) {
        throw new \RuntimeException('Geen geldige numerieke SKU’s gevonden om op verder te bouwen.');
    }

    // 5) Kies de hoogste en verhoog
    if ($maxNumeric >= $maxPrefNum) {
        return (string) ($maxNumeric + 1);
    }

    return $bestPrefix . ($maxPrefNum + 1);
}

    /**
     * Maak kopieën van een product.
     */
    public function copy(Request $request, $id)
    {
        $quantity = $request->get('quantity', 1);
        $product = Product::findOrFail($id);
        $ids = [];

        DB::transaction(function () use ($quantity, $product, &$ids) {
            for ($i = 0; $i < $quantity; $i++) {
                // Kopieer het product
                $newProduct = $product->replicate();
                $newProduct->sku = $this->generateSKU();
                $newProduct->woo_id = null; // Reset de Woo ID
                $newProduct->save();

                // Kopieer tags
                $newProduct->tags()->sync($product->tags->pluck('id'));
    
                // Kopieer prijzen
                foreach ($product->prices as $price) {
                    Price::create([
                        'product_id' => $newProduct->id,
                        'price'      => $price->price,
                        'type'       => $price->type,
                        'valid_from' => now(),
                    ]);
                }

                // Controleer sales channel 'web'
                $webChannel = Setting::where('key', 'sales_channel_web')->value('value');
                $channels   = is_string($product->sales_chanel)
                              ? json_decode($product->sales_chanel, true)
                              : $product->sales_chanel;
                if (in_array($webChannel, (array)$channels)) {
                    $this->dryRunExport($product, 'update');
                }
    
                $ids[] = $newProduct->id;
            }
        });
    
        return redirect()->route('products.bulkEdit', ['ids' => implode(',', $ids)]);
    }
    
    /**
     * Toon de galerij van een product.
     */
    public function gallery($id)
    {
        $product = Product::with('imageLinks.image')->findOrFail($id);
        return view('products.gallery', compact('product'));
    }
  
    /**
     * Toon de detailpagina van een product, met berekeningen en statistieken.
     */
    public function show($id)
    {
        $product = Product::with([
            'prices', 'supplier', 'latestPurchaseItem', 'purchaseOrderItems',
            'orderItems', 'stock', 'tags', 'imageLinks.image'
        ])->findOrFail($id);

        // Haal extra parametergegevens op
        $product->locationName = Parameter::where('value', $product->location)->value('name');

        $images = $product->imageLinks->map(fn($link) => $link->image)->unique('location');

        // Actieve prijzen (zonder valid_till)
        $activePrices = $product->prices->filter(fn($price) => is_null($price->valid_till));
        $regularPrice = $activePrices->where('type', 'regular')->first();
        $product->regularPrice = $regularPrice ? $regularPrice->price : 0;
        $vintedPrice = $activePrices->where('type', 'vinted')->first();
        $product->vintedPrice = $vintedPrice ? $vintedPrice->price : $product->regularPrice;

        // Bereken marges (met fallback indien er geen aankoopitem is)
        $latestPurchase = $product->latestPurchaseItem;
        $costPrice = $latestPurchase->price_incl_unit ?? 0;
        $product->regularPriceMargin = $this->calculateMargin($product->regularPrice, $costPrice);
        $product->vintedPriceMargin  = $this->calculateMargin($product->vintedPrice, $costPrice);

        // Laatste prijs update
        $product->last_price_update = $activePrices->max('valid_from') ?? null;

        // Voorraadstatistieken
        $firstPurchaseOrderItem = $product->purchaseOrderItems->first();
        $firstAvailableDate = $firstPurchaseOrderItem ? $firstPurchaseOrderItem->purchaseOrder->date : null;
        if ($firstAvailableDate) {
            $firstAvailableDate = Carbon::parse($firstAvailableDate);
            $weeksInAssortment = round($firstAvailableDate->diffInWeeks(Carbon::now()));
        } else {
            $weeksInAssortment = 0;
        }
        $product->weeksInAssortment = $weeksInAssortment;
        $product->total_orders = $product->orderItems->count() ?? 0;

        $product->average_sales_per_week = $weeksInAssortment > 0
            ? ($product->totalSales() / $weeksInAssortment)
            : 0;
        $product->average_sales_per_order = $product->total_orders > 0
            ? ($product->totalSales() / $product->total_orders)
            : 0;
        $product->totalSales   = $product->totalSales() ?? 0;
        $product->totalRevenue = $product->totalRevenue() ?? 0;
        $categoryIds = is_array($product->categories)
        ? $product->categories
        : json_decode($product->categories ?? '[]', true);
    
    $categories = Category::whereIn('id', $categoryIds)
        ->pluck('name', 'id')
        ->toArray();
    
    
        // Haal overige parameters op
        $sales_chanels = Parameter::where('key', 'sales_chanel')->get();
        $articleStatus = Parameter::where('key', 'article_status')
            ->where('name', $product->status)
            ->value('value');
        $product->product_type = Parameter::where('key', 'product_type')
            ->where('id', $product->product_type)
            ->value('name');
        $product->shipping_class = Parameter::where('key', 'shipping_class')
            ->where('id', $product->shipping_class)
            ->value('name');
        $product->locationName = Parameter::where('key', 'location')
            ->where('value', $product->location)
            ->value('name');
    

        // Voeg de ontbrekende variabelen toe
        $subgroups = Subgroup::all();
        $brands = Brand::all();

        // Groepeer verkochte items per week
        $weeklySalesData = $product->orderItems
            ->groupBy(fn($item) => Carbon::parse($item->order->date)
                ->startOfWeek()->format('Y-\WW'))
            ->map(fn($group, $week) => [
                'period' => $week,
                'afzet'  => $group->sum('quantity'),
                'omzet'  => $group->sum(fn($i) => $i->calculated_sales_price),
            ])->sortKeys()->values();

        // Groepeer verkochte items per maand
        $monthlySalesData = $product->orderItems
            ->groupBy(fn($item) => Carbon::parse($item->order->date)
                ->format('Y-m'))
            ->map(fn($group, $month) => [
                'period' => $month,
                'afzet'  => $group->sum('quantity'),
                'omzet'  => $group->sum(fn($i) => $i->calculated_sales_price),
            ])->sortKeys()->values();
       
        return view('products.show', compact(
            'product', 'images', 'sales_chanels', 'categories', 'articleStatus',
            'subgroups', 'brands', 'weeklySalesData', 'monthlySalesData'
        ));
    }

    
    /**
     * Verwijder een product als het niet gekoppeld is aan bestellingen of inkooporders.
     */
    public function destroy(Product $product, Request $request)
    {
        if ($product->orderItems()->exists() || $product->purchaseOrderItems()->exists()) {
            return redirect()
                ->route('products.index', $request->query())
                ->with('error', 'Product kan niet worden verwijderd omdat het gekoppeld is aan bestellingen of inkooporders. Saneer het artikel of zet geforceerd op vervallen.');
        }
        
        // Log het verwijderen van het product
        activity()->performedOn($product)
            ->log("Product verwijderd. SKU: {$product->sku}");
        
        $product->delete();
        
        return redirect()
            ->route('products.index', $request->query())
            ->with('success', 'Product succesvol verwijderd.');
    }
    
    /**
     * Zoek producten voor autocomplete/zoekfunctionaliteit.
     */
    public function search(Request $request)
    {
        $queryParam = $request->input('query');
        $inStock    = $request->input('inStock', 'true');
        $selectedProducts = $request->input('selectedProducts', []);
    
        if (empty($queryParam)) {
            return response()->json([]);
        }
    
        $products = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.brand_id',
                'products.subgroup_id',
                'products.purchase_quantity',
                'products.sale_quantity',
                'prices.price as sell_price',
                'subgroups.name as subgroup_name',
                'brands.name as brand_name',
                'product_stock.current_quantity as stock',
                DB::raw('(SELECT price_incl_bulk FROM purchase_order_items WHERE purchase_order_items.product_id = products.id ORDER BY created_at DESC LIMIT 1) as last_purchase_price'),
                DB::raw('(SELECT price_incl_unit FROM purchase_order_items WHERE purchase_order_items.product_id = products.id ORDER BY created_at DESC LIMIT 1) as last_purchase_unit_price'),
            ])
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('subgroups', 'subgroups.id', '=', 'products.subgroup_id')
            ->leftJoin('prices', function ($join) {
                $join->on('prices.product_id', '=', 'products.id')
                     ->where('prices.type', '=', 'regular')
                     ->whereNull('prices.valid_till');
            })
            ->leftJoin('product_stock', 'product_stock.product_id', '=', 'products.id')
            ->whereNotIn('products.id', $selectedProducts);
    
        if ($inStock === "true") {
            $products->where('product_stock.current_quantity', '>', 0);
        }
    
        $products->where(function ($query) use ($queryParam) {
            $query->where('products.name', 'like', "%{$queryParam}%")
                  ->orWhere('subgroups.name', 'like', "%{$queryParam}%")
                  ->orWhere('brands.name', 'like', "%{$queryParam}%")
                  ->orWhere('products.sku', 'like', "%{$queryParam}%");
        });
    
        $products->distinct();
    
        return response()->json($products->get());
    }
    
    /**
     * Update de status van producten op basis van SKU's.
     */
    public function updateStatus(Request $request)
{
    $productSkusRaw = $request->input('product_skus')[0] ?? null;
    $skus = is_string($productSkusRaw) ? json_decode($productSkusRaw, true) : [];
    
    if ($skus === null) {
        return redirect()->back()->with('error', 'Ongeldig formaat voor product_skus.');
    }
    
    $newStatus = $request->input('new_status');
    $result = Product::bulkUpdateStatusBySkus($skus, $newStatus);
    
    // Specifieke logging per geüpdatet product
    foreach ($result['updated'] as $updatedProduct) {
        activity()->performedOn($updatedProduct)
            ->log("Status geüpdatet naar {$updatedProduct->status} via updateStatus.");
    }
    
    activity()->log("Bulk status update uitgevoerd voor SKU's: " . implode(',', $skus));
    
    return redirect()->back()->with([
        'success' => $this->formatSuccessMessage($result['updated']),
        'error'   => $this->formatErrorMessage($result['notAllowed']),
    ]);
}

    
    /**
     * Ken een locatie toe aan een product.
     */
    public function assignLocation($productSku, $location)
    {
        $product = Product::where('sku', $productSku)->first();
        if (!$product) {
            return redirect()->back()->with('error', 'Product niet gevonden.');
        }
        $product->location = $location;
        $product->save();
        return redirect()->back()->with('success', 'Locatie succesvol toegewezen.');
    }
    
    /**
     * Bulk ken een locatie toe aan producten.
     */
    public function bulkAssignLocation(Request $request)
    {
        $productSkus = $request->get('product_skus', []);
        $location = $request->get('new_location');
    
        if (count($productSkus) === 1 && is_string($productSkus[0])) {
            $productSkus = json_decode($productSkus[0], true);
        }
    
        if (!empty($productSkus) && !empty($location)) {
            Product::whereIn('sku', $productSkus)->update(['location' => $location]);
        }
    
        return redirect()->back()->with('success', 'Locaties succesvol toegewezen.');
    }
    
    /**
     * Update een veld (vinted_title of vinted_description) via AJAX.
     */
    public function updateVintedField(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'field'      => 'required|in:vinted_title,vinted_description',
        'value'      => 'nullable|string',
    ]);
    
    $product = Product::findOrFail($request->product_id);
    $product->{$request->field} = $request->value;
    $product->save();
    
    // Specifieke log voor update van het vinted veld
    activity()->performedOn($product)
        ->log("Vinted field '{$request->field}' bijgewerkt naar '{$request->value}' via updateVintedField.");
    
    return response()->json(['success' => true]);
}

    
    /**
     * Bereken de marge (in procenten) op basis van verkoop- en inkoopprijs.
     */
    private function calculateMargin($salePrice, $costPrice)
    {
        if ($costPrice == 0 || $salePrice == 0) {
            return 0;
        }
        return round((($salePrice - $costPrice) / $salePrice) * 100, 2);
    }
    
    /**
     * Formatteer een succesbericht op basis van de bijgewerkte producten.
     */
    private function formatSuccessMessage($updates)
    {
        return collect($updates)
            ->map(fn($product) => "SKU: {$product->sku}, Status gewijzigd naar {$product->status}")
            ->join("\n");
    }
    
    /**
     * Formatteer een foutbericht op basis van de producten die niet bijgewerkt konden worden.
     */
    private function formatErrorMessage($errors)
    {
        return collect($errors)
            ->map(fn($error) => "SKU: {$error['sku']} {$error['name']}, Fout: {$error['reason']}")
            ->join("\n");
    }
    
    /**
     * Synchroniseer de tags voor een product.
     */
    private function syncTags(Product $product, ?string $tags)
    {
        if (!$tags) {
            $product->tags()->detach();
            return;
        }
    
        $tagNames = explode(',', $tags);
        $tagIds = [];
    
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }
    
        $product->tags()->sync($tagIds);
    }
    
    /**
     * Bereid data voor het productform en de view voor.
     */
    private function getProductData(Product $product, $isBulk = false)
    {
        return [
            'product'          => $product,
            'primaryCategory'  => $product->category_id,
            'selectedCategory' => is_array($product->categories)
                ? $product->categories
                : ($product->categories instanceof \Illuminate\Support\Collection
                    ? $product->categories->pluck('id')->toArray()
                    : []
                ),
            'categories'       => Category::all(),
            'brands'           => Brand::all(),
            'subgroups'        => Subgroup::all(),
            'tags'             => Tag::all(),
            'suppliers'        => Supplier::all(),
            'shippingClasses'  => Parameter::where('key', 'shipping_class')->get(),
            'vatRates'         => Parameter::where('key', 'vat_rate')->get(),
            'productTypes'     => Parameter::where('key', 'product_type')->get(),
            // Let op: consistente naamgeving (sales_chanel in DB, salesChanels in de view)
            'salesChanels'     => is_string($product->sales_chanel)
                ? json_decode($product->sales_chanel, true)
                : $product->sales_chanel,
            'regularPrice'     => $product->prices->where('type', 'regular')->last()->price ?? null,
            'vintedPrice'      => $product->prices->where('type', 'vinted')->last()->price ?? null,
            'productTags'      => $product->tags->pluck('name')->toArray(),
            'locations'        => $isBulk ? Parameter::where('key', 'location')->get() : null
        ];
    }
    
    /**
     * Geef algemene form data (zoals lijsten met categorieën, merken, etc.) door aan de views.
     */
    private function getFormData()
    {
        return [
            'categories'      => Category::all(),
            'brands'          => Brand::all(),
            'subgroups'       => Subgroup::all(),
            'tags'            => Tag::all(),
            'suppliers'       => Supplier::all(),
            'locations'       => Parameter::where('key', 'location')->get(),
            'shippingClasses' => Parameter::where('key', 'shipping_class')->get(),
            'vatRates'        => Parameter::where('key', 'vat_rate')->get(),
            'productTypes'    => Parameter::where('key', 'product_type')->get(),
            'salesChanels'    => Parameter::where('key', 'sales_chanel')->get(),
        ];
    }
    
    /**
     * Retourneert de validatieregels voor het aanmaken of updaten van een product.
     *
     * @param string $context 'store' of 'update'
     * @return array
     */
    private function getProductValidationRules(string $context = 'store'): array
    {
        $rules = [
            'name'              => 'required|string|max:255',
            'sku'               => 'sometimes|string|max:255',
            'categories'        => 'required|array',
            'subgroup_id'       => 'required|exists:subgroups,id',
            'brand_id'       => 'required|exists:brands,id',
            'shipping_class'    => 'nullable|string|max:255',
            'purchase_quantity' => 'required|integer',
            'sale_quantity'     => 'required|integer',
            'height'            => 'nullable|numeric',
            'width'             => 'nullable|numeric',
            'depth'             => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'to_website'        => 'sometimes|boolean',
            'supplier_id'       => 'required|exists:suppliers,id',
            'short_description' => 'nullable|string',
            'long_description'  => 'nullable|string',
            'status'            => 'nullable|integer',
            'published'         => 'sometimes|boolean',
            'woo_id'            => 'nullable|integer',
            'product_type'      => 'required|string|max:255',
            'attributes'        => 'nullable|array',
            'seo_title'         => 'nullable|string|max:255',
            'seo_description'   => 'nullable|string',
            'focus_keyword'     => 'nullable|string|max:255',
            'vat_rate_id'       => 'required|exists:parameters,id',
            'available_for_web' => 'required|boolean',
            'sales_chanel'      => 'required|array',
            'location'          => 'nullable|string|max:255',
            'primary_category_id' => 'nullable|exists:categories,id',
            'bundled_items'     => 'nullable|array',
            'vinted_title'      => 'nullable|string|max:255',
            'vinted_description'=> 'nullable|string',
            'back_in_stock'     => 'sometimes|boolean',
            'regular_price'     => 'required',
            'vinted_price'      => 'nullable',
        ];
    
        if ($context === 'update') {
            // Extra regels voor update, indien nodig
        }
    
        return $rules;
    }
    
    /**
     * Retourneert de validatieregels voor bulk-update.
     *
     * @return array
     */
    private function getBulkUpdateValidationRules(): array
    {
        $baseRules = $this->getProductValidationRules();
        $rules = [];
        foreach ($baseRules as $field => $rule) {
            $rules["products.*.{$field}"] = $rule;
        }
        return $rules;
    }
   
    
    /**
     * Synchroniseert één product (create of update) met WooCommerce,
     * en slaat voorafgaand een dry-run JSON op.
     */
    private function syncProductToWoo(Product $product): void
    {
        // 1) Zorg dat alle benodigde relaties geladen zijn
        $product->load(['prices', 'stock', 'brand', 'tags']);
    
        // 2) Bouw de volledige payload
        $payload = $this->buildWooPayload($product);
    
        try {
            // 3) Als er nog geen woo_id is, zoek eerst in Woo op SKU
            if (empty($product->woo_id)) {
                $existing = $this->wc->get('products', ['sku' => $product->sku]);
    
                if (! empty($existing) && isset($existing[0]->id)) {
                    // a) Bestaat al: sla woo_id lokaal op en doe een update
                    $product->woo_id = $existing[0]->id;
                    $product->save();
    
                    $updatePayload = Arr::except($payload, [
                        'sku', 'type', 'status', 'catalog_visibility'
                    ]);
    
                    $resp = $this->wc->put("products/{$product->woo_id}", $updatePayload);
                    Log::channel('woocommerce')->info(
                        "Woo FALLBACK UPDATE response for SKU {$product->sku}",
                        (array) $resp
                    );
                    return;
                }
    
                // b) Bestaat niet: maak nieuw product aan
                $resp = $this->wc->post('products', $payload);
                Log::channel('woocommerce')->info(
                    "Woo CREATE response for SKU {$product->sku}",
                    (array) $resp
                );
    
                if (! empty($resp->id)) {
                    $product->woo_id = $resp->id;
                    $product->save();
                }
            } else {
                // 4) Gewone update
                $updatePayload = Arr::except($payload, [
                    'sku', 'type', 'status', 'catalog_visibility'
                ]);
    
                $resp = $this->wc->put("products/{$product->woo_id}", $updatePayload);
                Log::channel('woocommerce')->info(
                    "Woo UPDATE response for SKU {$product->sku}",
                    (array) $resp
                );
            }
        } catch (HttpClientException $e) {
            Log::channel('woocommerce')->error(
                "Woo sync failed for SKU {$product->sku}: " . $e->getMessage()
            );
        }
    }

    /**
     * Slaat een JSON dry-run bestand op met de payload voor create of update.
     */
    protected function dryRunExport(Product $product, string $action): array
    {
        // 1) Payload binnenhalen
        $payload = $this->buildWooPayload($product);
        $this->syncProductToWoo($product);  
        // 2) Timestamp en bestandsnaam
        $now      = Carbon::now()->format('YmdHis');
        $filename = "woo/dryrun-{$action}-{$product->id}-{$now}.json";
        $fullPath = storage_path("app/{$filename}");

        // 3) Directory aanmaken indien nodig
        File::ensureDirectoryExists(dirname($fullPath), 0755);

        // 4) JSON schrijven
        File::put(
            $fullPath,
            json_encode(['dry_run' => $payload], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Log::info("Dry-run export {$action}: {$fullPath}");
        return ['file' => $filename, 'dry_run' => $payload];
    }

        /**
     * Bouwt de array-payload voor zowel create als update.
     */
    private function buildWooPayload(Product $product): array
    {
        Log::info("Bouw payload voor product {$product->id}");
        // 1) Haal lokale ID’s uit de JSON-velden (cast als array)
        $localCatIds = is_array($product->categories)
            ? $product->categories
            : (json_decode($product->categories, true) ?: []);
       
        $tags = $product->tags
        ->map(fn($tag) => array_filter([
            'id'   => $tag->woo_id,
            'slug' => $tag->slug,
            'name' => $tag->name,
        ]))
        ->values()
        ->toArray();
            // 2) Vertaal lokaal → woo_id
        $categoryIds = Category::whereIn('id', $localCatIds)
            ->pluck('woo_id')
            ->filter()  
            ->unique()
            ->values()
            ->toArray();
        // 3) Prijs & voorraad
        $regularPrice = $product->prices()
            ->where('type', 'regular')
            ->orderByDesc('valid_from')
            ->value('price') ?? 0;
        $currentQty  = $product->stock?->current_quantity ?? 0;
        $manageStock = true;
    
        // 4) Bouw je payload in één array (nooit meerdere malen opnieuw $payload = [...])
        $payload = [
            'name'               => $product->name,
            'sku'                => $product->sku,
            'status'             => $product->published ? 'publish' : 'draft',
            'catalog_visibility' => $product->available_for_web ? 'visible' : 'hidden',
            'description'        => $product->long_description,
            'short_description'  => $product->short_description,
            'regular_price'      => (string) $regularPrice,
            'price'              => (string) $regularPrice,
            'manage_stock'       => $manageStock,
            'stock_quantity'     => $currentQty,
            'dimensions'         => [
                'length' => (string) $product->depth,
                'width'  => (string) $product->width,
                'height' => (string) $product->height,
            ],
            'weight'             => $product->weight
                                      ? (string) ($product->weight / 1000)
                                      : '',
            'shipping_class_id'  => $product->shipping_class,
            'brands'             => $product->brand
                                      ? (! empty($product->brand->woo_id)
                                          ? [['id'   => $product->brand->woo_id]]
                                          : [['slug' => $product->brand->slug]]
                                        )
                                      : [],
            // hier categories & tags in de vorm die Woo wil
            'categories' => array_map(
                fn($id) => ['id' => $id],
                $categoryIds
            ),
            'tags'               => $tags,
           /* 'tags'       => array_map(
                fn($id) => ['id' => $id],
                $tagIds
            ),*/
            // alleen bij create
            'type'       => empty($product->woo_id) ? 'simple' : null,
        ];
        
        
        // 5) Meta-data enkel met Rank Math velden + merknaam
        $customMeta = [
            'rank_math_description'            => $product->seo_description,
            'rank_math_focus_keyword'          => $product->focus_keyword,
            'rank_math_title'                  => $product->seo_title,
            'rank_math_primary_product_brand'  => optional($product->brand)->name,
        ];
       // dd($customMeta);
        $payload['meta_data'] = collect($customMeta)
            ->filter()   // wegfilteren van null/lege values
            ->map(fn($value, $key) => [
                'key'   => $key,
                'value' => $value,
            ])->values()->toArray();
    $isCreate = empty($product->woo_id);

    if ($isCreate) {
        // bij nieuw product altijd concept (‘draft’) en privé (‘hidden’ in catalog)
        $payload['status']             = 'draft';    // concept
        $payload['catalog_visibility'] = 'hidden';   // privé/verborgen
        $payload['type']               = 'simple';   // als je dat nog nodig hebt
    }
    
        // 6) Cleanup: verwijder null en lege arrays
        return array_filter($payload, fn($v) => !is_null($v) && $v !== []);
    }
}
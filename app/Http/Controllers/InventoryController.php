<?php

namespace App\Http\Controllers;

use App\Models\Parameter; // Zorg ervoor dat je het juiste model importeert
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductStockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
class InventoryController extends Controller
{
    public function index()
{
    // Ophalen van alle locaties (parameters met key 'location')
    $locations = Parameter::where('key', 'location')->get();

    // Ophalen van producten per locatie met status 70 of lager
    foreach ($locations as $location) {
        $location->lowStockProducts = Product::where('location', $location->value)
                                              ->where('status', '<=', 70)
                                              ->get();
                                              // Tel het aantal producten met status onder 90
        $location->linkedProductsCount = Product::where('location', $location->value)
        ->where('status', '<', 90)
        ->count();
    }
    return view('products.inventory.index', compact('locations'));
}

public function countBatch(Request $request, $location)
{
    // Controleer of de locatie geldig is
    if (empty($location)) {
        return redirect()->route('inventory.index')->with('error', 'Geen geldige locatie');
    }

    // Haal producten op die aan de locatie zijn gekoppeld
    $products = Product::query()
        ->with(['stock','brand']) // Zorg ervoor dat je de voorraad relatie ophaalt
        ->where('location', $location) // Filter op basis van de locatie
        ->select('id', 'sku', 'brand_id', 'location', 'status', 'name') // Selecteer de benodigde velden
        ->get();

    // Voeg voorraadgegevens toe aan elk product
    foreach ($products as $product) {
        $product->current_stock = $product->stock->current_quantity ?? 0; // Huidige voorraad
        $product->stock_in_transit = $product->stock->in_transit_quantity ?? 0; // Voorraad onderweg
        
    }

    return view('products.inventory.countBatch', compact('products', 'location'));
}
    public function backInStock(){

        $products = Product::where('back_in_stock', 1)->get();

        return view('products.inventory.backInStock', compact('products'));
    }

    public function procesInStock(Request $request)
    {
        // Valideer de inkomende data
        $data = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'back_in_stock' => 'required|boolean',
        ]);

        // Haal het product op
        $product = Product::find($data['product_id']);

        // Update de back_in_stock status
        $product->back_in_stock = $data['back_in_stock'];
        $product->save();

        // Geef een JSON response terug zodat de AJAX call weet dat het gelukt is
        return response()->json([
            'success'       => true,
            'product_id'    => $product->id,
            'back_in_stock' => $product->back_in_stock,
        ]);
    }

    public function updateStock(Request $request)
    {
        $stocks = $request->input('stocks', []);
        $updatedCount = 0;
        DB::transaction(function () use ($stocks, &$updatedCount) {
            foreach ($stocks as $productId => $newStock) {
                if($newStock === null){
                    continue;
                }
                $newStock = (int) $newStock; // Zorg dat het een integer is
                
                // Haal de huidige voorraad op
                $productStock = ProductStock::firstOrNew(['product_id' => $productId]);
    
                // Bereken het verschil tussen oude en nieuwe voorraad
                $difference = $newStock - ($productStock->current_quantity ?? 0);
                $oldStock = $productStock->current_quantity;
                 if ($difference !== 0) {
                    // Update de voorraad
                    if($difference > 0){
                        $action = 'IN';
                    }else{
                        $action = 'OUT';
                    }
                    ProductStock::updateStock(
                        $productId,
                        $newStock,
                        'current_quantity',
                        'Voorraad correctie. Van '. $oldStock .' naar .' . $newStock,
                        auth()->id(),
                        $action
                    );

                    $updatedCount++; // Houd bij hoeveel producten zijn aangepast
                }
            }
               });
        ProductStock::exportStockToCSV();

        return redirect()->route('inventory.index')->with('success', "$updatedCount producten zijn bijgewerkt.");
    }
    
    // Functie voor het bijwerken van een bestaande locatie
    public function update(Request $request, Parameter $location)
    {
        $this->validateLocation($request);

        // Controleer of de nieuwe waarde uniek is
        if (Parameter::where('value', $request->value)->where('id', '!=', $location->id)->exists()) {
            return back()->withErrors(['value' => 'De waarde moet uniek zijn. De gevraagde id "'.$request->value .'" van de locatie met naam '.$request->name .' bestaat al.']);
        }

        // Bijwerken van de producten met de nieuwe waarde
        if ($location->value != $request->value) {
            // Werk de locatie van de producten bij die aan deze locatie zijn gekoppeld
            Product::where('location', $location->value)->update(['location' => $request->value]);
        }

        // Update de locatie
        $location->update($request->all());
        ProductStock::exportStockToCSV();

        return redirect()->route('locations.index')->with('success', 'Locatie bijgewerkt.');
    }
}

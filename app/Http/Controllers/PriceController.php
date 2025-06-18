<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceController extends Controller
{
    public function index()
    {
        $prices = Price::with('product')->get();
        return view('prices.index', compact('prices'));
    }

    public function create()
    {
        $products = Product::all();
        return view('prices.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:regular,sale,vinted',
        ]);

        Price::updateActivePrice($request->input('product_id'), $request->input('price'), $request->input('type'));

        return redirect()->route('prices.index')->with('success', 'Prijs succesvol toegevoegd.');
    }

    public function edit(Price $price)
    {
        $products = Product::all();
        return view('prices.edit', compact('price', 'products'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
            'type' => 'required|string',
            'valid_from' => 'required|date',
        ]);

        try {
            $newPrice = $this->updatePrice(
                $validated['product_id'],
                $validated['price'],
                $validated['type'],
                $validated['valid_from']
            );

            return response()->json([
                'success' => true,
                'data' => $newPrice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400); // HTTP-statuscode 400 voor een slechte aanvraag
        }
    }
    public function updatePrice(Request $request)
    {
         $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric',
        ]);
    
        $productId = $validated['product_id'];
        $prices = $validated['prices'];
    
        // Reguliere prijs is verplicht
        if (!array_key_exists('regular', $prices) || is_null($prices['regular'])) {
            return response()->json([
                'success' => false,
                'message' => 'De reguliere prijs is verplicht.',
            ], 422);
        }
    
        $product = Product::findOrFail($productId);
    
        $currentPrices = Price::where('product_id', $productId)
            ->whereNull('valid_till')
            ->get()
            ->keyBy('type');
    
        $updates = [];
    
        // Reguliere prijs
        if ($currentPrices->has('regular') && $currentPrices['regular']->price != $prices['regular']) {
            $updates[] = 'Regulier van €' . number_format($currentPrices['regular']->price, 2, ',', '.') . ' naar €' . number_format($prices['regular'], 2, ',', '.');
        } else {
            $updates[] = 'Reguliere prijs blijft gelijk op €' . number_format($prices['regular'], 2, ',', '.');
        }
    
        $this->saveSinglePrice($productId, 'regular', now(), $prices['regular']);
    
        // Vinted prijs
        if (array_key_exists('vinted', $prices)) {
            if ($prices['vinted'] > 0) {
                if ($currentPrices->has('vinted') && $currentPrices['vinted']->price != $prices['vinted']) {
                    $updates[] = 'Vinted van €' . number_format($currentPrices['vinted']->price, 2, ',', '.') . ' naar €' . number_format($prices['vinted'], 2, ',', '.');
                }
                $this->saveSinglePrice($productId, 'vinted', now(), $prices['vinted']);
            } else {
                $this->terminatePrice($productId, 'vinted', now());
                if (isset($currentPrices['vinted'])) {
                    $updates[] = 'Vinted prijs verwijderd. Was €' . number_format($currentPrices['vinted']->price, 2, ',', '.');
                } else {
                    $updates[] = 'Vinted prijs was al leeg.';
                }
            }
        }
    
        activity()->performedOn($product)->log('Prijs bijgewerkt via Price Update Modal');
        
        return redirect()->route('products.show', $productId)
        ->with('success', implode("\n", $updates));
    
    }
    
    
    private function saveSinglePrice($productID, $type, $validFrom, $price)
    {
        if ($price === null || $type === null || $validFrom === null) {
    Log::warning('Ongeldige prijsdata', compact('price', 'type', 'validFrom'));
    return;
}

        // Zoek de huidige actieve prijs voor dit product en type
        $activePrice = Price::where('product_id', $productID)
            ->where('type', $type)
            ->whereNull('valid_till') // Alleen actieve prijzen
            ->first();

        if ($activePrice) {
            // Controleer of de nieuwe prijs gelijk is aan de huidige prijs
            if (round((float) $activePrice->price, 2) === round((float) $price, 2)) {

                // Geen actie nodig als de prijs gelijk blijft
                return; // Stop de functie als er niets hoeft te gebeuren
            }      

            // Sluit de huidige prijs af
            $activePrice->update(['valid_till' => $validFrom]);
        }

        // Maak de nieuwe prijs aan, maar alleen als de prijs niet null is
        if ($price !== null) {
            try {
                $newPrice = Price::create([
                    'product_id' => $productID,
                    'price' => $price,
                    'type' => $type,
                    'valid_from' => $validFrom,
                ]);
            } catch (\Exception $e) {
                Log::error("Fout bij aanmaken prijs: " . $e->getMessage(), [
                    'product_id' => $productID,
                    'price' => $price,
                    'type' => $type,
                    'valid_from' => $validFrom,
                ]);
            }
            
        }
    }

    private function terminatePrice($productID, $type, $validTill)
    {
        // Zoek de huidige actieve prijs voor dit product en type
        $activePrice = Price::where('product_id', $productID)
            ->where('type', $type)
            ->whereNull('valid_till') // Alleen actieve prijzen
            ->first();

        if ($activePrice) {
            // Sluit de huidige prijs af door de valid_till datum in te stellen
            $activePrice->update(['valid_till' => $validTill]);
        }
    }
    
    public function destroy(Price $price)
    {
        $price->delete();
        return redirect()->route('prices.index')->with('success', 'Prijs succesvol verwijderd.');
    }
}

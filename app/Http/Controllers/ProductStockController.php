<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Product;
use App\Jobs\SyncStockToWooCommerceJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProductStockController extends Controller
{
    /**
     * Toon een lijst van alle voorraaditems.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $stocks = ProductStock::with('product')->get(); // Haal alle voorraaditems op, inclusief bijbehorende producten
        return view('product_stocks.index', compact('stocks'));
    }

    /**
     * Toon de details van een specifieke voorraaditem.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $stock = ProductStock::with('product')->findOrFail($id); // Haal specifieke voorraad op
        return view('product_stocks.show', compact('stock'));
    }

    /**
     * Toon het formulier voor het aanmaken van een nieuwe voorraaditem.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('product_stocks.create'); // Toon de aanmaakpagina
    }

    /**
     * Sla een nieuwe voorraaditem op in de database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Valideer de invoerparameters
        $request->validate([
            'product_id' => 'required|exists:products,id', // Product moet bestaan
            'available_quantity' => 'required|integer|min:0', // Beschikbare hoeveelheid moet een positief geheel getal zijn
            'reserved_quantity' => 'required|integer|min:0', // Gereserveerde hoeveelheid moet een positief geheel getal zijn
            'blocked_quantity' => 'required|integer|min:0', // Geblokkeerde hoeveelheid moet een positief geheel getal zijn
            'stock_in_transit' => 'required|integer|min:0', // Voorraad in transit moet een positief geheel getal zijn
        ]);

        // Maak een nieuwe voorraaditem aan met de gevalideerde invoer
        ProductStock::create($request->all());

        return redirect()->route('product_stocks.index')
            ->with('success', 'Voorraad succesvol toegevoegd.'); // Succesbericht
    }

    /**
     * Toon het formulier voor het bewerken van een bestaande voorraaditem.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $stock = ProductStock::findOrFail($id); // Haal voorraaditem op
        return view('product_stocks.edit', compact('stock')); // Toon de bewerkingspagina
    }

    /**
     * Werk een bestaande voorraaditem bij in de database.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Valideer de invoerparameters
        $request->validate([
            'available_quantity' => 'required|integer|min:0', // Beschikbare hoeveelheid moet een positief geheel getal zijn
            'reserved_quantity' => 'required|integer|min:0', // Gereserveerde hoeveelheid moet een positief geheel getal zijn
            'blocked_quantity' => 'required|integer|min:0', // Geblokkeerde hoeveelheid moet een positief geheel getal zijn
            'stock_in_transit' => 'required|integer|min:0', // Voorraad in transit moet een positief geheel getal zijn
        ]);

        $stock = ProductStock::findOrFail($id); // Haal de voorraaditem op
        
        // Bewaar het product dat bij deze voorraad hoort
        $product = $stock->product; 

        // Update de voorraaditem met de gevalideerde invoer
        $stock->update($request->all());
        
        // Roep de functie aan om de status automatisch bij te werken
        $product->updateStatusAutomatically();
        ProductStock::exportStockToCSV();


        return redirect()->route('product_stocks.index')
            ->with('success', 'Voorraad succesvol bijgewerkt.'); // Succesbericht
    }

    /**
     * Verwijder een bestaande voorraaditem uit de database.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $stock = ProductStock::findOrFail($id); // Haal de voorraaditem op
        $stock->delete(); // Verwijder de voorraaditem

        return redirect()->route('product_stocks.index')
            ->with('success', 'Voorraad succesvol verwijderd.'); // Succesbericht
    }

    
    public function logUpdatedProducts(Request $request)
    {
        // Aantal minuten (bijvoorbeeld 10 minuten)
        $minutes = 1;
        
        // Haal producten op die zijn gewijzigd in de afgelopen X minuten
        $updatedProducts = ProductStock::where('updated_at', '>=', Carbon::now()->subMinutes($minutes))
            ->with('product') // Laad de productrelatie
            ->get();

        // Formateer de data in een JSON-structuur
        $productsData = $updatedProducts->map(function($stock) {
            return [
                'product_id' => $stock->product->id, // Product ID
                'sku' => $stock->product->sku, // SKU
                'quantity' => $stock->current_quantity, // Huidige voorraad
                'on_the_way_quantity' => $stock->on_the_way_quantity, // Onderweg voorraad
                'reserved_quantity' => $stock->reserved_quantity, // Gereserveerde voorraad
            ];
        });

        // Log de data naar de logs (je kunt deze later naar WooCommerce sturen)
        Log::info('Products updated in the last X minutes:', $productsData->toArray());

        // Hier kun je logica toevoegen om de JSON naar WooCommerce te sturen.
        // Voor nu loggen we de data naar het logbestand.
        
        return response()->json(['message' => 'Products data logged successfully.']);
    }
    public function exportStockToCSV()
    {
        {
            // Haal alle voorraadgegevens op
            $stocks = ProductStock::with('product')->get();
            Log::info('Start voorraad export');
        
            // Voorlopige buffers
            $csvContent = "SKU,Voorraad\n";
            $stockArray = [];
        
            foreach ($stocks as $stock) {
                if ($stock->product) {
                    $sku = $stock->product->sku;
                    $currentQuantity = $stock->current_quantity;
        
                    // Voor CSV
                    $csvContent .= "{$sku},{$currentQuantity}\n";
        
                    // Voor JSON
                    $stockArray[] = [
                        'sku' => $sku,
                        'current_quantity' => $currentQuantity,
                    ];
                }
            }
        
            // Bestandsnaam
            $fileName = 'stock_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
            // CSV opslaan in storage/app
            Storage::disk('local')->put($fileName, $csvContent);
        
            // Log de actie
            Log::info('Voorraad CSV succesvol gegenereerd: ' . $fileName);
        
            // JSON response terugsturen inclusief CSV bestandsnaam en voorraad
            return response()->json([
                'success' => true,
                'message' => 'Voorraad succesvol geëxporteerd',
                'file'    => $fileName,
                'stocks'  => $stockArray,
            ]);
        }
    }
    /**
     * De voorraad CSV naar WooCommerce sturen (optie om later te sturen).
     * 
     * @return \Illuminate\Http\Response
     */
    public function sendCSVToWooCommerce()
    {
        // Laad het bestand uit storage
        $filePath = storage_path('app/stock_export_*.csv'); // Gebruik een wild card om het nieuwste bestand te vinden
        $csvFile = glob($filePath)[0] ?? null;

        if (!$csvFile) {
            return response()->json([
                'success' => false,
                'message' => 'Geen CSV-bestand gevonden.',
            ]);
        }

        // Haal de CSV-content op
        $csvContent = file_get_contents($csvFile);

        // URL van je WooCommerce API (bijvoorbeeld)
        $url = 'https://papierenversier.nl/wp-json/woocommerce/v1/csv-upload';

        // Stel de API request in
        $response = \Http::post($url, [
            'csv_content' => $csvContent,
        ]);

        if ($response->successful()) {
            Log::info("CSV succesvol verstuurd naar WooCommerce.");
            return response()->json([
                'success' => true,
                'message' => 'CSV succesvol verstuurd naar WooCommerce.',
            ]);
        } else {
            Log::error("Fout bij het versturen van de CSV naar WooCommerce.");
            return response()->json([
                'success' => false,
                'message' => 'Fout bij het versturen van de CSV naar WooCommerce.',
            ]);
        }
    }

}

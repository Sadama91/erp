<?php
namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageLink;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Parameter;
use App\Models\Subgroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function index(Request $request)
{
    $query = Image::with('imageLinks.product'); // Voeg de productrelatie toe om te zoeken naar producten gelinkt aan afbeeldingen

    // Zoekfilter op naam, SKU, merk en tags
    if ($request->has('search') && $request->get('search') != '') {
        $query->whereHas('imageLinks.product', function ($query) use ($request) {
            $query->where('sku', 'like', '%' . $request->get('search') . '%')
                  ->orWhere('name', 'like', '%' . $request->get('search') . '%')
                  ->orWhereHas('brand', function ($query) use ($request) {
                      $query->where('name', 'like', '%' . $request->get('search') . '%');
                  })
                  ->orWhereHas('tags', function ($query) use ($request) {
                      $query->where('name', 'like', '%' . $request->get('search') . '%'); // Zoek ook op tags
                  });
        });
    }

    // Filteren op merk
    if ($request->has('brand') && $request->get('brand') != '') {
        $query->whereHas('imageLinks.product', function ($query) use ($request) {
            $query->where('brand_id', $request->get('brand'));
        });
    }

    // Filteren op tags
    if ($request->has('tags') && $request->get('tags')) {
        $query->whereHas('imageLinks.product', function ($query) use ($request) {
            $query->whereHas('tags', function ($query) use ($request) {
                $query->whereIn('tag_id', $request->get('tags'));
            });
        });
    }

    // Pagineren met de per_page parameter
    $perPage = $request->get('per_page', 25); // Standaard is 25 per pagina
    $images = $query->paginate($perPage);
    $images->appends($request->except('page'));  // Voeg alle queryparameters toe behalve de 'page'

    $articleStatuses = Parameter::where('key', 'article_status')->get();
    $subgroups = Subgroup::all();
    $brands = Brand::all();
    $tags = Tag::all();
    return view('image.index', compact('images','articleStatuses','subgroups','tags','brands'));
}

    // Functie om een CSV-bestand te uploaden
    public function uploadCSV(Request $request)
    {
        // Validatie van het geüploade bestand
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:10240',
        ]);

        // Het bestand opslaan
        $path = $request->file('csv_file')->storeAs('uploads', 'images.csv', 'public');

        // Verwerk de CSV
        $this->processCSV($path);

        return redirect()->route('image.index')->with('success', 'CSV-bestand geüpload en afbeeldingen verwerkt.');
    }

    public function processCSV($path)
    {
        $csvData = array_map('str_getcsv', file(storage_path('app/public/uploads/images.csv')));
    
        foreach ($csvData as $row) {
            $sku = $row[0]; // SKU
            $woo_id = $row[1]; // WOO ID
            $high_res_url = $row[2]; // High-res Image URL
            $low_res_url = $row[3]; // Low-res Image URL (Thumbnail)
            $image_type = $row[4]; // Image Type
            $linked_products = $row[5]; // Linked Products (in de vorm van een lijst van SKU's)
    
            // Zoek naar het product op basis van SKU
            $product = Product::where('sku', $sku)->first();
    
            if (!$product) {
                // Als het product niet wordt gevonden, gaan we door met de volgende afbeelding
                continue;
            }
    
            // Zoek naar de afbeelding (als deze al bestaat)
            $image = Image::where('location', $high_res_url)->first();
    
            if (!$image) {
                // Bepaal het mime type van de afbeeldingen op basis van de extensie
                $mime_type = $this->getMimeTypeFromExtension($high_res_url);
                $thumbnail_mime_type = $this->getMimeTypeFromExtension($low_res_url);
    
                // Maak een nieuwe afbeelding aan als deze niet bestaat
                $image = Image::create([
                    'location' => $high_res_url, // Hoofdafbeelding (High-res)
                    'thumbnail_location' => $low_res_url, // Thumbnail afbeelding (Low-res)
                    'description' => 'Imported via CSV',
                    'original_filename' => basename($high_res_url), // Voeg de originele bestandsnaam toe
                    'mime_type' => $mime_type, // Automatisch bepaald mime type voor high-res
                    'status' => 'active',
                    'uploaded_by' => auth()->id(),
                ]);
            }
    
            // Verwijder bestaande koppelingen van deze afbeelding voor dit product (indien van toepassing)
            // Voeg controle toe om te zorgen dat de koppeling alleen wordt verwijderd als de afbeelding bestaat
            if ($image) {
                ImageLink::where('image_id', $image->id)->where('product_id', $product->id)->delete();
            }
    
            // Koppel de afbeelding aan het product
            ImageLink::create([
                'image_id' => $image->id,
                'product_id' => $product->id,
                'role' => $image_type,
                'publication' => 1,
                'order' => 1,
            ]);
    
            // Verwerk de gelinkte producten
            foreach (explode(',', $linked_products) as $linked_sku) {
                $linked_product = Product::where('sku', trim($linked_sku))->first();
                if ($linked_product) {
                    ImageLink::create([
                        'image_id' => $image->id,
                        'product_id' => $linked_product->id,
                        'role' => 'Secondary',
                        'publication' => 1,
                        'order' => 2,
                    ]);
                }
            }
        }
    }
    

    // Functie om een afbeelding te tonen
    public function show($id)
    {
        $image = Image::with('imageLinks')->findOrFail($id);
        return view('image.show', compact('image'));
    }

    // Functie om een afbeelding bij te werken
    public function update(Request $request, $id)
    {
        $request->validate([
            'location' => 'required|url', // URL validatie
            'description' => 'nullable|string',
            'status' => 'required|string',
        ]);

        $image = Image::findOrFail($id);
        $image->update($request->all());

        return redirect()->route('image.index')->with('success', 'Afbeelding succesvol bijgewerkt.');
    }

    // Functie om een afbeeldingkoppeling te verwijderen (niet het bestand zelf)
    public function destroy($imageID)
    {
        $image = Image::find($imageID);

        if (!$image) {
            return redirect()->route('image.index')->with('error', 'Afbeelding niet gevonden.');
        }

        // Verwijder de gekoppelde relaties
        ImageLink::where('image_id', $image->id)->delete();

        // Verwijder de afbeelding zelf uit de database
        $image->delete();

        return redirect()->route('image.index')->with('success', 'Afbeelding succesvol verwijderd.');
    }

    private function getMimeTypeFromExtension($fileUrl)
{
    $ext = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));

    // Mime type afleiden op basis van de extensie
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    return $mimeTypes[$ext] ?? 'application/octet-stream'; // Default naar een algemeen mime type als de extensie niet wordt herkend
}

}

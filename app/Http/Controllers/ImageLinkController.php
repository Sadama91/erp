<?php
namespace App\Http\Controllers;

use App\Models\ImageLink;
use Illuminate\Http\Request;

class ImageLinkController extends Controller
{
    public function index()
    {
        $imageLinks = ImageLink::all();
        return response()->json($imageLinks);
    }

    // Deze methode kan blijven voor het koppelen van een afbeelding aan een product.
    public function store(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:images,id',
            'product_id' => 'required|exists:products,id',
            'role' => 'required|string|max:255',
            'publication' => 'required|boolean',
            'order' => 'required|integer',
        ]);

        $imageLink = ImageLink::create($request->all());
        return response()->json($imageLink, 201);
    }

    // Functie om een koppeling van een afbeelding naar een product te verwijderen
    public function destroy($id)
    {
        $imageLink = ImageLink::findOrFail($id);

        // Verwijder de afbeelding-koppeling
        $imageLink->delete();

        return response()->json(null, 204); // HTTP 204 No Content
    }

    // Functie om een afbeeldingkoppeling bij te werken
    public function update(Request $request, $id)
    {
        $imageLink = ImageLink::findOrFail($id);

        // Validatie van de inkomende gegevens
        $request->validate([
            'role' => 'required|string|max:255',
            'publication' => 'required|boolean',
            'order' => 'required|integer',
        ]);

        // Bijwerken van de afbeeldingkoppeling
        $imageLink->update($request->all());

        return response()->json($imageLink);
    }
}

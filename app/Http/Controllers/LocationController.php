<?php

namespace App\Http\Controllers;

use App\Models\Parameter; // Zorg ervoor dat je het juiste model importeert
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
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

    return view('products.location.index', compact('locations'));
}


    public function create()
    {
        return view('products.location.create');
    }

    public function store(Request $request)
    {
        $this->validateLocation($request);

        // Unieke waarde controleren
        if (Parameter::where('key', $request->key)->where('value', $request->value)->exists()) {
            return back()->withErrors(['value' => 'De waarde moet uniek zijn voor deze key.']);
        }
        
        // Maak een nieuwe locatie aan
        Parameter::create($request->all());

        return redirect()->route('locations.index')->with('success', 'Locatie toegevoegd.');
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

        return redirect()->route('locations.index')->with('success', 'Locatie bijgewerkt.');
    }

    // Functie voor het verwijderen van een locatie
    public function destroy(Parameter $location)
    {
        // Controleer of er producten zijn met status 70 of lager
        if (Product::where('location', $location->value)->where('status', '<=', 70)->exists()) {
            return redirect()->route('locations.index')->withErrors(['delete' => 'Deze locatie kan niet worden verwijderd omdat er producten met een status van 70 of lager aanhangen.']);
        }

        // Als de status 90 is, legen we het waardeveld in de producten
        if ($location->status == 90) {
            Product::where('location', $location->value)->update(['location' => null]);
        }

        // Verwijder de locatie
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Locatie verwijderd.');
    }

    // Validatie voor locatie-invoer
    protected function validateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }
}

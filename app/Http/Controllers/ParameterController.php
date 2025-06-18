<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index()
    {
        // Haal alle unieke keys op en groepeer ze
        $parameters = Parameter::all()->groupBy('key');
        return view('parameters.index', compact('parameters'));
    }

    public function show($key)
    {
        // Haal alle waarden op voor de specifieke key
        $parameters = Parameter::where('key', $key)->get();
        return view('parameters.show', compact('parameters', 'key'));
    }
    public function create()
    {
        $existingKeys = Parameter::pluck('key'); // Verkrijg alle bestaande keys
        return view('parameters.create', compact('existingKeys'));
    }
    
    public function store(Request $request)
{
    $request->validate([
        'key' => 'required|string',
        'name' => 'required|string',
        'value' => 'required|string',
    ]);

    Parameter::create($request->all());
    return redirect()->route('parameters.index')->with('success', 'Parameter created successfully.');
}


    public function edit(Parameter $parameter)
    {
        return view('parameters.edit', compact('parameter'));
    }


    public function update(Request $request, Parameter $parameter)
    {
        $request->validate([
            'name' => 'required|string',
            'value' => 'required|string',
        ]);
    
        // Sla de oude waarde op
        $parameter->old_value = $parameter->value;
        $parameter->save();
    
        // Update de parameter met de nieuwe waarde
        $parameter->update([
            'name' => $request->name,
            'value' => $request->value,
        ]);
    
        return redirect()->route('parameters.index')->with('success', 'Parameter updated successfully.');
    }
    

    public function destroy(Parameter $parameter)
    {
        $parameter->delete();
        return redirect()->route('parameters.index')->with('success', 'Parameter deleted successfully.');
    }
}

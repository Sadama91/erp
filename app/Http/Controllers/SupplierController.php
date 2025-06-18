<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Bepaal het aantal resultaten per pagina, standaard is 10
        $resultsPerPage = $request->input('results_per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');
        $sort = $request->input('sort');

        $query = Supplier::query()->withCount(['purchaseOrders', 'products']);

        // Zoekfunctie
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('contact_info', 'like', '%' . $search . '%');
            });
        }

        // Filter op status
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        // Sorteren
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'az':
                $query->orderBy('name', 'asc');
                break;
            case 'za':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        // Paginate de resultaten
        if ($resultsPerPage === 'all') {
            $suppliers = $query->paginate($query->count());
        } else {
            $suppliers = $query->paginate($resultsPerPage);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function show($id)
    {
        $supplier = Supplier::with(['purchaseOrders', 'products', 'activities' => function($query) {
            $query->orderByDesc('created_at'); // Zorg dat de logs gesorteerd worden
        }])->findOrFail($id);
    
        $productCount = $supplier->products->count();
        $purchaseCount = $supplier->purchaseOrders->count();
        $totalPurchases = $supplier->purchaseOrders->sum('total');
        $supplierLogs = $supplier->activities;  // Logactiviteiten die al gesorteerd zijn
    
        return view('suppliers.show', compact('supplier', 'productCount', 'purchaseCount', 'totalPurchases', 'supplierLogs'));
    }
    
    
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'contact_info'  => 'nullable|string',
            'purchase_via'  => 'nullable|string',
            'telephone'     => 'nullable|string',
            'website'       => 'nullable|string',
            'terms'         => 'nullable|string',
            'remarks'       => 'nullable|string',
            'status'        => 'boolean',
        ]);

        $supplier = Supplier::create($request->all());
          // Spatie activity log toevoegen
        activity()
        ->performedOn($supplier)
        ->causedBy(auth()->user())
        ->withProperties(['attributes' => $supplier->getAttributes()])
        ->log('Leverancier aangemaakt');


        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'contact_info'  => 'nullable|string',
            'purchase_via'  => 'nullable|string',
            'telephone'     => 'nullable|string',
            'website'       => 'nullable|string',
            'terms'         => 'nullable|string',
            'remarks'       => 'nullable|string',
            'status'        => 'boolean',
        ]);
    
        // Bewaar de originele attributen vóór de update
        $oldAttributes = $supplier->getOriginal();
    
        $supplier->update($request->all());
    
        // Spatie activity log toevoegen
        activity()
            ->performedOn($supplier)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $oldAttributes,
                'new' => $supplier->getAttributes()
            ])
            ->log('Leverancier bijgewerkt');
    
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }
    

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        // Controleer of de leverancier aan producten is gekoppeld
        if ($supplier->products()->count() > 0) {
            return redirect()->route('suppliers.index')->with('error', 'Kan deze leverancier niet verwijderen omdat deze nog aan producten is gekoppeld.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Leverancier succesvol verwijderd.');
    }
}

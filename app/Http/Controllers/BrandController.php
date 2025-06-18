<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        // Bepaal het aantal resultaten per pagina, standaard is 15
        $resultsPerPage = $request->input('results_per_page', 15);
        $search = $request->input('search');
        $sort = $request->input('sort');

        $brands = Brand::query();

        // Zoeken
        if ($search) {
            $brands->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        // Sorteren
        if ($sort === 'newest') {
            $brands->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $brands->orderBy('created_at', 'asc');
        } elseif ($sort === 'az') {
            $brands->orderBy('name', 'asc');
        } elseif ($sort === 'za') {
            $brands->orderBy('name', 'desc');
        }

        // Paginate de resultaten
        if ($resultsPerPage === 'all') {
            $brands = $brands->paginate($brands->count());
        } else {
            $brands = $brands->paginate($resultsPerPage);
        }

        return view('brands.index', compact('brands'));
    }
    public function syncWithWoo(Request $request, Brand $brand)
    {
        try {
            $brand->syncWithWooCommerce();

            return redirect()->back()->with('success', 'Merk succesvol gesynchroniseerd met WooCommerce.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Fout tijdens synchronisatie: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug',
        ]);

        Brand::create($request->all());
        return redirect()->route('brands.index')->with('success', 'Merk succesvol toegevoegd.');
    }


    public function show($id)
    {
        $brand = Brand::findOrFail($id);
        return view('brands.show', compact('brand'));
    }
    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:brands,slug,' . $brand->id,
        ]);

        $brand->update($request->all());
        return redirect()->route('brands.index')->with('success', 'Merk succesvol bijgewerkt.');
    }

    public function destroy(Brand $brand)
    {
        if (Product::where('brand_id', $brand->id)->exists()) {
            return redirect()->route('brands.index')->with('error', 'Merk "' . $brand->name .'" kan niet worden verwijderd omdat er producten aan gekoppeld zijn.');
        }

        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Merk  "' . $brand->name .'" succesvol verwijderd.');
    }
}

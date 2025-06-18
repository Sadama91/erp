<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Bepaal het aantal resultaten per pagina, standaard is 15
        $resultsPerPage = $request->input('results_per_page', 15);
        $search = $request->input('search');
        $sort = $request->input('sort');

        $categories = Category::query();

        // Zoeken
        if ($search) {
            $categories->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
        }

        // Sorteren
        if ($sort === 'newest') {
            $categories->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $categories->orderBy('created_at', 'asc');
        } elseif ($sort === 'az') {
            $categories->orderBy('name', 'asc');
        } elseif ($sort === 'za') {
            $categories->orderBy('name', 'desc');
        }

        // Paginate de resultaten
        if ($resultsPerPage === 'all') {
            $categories = $categories->paginate($categories->count());
        } else {
            $categories = $categories->paginate($resultsPerPage);
        }

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:categories,slug',
    ]);

    return DB::transaction(function () use ($request) {
        $category = Category::create($request->only(['name', 'slug']));

        return redirect()->route('categories.index')
            ->with('success', 'Categorie succesvol toegevoegd en gekoppeld aan WooCommerce.');
    }, 3); // 3 pogingen bij falen
}

public function syncWithWoo(Request $request, Category $category)
{
    try {
        $category->syncWithWooCommerce();

        return redirect()->back()->with('success', 'Categorie succesvol gesynchroniseerd met WooCommerce.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Fout tijdens synchronisatie: ' . $e->getMessage());
    }
}
public function update(Request $request, Category $category)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
    ]);

    return DB::transaction(function () use ($request, $category) {
        $category->update($request->only(['name', 'slug']));


        return redirect()->route('categories.index')
            ->with('success', 'Categorie succesvol bijgewerkt.');
    }, 3); // 3 pogingen bij falen
}
    

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }


    public function destroy(Category $category)
    {
        $categoryId = (string) $category->id;
    
        // Controleer of er producten zijn waarbij de categorie voorkomt in het JSON-veld 'categories'
        $hasProducts = Product::whereJsonContains('categories', $categoryId)->exists();
    
        if ($hasProducts) {
            return redirect()->route('categories.index')->with('error', 'Categorie kan niet worden verwijderd omdat er producten aan gekoppeld zijn.');
        }
    
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Categorie succesvol verwijderd.');
    }
}    
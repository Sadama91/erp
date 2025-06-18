<?php

namespace App\Http\Controllers;

use App\Models\Subgroup;
use Illuminate\Http\Request;

class SubgroupController extends Controller
{
    
    public function index(Request $request)
    {
        // Bepaal het aantal resultaten per pagina, standaard is 15
        $resultsPerPage = $request->input('results_per_page', 15);
        $search = $request->input('search');
        $sort = $request->input('sort');

        $subgroups = Subgroup::query();

        // Zoeken
        if ($search) {
            $subgroups->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
        }

        // Sorteren
        if ($sort === 'newest') {
            $subgroups->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $subgroups->orderBy('created_at', 'asc');
        } elseif ($sort === 'az') {
            $subgroups->orderBy('name', 'asc');
        } elseif ($sort === 'za') {
            $subgroups->orderBy('name', 'desc');
        }

        // Paginate de resultaten
        if ($resultsPerPage === 'all') {
            $subgroups = $subgroups->paginate($subgroups->count());
        } else {
            $subgroups = $subgroups->paginate($resultsPerPage);
        }

        return view('subgroups.index', compact('subgroups'));
    }



    public function create()
    {
        return view('subgroups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subgroups,slug',
        ]);

        Subgroup::create($request->all());
        return redirect()->route('subgroups.index')->with('success', 'Subgroep succesvol toegevoegd.');
    }

    public function edit(Subgroup $subgroup)
    {
        return view('subgroups.edit', compact('subgroup'));
    }

    public function update(Request $request, Subgroup $subgroup)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subgroups,slug,' . $subgroup->id,
        ]);

        $subgroup->update($request->all());
        return redirect()->route('subgroups.index')->with('success', 'Subgroep succesvol bijgewerkt.');
    }

    public function destroy(Subgroup $subgroup)
{
    // Controleer of de subgroep gekoppeld is aan producten
    if ($subgroup->products()->exists()) {
        return redirect()->route('subgroups.index')->withErrors('Deze subgroep kan niet worden verwijderd omdat deze nog gekoppeld is aan producten.');
    }

    // Verwijder de subgroep
    $subgroup->delete();

    return redirect()->route('subgroups.index')->with('success', 'Subgroep succesvol verwijderd.');
}

}

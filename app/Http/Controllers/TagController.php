<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        // Bepaal het aantal resultaten per pagina, standaard is 15
        $resultsPerPage = $request->input('results_per_page', 15);
        $search = $request->input('search');
        $sort = $request->input('sort');

        $tags = Tag::query();

        // Zoeken
        if ($search) {
            $tags->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
        }

        // Sorteren
        if ($sort === 'newest') {
            $tags->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $tags->orderBy('created_at', 'asc');
        } elseif ($sort === 'az') {
            $tags->orderBy('name', 'asc');
        } elseif ($sort === 'za') {
            $tags->orderBy('name', 'desc');
        }

        // Paginate de resultaten
        if ($resultsPerPage === 'all') {
            $tags = $tags->paginate($tags->count());
        } else {
            $tags = $tags->paginate($resultsPerPage);
        }

        return view('tags.index', compact('tags'));
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        return response()->json(Tag::where('name', 'like', "%$query%")->limit(10)->get());
    }
    public function show(Request $request)
    {
        $query = $request->query('q');
        return response()->json(Tag::where('name', 'like', "%$query%")->limit(10)->get());
    }
    public function create()
    {
        return view('tags.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug',
        ]);

        Tag::create($request->all());
        return redirect()->route('tags.index')->with('success', 'Tag succesvol toegevoegd.');
    }

    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,' . $tag->id,
        ]);

        $tag->update($request->all());
        return redirect()->route('tags.index')->with('success', 'Tag succesvol bijgewerkt.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return redirect()->route('tags.index')->with('success', 'Tag succesvol verwijderd.');
    }
}

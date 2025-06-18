@extends('layouts.app')
@section('page_title', 'Overzicht subgroepen')

@section('content')
<div class="container mx-auto mt-6">
    <div class="flex mb-4">
    <a href="{{ route('subgroups.create') }}" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
        Nieuwe Subgroep Toevoegen
    </a>
</div>
    <!-- Filters en zoeken -->
    <form method="GET" action="{{ route('subgroups.index') }}" class="mb-4 flex justify-between items-center">
        <!-- Zoeken en sorteren -->
        <div class="flex items-center space-x-2">
            <!-- Zoeken -->
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Zoek subgroepen..." 
                class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" />

            <!-- Sorteren -->
            <select name="sort" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" onchange="this.form.submit()">
                <option value="" disabled selected>Sorteer op</option>
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Nieuw - Oud</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oud - Nieuw</option>
                <option value="az" {{ request('sort') == 'az' ? 'selected' : '' }}>A-Z</option>
                <option value="za" {{ request('sort') == 'za' ? 'selected' : '' }}>Z-A</option>
            </select>

            <!-- Filter knop -->
            <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
                Filter
            </button>
        </div>

        <!-- Resultaten per pagina (helemaal rechts) -->
        <div class="flex items-center space-x-2">
            <label for="results_per_page" class="text-sm text-gray-600">Resultaten per pagina:</label>
            <select name="results_per_page" id="results_per_page" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" onchange="this.form.submit()">
                <option value="15" {{ request('results_per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                <option value="30" {{ request('results_per_page', 15) == 30 ? 'selected' : '' }}>30</option>
                <option value="all" {{ request('results_per_page', 15) == 'all' ? 'selected' : '' }}>Alles</option>
            </select>
        </div>
    </form>

    <!-- Tabel met Subgroepen -->
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($subgroups as $subgroup)
            <tr>
                <td class="px-4 py-2 whitespace-nowrap">{{ $subgroup->name }}</td>
                <td class="px-4 py-2 whitespace-nowrap">{{ $subgroup->slug }}</td>
                <td class="px-4 py-2 whitespace-nowrap">
                    <a href="{{ route('subgroups.edit', $subgroup->id) }}" class="text-blue-600 hover:text-blue-700 inline-flex items-center transition">
                        <svg class="w-5 h-5 mr-1" data-feather="edit"></svg>
                    </a>
                    <form action="{{ route('subgroups.destroy', $subgroup->id) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je deze subgroep wilt verwijderen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700 transition">
                            <svg class="w-5 h-5" data-feather="trash-2"></svg>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Paginatie -->
    <div class="mt-4 flex justify-between items-center">
        <div>
            {{ $subgroups->withQueryString()->links() }}
        </div>
        <div class="text-sm text-gray-600">
            {{ $subgroups->total() }} resultaten
        </div>
    </div>
</div>
@endsection

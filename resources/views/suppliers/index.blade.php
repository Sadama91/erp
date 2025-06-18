@extends('layouts.app')

@section('page_title', 'Overzicht Leveranciers')

@section('content')
<div class="max-w-5xl mx-auto p-4">
    <h1 class="mb-6 text-2xl font-bold">Leveranciers</h1>
    
    <!-- Zoek- en filtersectie -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-4 sm:space-y-0">
        <form action="{{ route('suppliers.index') }}" method="GET" class="flex-grow mr-2">
            <input type="text" name="search" value="{{ request()->get('search') }}" placeholder="Zoek leveranciers..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
        </form>

        <div class="flex items-center space-x-2">
            <form action="{{ route('suppliers.index') }}" method="GET" class="flex items-center">
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                    <option value="">Alle statussen</option>
                    <option value="1" {{ request()->get('status') == '1' ? 'selected' : '' }}>Actief</option>
                    <option value="0" {{ request()->get('status') == '0' ? 'selected' : '' }}>Inactief</option>
                </select>
                <button type="submit" class="ml-3 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Filter
                </button>
            </form>
            <a href="{{ route('suppliers.create') }}" class="ml-3 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Nieuwe Leverancier Toevoegen
            </a>
        </div>
    </div>

    <!-- Tabel met overzicht -->
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300">
            <thead>
                <tr class="bg-gray-100 text-gray-600">
                    <th class="px-4 py-2 border-b text-left">Naam</th>
                    <th class="px-4 py-2 border-b text-left">Contact Info</th>
                    <th class="px-4 py-2 border-b text-left">Website</th>
                    <th class="px-4 py-2 border-b text-center">Inkooporders</th>
                    <th class="px-4 py-2 border-b text-center">Artikelen</th>
                    <th class="px-4 py-2 border-b text-center">Status</th>
                    <th class="px-4 py-2 border-b text-center">Acties</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border-b">
                        <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-blue-600 hover:underline">
                            {{ $supplier->name }}
                        </a>
                    </td>
                    <td class="px-4 py-2 border-b">{{ $supplier->contact_info }}</td>
                    <td class="px-4 py-2 border-b">
                        @if($supplier->website)
                            <a href="{{ $supplier->website }}" target="_blank" class="text-blue-600 hover:underline">{{ $supplier->website }}</a>
                        @else
                            Geen website
                        @endif
                    </td>
                    <td class="px-4 py-2 border-b text-center">
                        {{ $supplier->purchase_orders_count }}
                        @if($supplier->purchase_orders_count)
                            <a href="{{ route('purchases.index', ['supplier_id' => $supplier->id]) }}" class="inline-flex items-center ml-2 text-blue-600 hover:underline">
                                <svg class="w-4 h-4" data-feather="eye"></svg>
                            </a>
                        @endif
                    </td>
                    <td class="px-4 py-2 border-b text-center">
                        {{ $supplier->products_count }}
                        @if($supplier->products_count)
                            <a href="{{ route('products.index', ['supplier_id' => $supplier->id]) }}" class="inline-flex items-center ml-2 text-blue-600 hover:underline">
                                <svg class="w-4 h-4" data-feather="eye"></svg>
                            </a>
                        @endif
                    </td>
                    <td class="px-4 py-2 border-b text-center">{{ $supplier->status ? 'Actief' : 'Inactief' }}</td>
                    <td class="px-4 py-2 border-b flex justify-center space-x-2">
                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-blue-600 hover:text-blue-700 inline-flex items-center">
                            <svg class="w-5 h-5" data-feather="edit"></svg>
                        </a>
                        <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-green-600 hover:text-green-700 inline-flex items-center">
                            <svg class="w-5 h-5" data-feather="eye"></svg>
                        </a>
                        <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je deze leverancier wilt verwijderen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700 inline-flex items-center">
                                <svg class="w-5 h-5" data-feather="trash-2"></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Paginering -->
    <div class="mt-6">
        {{ $suppliers->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
    feather.replace();
</script>
@endsection

@extends('layouts.app')

@section('page_title', 'Overzicht inkooporders')

@section('content')
<div class="container mx-auto mt-6">
    <!-- nieuwe aanmaken-->
    <div class="flex mb-4">
        <a href="{{ route('purchases.create') }}" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
            Creëer nieuwe inkoop order
        </a>
    </div>

    <!-- Filters en zoeken -->
    <form method="GET" action="{{ route('purchases.index') }}" class="mb-4 flex justify-between items-center">
        <!-- Leverancier filter -->
        <select name="supplier_id" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
            <option value="">Selecteer leverancier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
            @endforeach
        </select>

       <!-- Datum filter -->
       <input type="date" name="start_date" placeholder="Startdatum">
       <input type="date" name="end_date" placeholder="Einddatum">
      
        <!-- Zoeken en sorteren -->
        <div class="flex items-center space-x-2">
        
            <select name="sort" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" onchange="this.form.submit()">
                <option value="" disabled selected>Sorteer op</option>
                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Nieuw - Oud</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oud - Nieuw</option>
                <option value="az" {{ request('sort') == 'az' ? 'selected' : '' }}>A-Z</option>
                <option value="za" {{ request('sort') == 'za' ? 'selected' : '' }}>Z-A</option>
            </select>

            <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
                Filter
            </button>
        </div>

        <div class="flex items-center space-x-2">
            <label for="results_per_page" class="text-sm text-gray-600">Resultaten per pagina:</label>
            <select name="results_per_page" id="results_per_page" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200" onchange="this.form.submit()">
                <option value="15" {{ request('results_per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                <option value="30" {{ request('results_per_page', 15) == 30 ? 'selected' : '' }}>30</option>
                <option value="all" {c:\xampp\htdocs\beheerApp\resources\views\purchase_orders{ request('results_per_page', 15) == 'all' ? 'selected' : '' }}>Alles</option>
            </select>
        </div>
    </form>

    <!-- Tabel met Inkooporders -->
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inkoop order</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ingekocht via</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waarde</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($purchases as $purchase)
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $purchase->id }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($purchase->date)->format('d-M-Y') }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $purchase->supplier->name }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $purchase->total_quantity }}</td>
                    <td class="px-4 py-2">€{{ number_format($purchase->purchase_order_items_sum_total, 2) }}</td> <!-- Totaalbedrag -->    
                    <td class="px-4 py-2">{{ $statuses[$purchase->status] ?? 'Onbekend' }}</td> <!-- Statusnaam -->
                    <td class="px-4 py-2 border-b flex space-x-2">
                            <a href="{{ route('purchases.show', $purchase->id) }}" class="text-green-600 hover:underline" title="Bekijken">
                            <svg class="w-5 h-5 mr-1" data-feather="eye"></svg>
                        </a>
                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center">
                            <svg class="w-5 h-5 mr-1" data-feather="edit"></svg>
                        </a>
                        @if($purchase->status <3)
                        <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="inline-flex">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700 inline-flex items-center">
                                <svg class="w-5 h-5 mr-1" data-feather="trash-2"></svg>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Paginatie -->
    <div class="mt-4 flex justify-between items-center">
        <div>
            {{ $purchases->links() }}
        </div>
        <div class="text-sm text-gray-600">
            {{ $purchases->total() }} resultaten
        </div>
    </div>
</div>
@endsection

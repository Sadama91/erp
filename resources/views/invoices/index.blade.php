@extends('layouts.app')

@section('page_title', 'Factuuroverzicht')

@section('content')
<div class="container mx-auto p-6 bg-white shadow-lg rounded-lg">

    <!-- Titel en knop voor nieuwe factuur -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Factuuroverzicht</h2>
        <a href="{{ route('invoices.create') }}"
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded inline-flex items-center">
            <svg class="w-5 h-5 mr-1" data-feather="file-plus"></svg> Nieuwe Factuur
        </a>
    </div>

    <!-- Filterformulier -->
    <form method="GET" class="grid md:grid-cols-5 gap-4 mb-6">
        <input type="text" name="search" placeholder="Omschrijving..." value="{{ request('search') }}" class="border p-2 rounded">
        <input type="number" name="amount" placeholder="Bedrag (€)..." value="{{ request('amount') }}" class="border p-2 rounded">
        <select name="type" class="border p-2 rounded">
            <option value="">Alle types</option>
            <option value="verkoop" {{ request('type') == 'verkoop' ? 'selected' : '' }}>Verkoop</option>
            <option value="inkoop" {{ request('type') == 'inkoop' ? 'selected' : '' }}>Inkoop</option>
            <option value="kosten" {{ request('type') == 'kosten' ? 'selected' : '' }}>Kosten</option>
        </select>
        <select name="status" class="border p-2 rounded">
            <option value="">Alle status</option>
            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
            <option value="betaald" {{ request('status') == 'betaald' ? 'selected' : '' }}>Betaald</option>
            <option value="vervallen" {{ request('status') == 'vervallen' ? 'selected' : '' }}>Vervallen</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded inline-flex items-center">
            <svg class="w-5 h-5 mr-2" data-feather="search"></svg> Filter
        </button>
    </form>

    <!-- Tabel met factuuroverzicht -->
    <div class="overflow-x-auto">
        <table class="w-full table-auto border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border"><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th class="border p-2">ID</th>
                    <th class="border p-2">Type</th>
                    <th class="border p-2">Bedrag incl. BTW</th>
                    <th class="border p-2">Factuurdatum</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                        <td class="p-2 border text-center"><input type="checkbox" name="invoices[]" value="{{ $invoice->id }}"></td>
                        <td class="border p-2">{{ $invoice->id }}</td>
                        <td class="border p-2 capitalize">{{ $invoice->type }}</td>
                        <td class="border p-2">
                            &euro;{{ number_format($invoice->invoiceLines->sum('amount_incl_vat_total'), 2) }}
                        </td>
                        <td class="border p-2">
                            {{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}
                        </td>
                        <td class="border p-2">
                            <span class="px-2 py-1 text-sm rounded
                                {{ $invoice->status == 'betaald' ? 'bg-green-100 text-green-800' 
                                    : ($invoice->status == 'vervallen' ? 'bg-red-400 text-red-800' 
                                    : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>

                        <td class="border p-2 space-x-2 text-center">
                            <a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-800 inline-block">
                                <svg class="w-5 h-5" data-feather="eye"></svg>
                            </a>
                            <a href="{{ route('invoices.edit', $invoice->id) }}" class="text-yellow-600 hover:text-yellow-800 inline-block">
                                <svg class="w-5 h-5" data-feather="edit"></svg>
                            </a>
                            <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" data-feather="trash-2"></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500">Geen facturen gevonden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginatie -->
    <div class="mt-6">{{ $invoices->withQueryString()->links() }}</div>
</div>

<script>
    feather.replace();
    function toggleAll(masterCheckbox) {
        const checkboxes = document.querySelectorAll('input[name="invoices[]"]');
        checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    }
</script>
@endsection

@extends('layouts.app')

@section('page_title', 'Controle inkooporder: #'.$purchaseOrder->id)

@section('content')
<!-- Totale informatie van de inkooporder -->
<div class="bg-white shadow-md rounded-lg p-4 mb-4">
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <strong>Leverancier:</strong> {{ $purchaseOrder->supplier->name }}<br>
            <strong>Datum:</strong> {{ \Carbon\Carbon::parse($purchaseOrder->date)->format('d F Y') }}<br>
            <strong>Aanmaakdatum:</strong> {{ $purchaseOrder->created_at->format('d F Y') }}<br>
            <strong>Status:</strong> {{ $statuses[$purchaseOrder->status] ?? 'Onbekend' }}<br>
        </div>
        <div>
            <strong>Order Totaal:</strong> {{ number_format($purchaseOrder->purchaseOrderItems->sum('total'), 2, ',', '.') }} €<br>
            <strong>Aantal Items:</strong> {{ $purchaseOrder->purchaseOrderItems->count() }}<br>
            <strong>Verkoopwaarde:</strong> {{ number_format($purchaseOrder->purchaseOrderItems->sum('total') * 1.21, 2, ',', '.') }} €<br>
        </div>
    </div>

<div class="mb-4 space-x-2">
    <!-- Terug -->
    <a href="{{ route('purchases.index', request()->query()) }}"
       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded inline-flex items-center">
        <svg class="w-5 h-5 mr-1" data-feather="arrow-left"></svg>
        Terug
    </a>

    <!-- Bewerken -->
    <a href="{{ route('purchases.edit', $purchaseOrder->id) }}"
       class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
        <svg class="w-5 h-5 mr-1" data-feather="edit-3"></svg>
        Bewerken
    </a>

    <!-- Verwijderen -->
    <form action="{{ route('purchases.destroy', $purchaseOrder->id) }}" method="POST" class="inline-block">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
            <svg class="w-5 h-5 mr-1" data-feather="trash"></svg>
            Verwijderen
        </button>
    </form>
</div>

</div>

<!-- Formulier met inkooporder items -->
<form action="{{ route('purchase_orders.process', $purchaseOrder->id) }}" method="POST">
    @csrf
    <h2 class="text-xl font-bold mb-4">Controle inkooporder Items</h2>
    <table class="min-w-full bg-white border border-gray-300">
        <thead>
            <tr>
                <th class="px-4 py-2 border">SKU</th>
                <th class="px-4 py-2 border">Omschrijving</th>
                <th class="px-4 py-2 border">Inkoopprijs incl</th>
                <th class="px-4 py-2 border">Aantal besteld</th>
                <th class="px-4 py-2 border">Aantal geteld</th>
                <th class="px-4 py-2 border">Verschil</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->purchaseOrderItems as $item)
            <tr>
                <td class="px-4 py-2 border">{{ $item->product->sku }}</td>
                <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                <td class="px-4 py-2 border">{{ number_format($item->price_incl_unit, 2, ',', '.') }} €</td>
                <td class="px-4 py-2 border">{{ $item->quantity }}</td>
                <td class="px-4 py-2 border">
                    <input type="number" name="counted[{{ $item->id }}]" value="{{ $item->quantity }}" 
                        min="0" class="border rounded w-full" onchange="calculateDifference(this, {{ $item->quantity }})" />
                </td>
                <td class="px-4 py-2 border" id="difference-{{ $item->id }}">0</td>
            </tr>
            @endforeach
        </tbody>
    </table><div class="flex items-center mt-4 mb-2">
    <input type="checkbox" id="create_invoice" name="create_invoice" value="1" class="form-checkbox h-5 w-5 text-green-600">
    <label for="create_invoice" class="ml-2 text-gray-700 font-medium">Maak factuur aan na verwerking</label>
</div>

    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 mt-4 rounded">
        Opslaan Controle
    </button>
</form>

<script>
function calculateDifference(input, expectedQuantity) {
    const counted = parseInt(input.value) || 0;
    const difference = counted - expectedQuantity;
    const differenceCell = input.closest('tr').querySelector(`[id^="difference-"]`);
    differenceCell.innerText = difference;
}
</script>
@endsection

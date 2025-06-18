@extends('layouts.app')

@section('page_title', 'Bestelling Details #'. $order->id )

@section('content')
<div class="container mx-auto">

    <div class="bg-white p-6 rounded-lg shadow-md">
        <!-- Knoppen Sectie -->
        <div class="flex flex-wrap gap-4 justify-start">
            <!-- Terug Knop -->
            <a href="{{ url()->previous() }}" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded text-center">
                Terug
            </a>
 
    
            <!-- Bewerken Knop -->
            @if(!in_array($order->status, [3]))
                <a href="{{ route('orders.edit', $order->id) }}" 
                class="font-semibold py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 text-white">
                    Bewerken
                </a>
            @else 

            <a href="#""
                class="font-semibold py-2 px-4 rounded text-center bg-gray-400 text-gray-600 cursor-not-allowed" 
                aria-disabled=true tabindex=-1 onclick="return false">
                 Bewerken
             </a>
            @endif
    
            <!-- Pas Status Aan Knop -->
            <button class="font-semibold py-2 px-4 rounded text-center 
                          {{ $order->status == 3 ? 'bg-gray-400 text-gray-600 cursor-not-allowed' : 'bg-yellow-500 hover:bg-yellow-600 text-white' }}" 
                    {{ $order->status == 3 ? 'disabled aria-disabled=true' : '' }} 
                    onclick="{{ $order->status == 3 ? '' : 'openStatusModal()' }}">
                Pas Status Aan
            </button>
    
            <!-- Annuleren/Verwijderen Knop -->
            <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="font-semibold py-2 px-4 rounded text-center 
                               {{ in_array($order->status, [2, 3]) ? 'bg-gray-400 text-gray-600 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600 text-white' }}" 
                        {{ in_array($order->status, [2, 3]) ? 'disabled aria-disabled=true' : '' }} 
                        onclick="{{ in_array($order->status, [2, 3]) ? 'return false;' : 'return confirm(\'Weet je zeker dat je deze bestelling wilt annuleren?\')' }}">
                    Annuleren/Verwijderen
                </button>
            </form>
        </div>
    </div>
    

        </div>
        
        <div class="flex space-x-4">
            <!-- Kolom 1 -->
            <div class="bg-gray-50 rounded-lg p-4 w-1/3">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="font-medium text-gray-700">Datum</td>
                            <td class="text-gray-900">{{ $order->date }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Orderstatus</td>
                            <td class="text-gray-900">{{ $orderStatuses->firstWhere('value', $order->status)->name ?? $order->status }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Besteld Via</td>
                            <td class="text-gray-900">{{ $salesChannels->firstWhere('value', $order->order_source)->name ?? $order->order_source }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Verzonden Via</td>
                            <td class="text-gray-900">{{ $shippingMethods->firstWhere('value', $order->shipping_method)->name ?? $order->shipping_method }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Verzendkosten</td>
                            <td class="text-gray-900">€{{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                        <tr>
                            <td  colspan="2">
                                <span class="block text-sm font-medium text-green-800 bg-green-100 rounded-md px-3 py-2">
                                    {{ $orderStatuses[$order->status]->name ?? 'Onbekend' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        
            <!-- Kolom 2 -->
            <div class="bg-gray-50 rounded-lg p-4 w-1/3">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="font-medium text-gray-700">Klant Naam</td>
                            <td class="text-gray-900">{{ $order->customer_name }}</td>
                        </tr>
                        @if($order->username)
                            <tr>
                                <td class="font-medium text-gray-700">(Vinted) Gebruikersnaam</td>
                                <td class="text-gray-900">{{ $order->username }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="font-medium text-gray-700">Adres Klant</td>
                            <td class="text-gray-900">{{ $order->customer_address }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Postcode</td>
                            <td class="text-gray-900">{{ $order->postal_code }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Plaats</td>
                            <td class="text-gray-900">{{ $order->city }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">Land</td>
                            <td class="text-gray-900">{{ $order->country }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        
            <!-- Kolom 3 -->
            <div class="bg-gray-50 rounded-lg p-4 w-1/3">
                <table class="w-full">
                    <tbody>
                        <tr>
                            <td class="font-medium text-gray-700">Opmerkingen</td>
                        </tr>
                        <tr>
                            <td class="text-gray-900">{{ $order->NOTES }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
                
    

    <!-- Totale informatie -->
    
    <div class="bg-gray-20 p-6 rounded-lg shadow-md mb-6 mt-2 py-2 text-left">
        <h3 class="font-semibold text-lg mb-4">Totale Informatie</h3>
        <table class="table-auto w-full">
            <thead>
                <th>Aantal Items:</th>
                <th>Aantal Items:</th>
                <th>Totale verkoop waarde:</th>
                <th>Totale inkoop waarde:</th>
                <th>marge</th>
            </thead>
            <tbody>
                <td> {{ count($order->items) }}</td>
                <td>{{ $order->orderItems->sum('quantity') }}</td> <!-- Telt de totale hoeveelheid van alle bestelitems -->
                <td> €{{ number_format($totalSalesIncl, 2) }}</td>
                <td> €{{ number_format($totalValue, 2) }}</td>
                <td> €{{ number_format($totalMargin, 2) }} ({{ number_format($totalMarginPercent, 2) }}%)</td>
            </tbody>
        </table>
    </div>

    <!-- Tabel voor geselecteerde producten -->
    <table class="bg-white min-w-full mb-4 text-left rounded-lg text-left">
        <thead class="bg-gray-200 shadow-md mb-6">
            <tr>
                <th class="px-4 py-2 w-12">#</th>
                <th class="px-4 py-2 w-12">SKU</th>
                <th class="px-4 py-2">Artikel</th>
                <th class="py-2">Inkoop</th>
                <th class="py-2">Totaal prijs</th>
                <th class="py-2">Huidige voorraad</th>
                <th class="py-2">Locatie</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->product->sku }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>€{{ number_format($item->purchase_price, 2) }}</td>
                    <td>€{{ number_format($item->calculated_sales_price, 2) }}</td>
                    <td>{{ $item->product->stock->current_quantity ?? 0 }}
                        @if(isset($item->product->stock->on_the_way_quantity) && $item->product->stock->on_the_way_quantity > 0)   
                            <em>Onderweg:  {{ $item->product->stock->on_the_way_quantity ?? 0}}</em>
                        @endif
                    </td>
                    <td>{{ $item->product->locationName ?? 'Geen locatie' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

<!-- Modal voor status bijwerken -->
<div id="statusModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-100 max-w-lg">
        <h2 class="text-lg font-bold mb-4">Pas Status Aan</h2>
        <p class="mb-4">Huidige status: <strong>{{ $orderStatuses->firstWhere('value', $order->status)->name ?? 'Onbekend' }}</strong></p>
        <form action="{{ route('orders.updateStatus', $order->id) }}" method="POST">
            @csrf
            <label for="status" class="block mb-2 font-semibold">Nieuwe Status:</label>
            <!-- Dropdown over de volledige breedte -->
            <select id="status" name="status" class="border border-gray-300 rounded-lg px-3 py-2 w-75">
                <option value="">Selecteer status</option>
                @foreach($orderStatuses as $status)
                    @if($status->value >= $order->status)
                        <option value="{{ $status->value }}" {{ $order->status == $status->value ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                    @endif
                @endforeach
            </select>
            <div class="flex justify-end mt-4">
                <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded mr-2" onclick="closeStatusModal()">Annuleren</button>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">Opslaan</button>
            </div>
        </form>
    </div>
</div>


<script>
function openStatusModal() {
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}
</script>

@endsection

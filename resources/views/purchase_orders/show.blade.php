@extends('layouts.app')

@section('page_title', 'Overzicht inkooporder: #'.$purchaseOrder->id)

@section('content')
<div class="max-w-5xl mx-auto p-4" x-data="{ tab: 'details' }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('purchases.index') }}" class="flex items-center text-sm text-gray-600 hover:underline">
            <svg class="w-5 h-5 mr-1" data-feather="arrow-left"></svg> Terug
        </a>
        <h1 class="text-2xl font-bold">Inkooporder #{{ $purchaseOrder->id }}</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b mb-4">
        <nav class="flex space-x-6 text-sm font-medium">
            <button @click="tab = 'details'" :class="tab === 'details' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="file-text"></svg> Details
            </button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="activity"></svg> Logs
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div>
        {{-- Tab: Details --}}
        <div x-show="tab === 'details'" x-cloak class="space-y-6">
            <!-- Algemene gegevens in een grid -->
            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p><strong>Leverancier:</strong> {{ $purchaseOrder->supplier->name }}</p>
                        <p><strong>Datum:</strong> {{ \Carbon\Carbon::parse($purchaseOrder->date)->format('d F Y') }}</p>
                        <p><strong>Aanmaakdatum:</strong> {{ $purchaseOrder->created_at->format('d F Y') }}</p>
                        <p><strong>Status:</strong> {{ $statuses[$purchaseOrder->status] ?? 'Onbekend' }}</p>
                    </div>
                    <div>
                        @if(isset($purchaseOrder->notes))
                            <p><strong>Opmerkingen:</strong></p>
                            <p class="bg-gray-100 py-2 px-4 rounded">{{ nl2br(e($purchaseOrder->notes)) }}</p>
                        @endif
                    </div>
                </div>

                <!-- Knoppen -->
                <div class="mt-4 grid grid-cols-5 gap-4">
                    <a href="{{ route('purchases.edit', $purchaseOrder->id) }}" class="bg-blue-500 w-full text-white px-4 py-2 rounded">
                    <svg class="w-5 h-5 mr-1" data-feather="edit"></svg>Bewerken</a>
                    @if ($purchaseOrder->status <= 2)
                        <a href="{{ route('purchase_orders.control', $purchaseOrder->id) }}"
                        class="bg-yellow-500 w-full text-white px-4 py-2 rounded">
                            <svg class="w-5 h-5 mr-1" data-feather="check-square"></svg>
                            Controleer
                        </a>
                    @endif
                    @if($purchaseOrder->status >1)
                        <a href="{{ route('purchases.destroy', $purchaseOrder->id) }}" class="bg-red-500 text-white px-4 py-2 rounded"> <svg class="w-5 h-5 mr-1" data-feather="trash-2"></svg>Verwijderen</a>
                    @endif
                    @if (!$purchaseOrder->invoice)
                        <a href="{{ route('invoices.create', ['from_purchase_order' => $purchaseOrder->id]) }}" class="bg-green-600 text-white px-4 py-2 rounded"><svg class="w-5 h-5 mr-1" data-feather="file-text"></svg>Maak Factuur Aan</a>
                    @else
                        <a href="{{ route('invoices.show', $purchaseOrder->invoice->id) }}" class="bg-gray-600  w-full text-white px-4 py-2 rounded"><svg class="w-5 h-5 mr-1" data-feather="eye"></svg>Bekijk factuur</a>
                    @endif
                
                </div>
            </div>

            <!-- Tabel met inkooporder items -->
            <h2 class="text-xl font-bold mb-4">Inkooporder Items</h2>
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border">#</th>
                        <th class="px-4 py-2 border">SKU</th>
                        <th class="px-4 py-2 border">Omschrijving</th>
                        <th class="px-4 py-2 border">Inkoopprijs stuk</th>
                        <th class="px-4 py-2 border">Verkoopprijs</th>
                        <th class="px-4 py-2 border">Totaal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->purchaseOrderItems as $item)
                    <tr>
                        <td class="px-4 py-2 border">{{ $item->quantity }}</td>
                        <td class="px-4 py-2 border">{{ $item->product->sku }}</td>
                        <td class="px-4 py-2 border">{{ $item->product->name }}</td>
                        <td class="px-4 py-2 border">{{ number_format($item->price_incl_unit, 2, ',', '.') }} €</td>
                        <td class="px-4 py-2 border">€{{ number_format($item->price->price, 2) }}</td>
                        <td class="px-4 py-2 border">
                            €{{ number_format($item->price_incl_unit * $item->quantity, 2) }}
                            <em>(€{{ number_format(($item->price->price *$item->quantity),2) }})</em>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tab: Logs --}}
        <div x-show="tab === 'logs'" x-cloak class="space-y-4">
            <h2 class="font-semibold text-lg">Wijzigingsgeschiedenis</h2>
            <div class="space-y-2 text-sm">
                @if($purchaseOrder->activities->isEmpty())
                    <p class="text-gray-500">Geen logs beschikbaar.</p>
                @else
                    @foreach ($purchaseOrder->activities as $log)
                        <div class="border-l-4 border-blue-500 pl-3 py-1 bg-blue-50">
                            <div>
                                <span class="font-semibold">{{ $log->created_at->format('d-m-Y H:i') }}</span> – 
                                {{ $log->description }}
                            </div>
                            <div class="text-gray-600">Door: {{ $log->causer->name ?? 'Onbekend' }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    feather.replace();
</script>
@endsection

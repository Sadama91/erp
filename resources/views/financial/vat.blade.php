@extends('layouts.app')

@section('page_title', 'BTW Overzicht')
@section('content')
<!-- Alpine container voor de hele pagina -->
<div x-data="transactionModal()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">BTW Overzicht</h1>
    </div>

    <!-- Filterformulier -->
    <form method="GET" action="{{ route('financial.vat.index') }}" class="mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Startdatum</label>
                <input type="text" name="start_date" id="start_date" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       value="{{ old('start_date', $startDate) }}" placeholder="dd-mm-jjjj">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">Einddatum</label>
                <input type="text" name="end_date" id="end_date" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       value="{{ old('end_date', $endDate) }}" placeholder="dd-mm-jjjj">
            </div>
            <div>
                <label for="source" class="block text-sm font-medium text-gray-700">Bron</label>
                <select name="source" id="source" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Alle bronnen --</option>
                    <option value="order" {{ $source == 'order' ? 'selected' : '' }}>Order</option>
                    <option value="kosten" {{ $source == 'kosten' ? 'selected' : '' }}>Kosten</option>
                    <option value="inkoop" {{ $source == 'inkoop' ? 'selected' : '' }}>Inkoop</option>
                    <option value="verkoop" {{ $source == 'verkoop' ? 'selected' : '' }}>Verkoop</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                    Filter
                </button>
            </div>
        </div>
    </form>

    @if(session('log_message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('log_message') }}
        </div>
    @endif

    <!-- Tabel met BTW-transacties -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto">
            <div class="py-2 align-middle inline-block min-w-full">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactie Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BTW Bedrag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($vats as $vat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $vat->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $vat->order_id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $vat->invoice_id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ ucfirst($vat->vat_transaction_type) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($vat->vat_amount, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $vat->created_at->format('d-m-Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button @click="openModal({{ $vat->finance_transaction_id }})" 
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                            Bekijk transactie
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        Geen BTW-transacties gevonden voor de geselecteerde criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $vats->links() }}
    </div>

    <!-- Modal component -->
    <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
                <div class="flex justify-between items-center px-4 py-3 border-b">
                    <h3 class="text-lg font-medium text-gray-900">
                        Financiële Transactie #<span x-text="transaction.id"></span>
                    </h3>
                    <button @click="close()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="px-4 py-4">
                    <div class="border-b border-gray-200 mb-4">
                        <nav class="-mb-px flex space-x-8">
                            <a href="#" 
                               @click.prevent="activeTab = 'details'" 
                               :class="activeTab === 'details' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                               class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                               Details
                            </a>
                            <a href="#" 
                               @click.prevent="activeTab = 'logs'" 
                               :class="activeTab === 'logs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                               class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                               Logs
                            </a>
                        </nav>
                    </div>
                    <div x-show="activeTab === 'details'" class="space-y-4">
                        <div>
                            <span class="font-semibold">Account ID:</span>
                            <span x-text="transaction.account_id"></span>
                        </div>
                        <div>
                            <span class="font-semibold">Bedrag:</span>
                            € <span x-text="transaction.amount"></span>
                        </div>
                        <div>
                            <span class="font-semibold">Datum:</span>
                            <span x-text="transaction.transaction_date"></span>
                        </div>
                        <div>
                            <span class="font-semibold">Omschrijving:</span>
                            <span x-text="transaction.description"></span>
                        </div>
                    </div>
                    <div x-show="activeTab === 'logs'" class="space-y-4">
                        <template x-if="logs.length">
                            <ul class="divide-y divide-gray-200">
                                <template x-for="log in logs" :key="log.id">
                                    <li class="py-2">
                                        <div class="text-sm text-gray-700" x-text="log.description"></div>
                                        <div class="text-xs text-gray-500" x-text="log.created_at"></div>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <template x-if="!logs.length">
                            <p class="text-gray-500 text-sm">Geen logs beschikbaar.</p>
                        </template>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 text-right">
                    <button @click="close()" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                        Sluiten
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Zorg dat Alpine.js geladen wordt (zorg dat dit vóór de rest komt in je layout als dat mogelijk is) -->
    <script>
        document.addEventListener('alpine:init', () => {
            console.log('Alpine is initialized');
        });
        // Definieer de functie in de globale scope
      window.transactionModal = function() {
    return {
        isOpen: false,
        activeTab: 'details',
        transaction: {}, // Dit zal de finance_transaction bevatten
        vat: {},         // Dit bevat het VAT-record
        logs: [],
        openModal(transactionId) {
            console.log('openModal clicked, transactionId:', transactionId);
            this.isOpen = true;
            this.activeTab = 'details';
            // Bouw de URL dynamisch met de route-helper
            let url = "{{ route('financial.vat.json', ':id') }}";
            url = url.replace(':id', transactionId);
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched data:', data);
                    // Wijzig de toewijzing zodat je duidelijk onderscheid hebt:
                    this.transaction = data.finance_transaction; // Gekoppelde financiële transactie
                    this.vat = data.vat;                           // Het VAT-record
                    this.logs = data.logs;   
                    console.log(this.logs);
                                          // Eventuele logs
                })
                .catch(error => {
                    console.error('Error fetching transaction data:', error);
                });
        },
        close() {
            this.isOpen = false;
            this.transaction = {};
            this.vat = {};
            this.logs = [];
        }
    }
}

    </script>
@endsection

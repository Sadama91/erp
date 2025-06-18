@extends('layouts.app')

@section('page_title', 'Overzicht bestellingen')

@section('content')
<div class="container mx-auto mt-6">
    <!-- Nieuwe bestelling aanmaken -->
    <div class="flex mb-4">
        <a href="{{ route('orders.create') }}" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
            Creëer nieuwe bestelling
        </a>
    </div>

    <!-- Filters en zoeken -->
    <form method="GET" action="{{ route('orders.index') }}" class="mb-4 flex flex-col space-y-2 bg-white p-2 rounded shadow-sm">
        <div class="flex flex-col md:flex-row md:space-x-2">
            <!-- Datum filter -->
            <div class="flex-1">
                <label for="start_date" class="text-sm text-gray-700">Startdatum</label>
                <input type="date" name="start_date" id="start_date" class="rounded px-2 py-1 w-full border-none focus:outline-none">
            </div>

            <div class="flex-1">
                <label for="end_date" class="text-sm text-gray-700">Einddatum</label>
                <input type="date" name="end_date" id="end_date" class="rounded px-2 py-1 w-full border-none focus:outline-none">
            </div>

            <!-- Bestel methode filter -->
            <div class="flex-1">
                <label for="order_source" class="text-sm text-gray-700">Bron</label>
                <select name="order_source" id="order_source" class="border border-gray-300 rounded px-2 py-1 w-full">
                    <option value="">Selecteer bron</option>
                    @foreach($salesMethods as $key => $method)
                        <option value="{{ $key }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status filter -->
            <div class="flex-1">
                <label for="order_status" class="text-sm text-gray-700">Status</label>
                <select name="order_status" id="order_status" class="border border-gray-300 rounded px-2 py-1 w-full">
                    <option value="">Selecteer status</option>
                    @foreach($orderStatuses as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between mt-2">
            <div class="w-1/2">
                <input type="text" name="customer_name" id="customer_name" placeholder="Zoek klant" class="border border-gray-300 rounded px-2 py-1 w-full" value="{{ request('customer_name') }}">
            </div>
            <div class="flex items-center space-x-2">
                <label for="results_per_page" class="text-sm text-gray-600">Resultaten per pagina:</label>
                <select name="results_per_page" id="results_per_page" class="border border-gray-300 rounded px-2 py-1 w-16" onchange="this.form.submit()">
                    <option value="15" {{ request('results_per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="30" {{ request('results_per_page', 15) == 30 ? 'selected' : '' }}>30</option>
                    <option value="all" {{ request('results_per_page', 15) == 'all' ? 'selected' : '' }}>Alles</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <select name="sort" class="border border-gray-300 rounded px-2 py-1" onchange="this.form.submit()">
                    <option value="" disabled selected>Sorteer op</option>
                    <option value="newest">Nieuwste</option>
                    <option value="oldest">Oudste</option>
                    <option value="customer_name">Naam klant A-Z</option>
                    <option value="shipping_method">Bestel methode</option>
                    <option value="status">Status</option>
                </select>

                <button type="submit" class="bg-blue-600 text-white rounded px-3 py-1 hover:bg-blue-700 transition duration-200">
                    Filter
                </button>
            </div>
        </div>
    </form>

    <div class="container mx-auto p-4">
        <form id="picklistForm" method="POST" action="{{ route('orders.picklist') }}" class="mb-6">
            @csrf
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selecteer</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waarde</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bron</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox" 
                                    data-date="{{ \Carbon\Carbon::parse($order->date)->format('d-M-Y') }}"
                                    data-name="{{ $order->customer_name }}"
                                    data-amount="{{ number_format($order->total_value, 2) }}"
                                    data-status="{{ $orderStatuses[$order->status] ?? 'Onbekend' }}" />
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $order->id }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->date)->format('d-M-Y') }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $order->customer_name }} 
                                @if(isset($order->username))
                                    <em>({{ $order->username}})</em>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $order->total_quantity }}</td>
                            <td class="px-4 py-2">€{{ number_format($order->total_value, 2) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $order->order_source ?? 'onbekend' }}</td>
                            <td class="px-4 py-2">{{ $orderStatuses[$order->status] ?? 'Onbekend' }}</td>
                            <td class="px-4 py-2 border-b flex space-x-2">
                                <a href="{{ route('orders.show', $order->id) }}" class="text-green-600 hover:underline" title="Bekijken">
                                    <svg class="w-5 h-5 mr-1" data-feather="eye"></svg>
                                </a>
                                @if($order->status < 2)
                                    <a href="{{ route('orders.edit', $order->id) }}" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center">
                                        <svg class="w-5 h-5 mr-1" data-feather="edit"></svg>
                                    </a>
                                    <button type="button" class="text-red-600 hover:text-red-700 inline-flex items-center delete-order-btn" 
                                        data-url="{{ route('orders.destroy', $order->id) }}"
                                        onClick="confirmDelete(this)">
                                        <svg class="w-5 h-5 mr-1" data-feather="trash-2"></svg>
                                    </button>

                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    
            <input type="hidden" name="selected_orders" id="selected_orders" value="">
    
            <!-- Knop om picklijst aan te maken -->
            <div class="mt-4 flex justify-between items-center">
                <button type="button" class="bg-green-600 text-white rounded-lg px-4 py-2 hover:bg-green-700 transition duration-200" id="generatePicklist">
                    Maak Picklijst
                </button>
            </div>
    
            @method('POST')
            <!-- Pagina navigatie -->
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </form>
    
        <!-- Modal voor bevestiging picklijst -->
        <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Bevestig Picklijst Aanmaak</h2>
                <p>Weet je zeker dat je een picklijst wilt aanmaken voor de geselecteerde bestellingen?</p>
                
                <div id="orderList" class="mt-4">
                    <h3 class="font-semibold">Geselecteerde Bestellingen:</h3>
                    <ul id="orderItems" class="list-disc pl-5">
                        <!-- Hier komen de bestellingen -->
                    </ul>
                </div>
    
                <div class="flex justify-end mt-4">
                    <button id="confirmBtn" type="button" class="bg-green-600 text-white rounded-lg px-4 py-2">Bevestig</button>
                    <button id="cancelBtn" type="button" class="bg-red-600 text-white rounded-lg px-4 py-2 ml-2">Annuleer</button>
                </div>
            </div>
        </div>
    </div>

    <form id="deleteOrderForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    
    @endsection
    @section('scripts')
    <script>
        function confirmDelete(button) {
            if (confirm('Deze order wordt verwijderd, dit kan niet ongedaan gemaakt worden. Weet je zeker dat je deze actie wilt uitvoeren?')) {
                let form = document.getElementById('deleteOrderForm');
                form.action = button.getAttribute('data-url');
                form.submit();
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const generatePicklistBtn = document.getElementById('generatePicklist');
            const confirmModal = document.getElementById('confirmModal');
            const orderItemsList = document.getElementById('orderItems');
            const confirmBtn = document.getElementById('confirmBtn');
            const cancelBtn = document.getElementById('cancelBtn');
    
            let selectedOrders = []; // Array om geselecteerde bestellingen bij te houden
    
            // Event listener voor de genereren picklijst knop
            generatePicklistBtn.addEventListener('click', function() {
                // Leeg de lijst voor elke keer dat de modal geopend wordt
                orderItemsList.innerHTML = '';
                selectedOrders = []; // Reset de geselecteerde bestellingen
    
                // Hier neem je de geselecteerde bestellingen uit de checkboxes
                const checkboxes = document.querySelectorAll('input[name="order_ids[]"]:checked');
                checkboxes.forEach(checkbox => {
                    const orderId = checkbox.value;
                    const orderDate = checkbox.getAttribute('data-date');
                    const orderName = checkbox.getAttribute('data-name');
                    const orderAmount = checkbox.getAttribute('data-amount');
                    const orderStatus = checkbox.getAttribute('data-status');
    
                    // Voeg de bestelling toe aan de lijst
                    orderItemsList.innerHTML += `<li>${orderDate} - ${orderName} - €${orderAmount} - ${orderStatus}</li>`;
                    selectedOrders.push(orderId); // Voeg orderId toe aan de array
                });
    
                // Als er geselecteerde bestellingen zijn, toon de modal
                if (selectedOrders.length > 0) {
                    confirmModal.classList.remove('hidden');
                } else {
                    alert('Selecteer ten minste één bestelling.');
                }
            });
    
            // Bevestig de aanmaak van de picklijst
            confirmBtn.addEventListener('click', function() {
                document.getElementById('selected_orders').value = selectedOrders.join(',');
                document.getElementById('picklistForm').submit(); // Zorg ervoor dat dit een POST-verzoek indient
            });
    
            // Annuleer de actie en sluit de modal
            cancelBtn.addEventListener('click', function() {
                confirmModal.classList.add('hidden');
            });
        });
    </script>
    @endsection
    
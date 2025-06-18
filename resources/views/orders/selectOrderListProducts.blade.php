@extends('layouts.app')

@section('page_title', 'Overzicht Verkoop Sinds')

@section('content')
<div class="container">
    <form method="POST" action="{{ route('orders.showSoldSinceAndItems') }}">
        @csrf
        <table>
            <tr>
                <td class="col-md-6">
                <h4>Van bestelling</h4>
<select name="order_id" id="order_id" class="form-control">
    @foreach($orders as $order)
        <option value="{{ $order->id }}" {{ ($order->id == $orderId) ? 'selected' : '' }}>
            #{{ $order->id }} - {{ $order->customer_name }} - {{ $order->created_at->format('d-m-Y') }} - Via {{ $order->order_source }}
        </option>
    @endforeach
</select>

<h4>Tot bestelling</h4>
<select name="order_id_end" id="order_id_end" class="form-control">
    <!-- Wordt dynamisch gevuld -->
</select>

                </td>
                <td class="col-md-6">
                    <h4>Verkoopkanalen</h4>
                    <select name="channel_key" class="form-control">
                        <option value="" disabled>Selecteer een verkoopkanaal</option>
                        <option value="all" {{ ($channel == 'all') ? 'selected' : '' }}>Alle</option>
                        @foreach($channels as $channelOption)
                            <option value="{{ $channelOption->value }}" {{ ($channelOption->value == $channel) ? 'selected' : '' }}>{{ $channelOption->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="row mt-3">
                    <h4>&nbsp;</h4>
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">Verkoop sinds bekijken</button>
                    </div>
                </td>
            </tr>
        </table>
    </form>

    @if(is_string($items))
        <div class="mt-4 alert alert-info">{{ $items }}</div>
    @else
        @if($items->isNotEmpty())
            <div class="bg-white shadow-lg rounded-lg overflow-hidden mt-4">
                <table class="w-full border-collapse border border-gray-300 text-gray-700 sortable">
                    <thead><!--
                        <tr class="bg-gray-200 text-gray-800">
                            <th class="border p-3 text-left">#</th>
                            <th class="border p-3 text-left"></th>
                            <th class="border p-3 text-left">SKU</th>
                            <th class="border p-3 text-left"></th>
                            <th class="border p-3 text-left"></th>
                            <th class="border p-3 text-left">Categorie</th>
                            <th class="border p-3 text-left">Aantal verkocht</th>
                            <th class="border p-3 text-left">Voorraad</th>
                            <th></th>
                        </tr>-->
                    </thead>
                    <tbody>
                        

                    @foreach($items as $item)
    @php
        $product = $item->product;
        $primaryImageLink = $product->imageLinks->firstWhere('role', 'primary') ?? $product->imageLinks->first();
        $image = $primaryImageLink?->image;
        $modalId = 'modal_' . $product->id;
        $imageId = 'modalImage_' . $product->id;
    @endphp
    <tr class="product-row" data-id="{{ $product->id }}">
    <td class=" text-center border-r w-8" rowspan="2">
    <input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $product->id }}"
                   @if(in_array($product->id, $selectedItems)) checked @endif>
        </td>
        
        <td class="w-8 text-center" rowspan="2">
                                        <a href="{{ route('products.show', $product->id) }}" target="_blank" class="text-green-600 hover:underline">
                                            <svg class="w-5 h-5 mr-1" data-feather="eye"></svg></a>
        </td>
        <td class="px-1 py-1 w-24" rowspan="2">
            @if ($image)
                <img src="{{ $image->thumbnail_location }}"
                     alt="Thumbnail"
                     class="w-20 h-20 object-cover cursor-pointer"
                     onclick="openModal('{{ $modalId }}', '{{ $image->location }}', '{{ $imageId }}')">
                <div id="{{ $modalId }}"
                     class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden"
                     onclick="handleClickOutside(event, '{{ $modalId }}', '{{ $modalId }}_content')">
                    <div id="{{ $modalId }}_content"
                         class="H-96 w-96 bg-white rounded shadow-lg p-4 relative flex items-center justify-center">
                        <span onclick="closeModal('{{ $modalId }}', '{{ $imageId }}')"
                              class="absolute to right-3 text-xl cursor-pointer text-gray-600">&times;</span>
                        <img id="{{ $imageId }}" src="" alt="Vergrote afbeelding"
                             class="max-h-[300px] object-contain" />
                    </div>
                </div>
            @else
                <span>Geen</span>
            @endif
        </td>        <td class=" w-64" rowspan="2">

            {{ $product->sku }} - {{ $product->brand->name ?? '' }} - {{ $product->name }}
        </td>
        <td class=" w-40 border-r">
            {{ $product->subgroup->name ?? '-' }}
        </td>
        <td class=" w-40 border-r">
            Voorraad: {{ $item->current_quantity ?? 0 }}
            @if($item->on_the_way_quantity > 0)
                <br><em>Onderweg: {{ $item->on_the_way_quantity }}</em>
            @endif
        </td>
        <td class=" w-60 border-r">
            <input type="text" name="vinted_title[{{ $product->id }}]" class="w-full" value="{{ $product->vinted_title ?? '' }}" />
        </td>
    </tr>
    <tr class="border-0">
    <td class="borr  w-40 border-r">
            € {{ number_format($product->vintedPrice ?? $product->regularPrice, 2, ',', '.') }}
        </td>
        <td class=" w-40 border-r">
            Verkocht: {{ $item->total_quantity }}
        </td>
        <td class=" w-60 border-r">
            <textarea name="vinted_description[{{ $product->id }}]" rows="2" class="w-full" placeholder="Vinted Omschrijving">{{ $product->vinted_description ?? '' }}</textarea>
        </td>
    </tr>
    {{-- Extra rand tussen producten --}}
    <tr><td colspan="7" class="border border-black p-0"></td></tr>
@endforeach


</tbody>

                </table>
            </div>
        @else
            <div class="mt-4 alert alert-info">Geen producten gevonden.</div>
        @endif
    @endif
</div>

@endsection

@section('scripts')
<script>
    function openModal(modalId, src, imageId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(imageId).src = src;

        // Voeg event listener toe om modal bij klik te sluiten
        document.getElementById(modalId).onclick = function () {
            closeModal(modalId, imageId);
        };
    }

    function closeModal(modalId, imageId) {
        document.getElementById(modalId).classList.add('hidden');
        document.getElementById(imageId).src = '';
    }   
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('input[name^="vinted_title"], textarea[name^="vinted_description"]').forEach(field => {
        field.addEventListener("blur", function () {
            const productId = this.name.match(/\[(\d+)\]/)[1];
            const fieldName = this.name.split('[')[0];
            const value = this.value;

            fetch('{{ route('products.updateVintedField') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_id: productId,
                    field: fieldName,
                    value: value
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Fout bij opslaan.');
                }
            })
            .catch(() => alert('Opslaan mislukt.'));
        });
    });

    const orderStart = document.querySelector('#order_id');
    const orderEnd = document.querySelector('#order_id_end');

    const allOrders = @json($ordersJson);
    const selectedStartId = parseInt(orderStart.value);
    const selectedEndId = {!! json_encode($orderIdEnd) !!};

    function updateOrderEndOptions(startId, preselectId = null) {
    orderEnd.innerHTML = '';

    const filtered = allOrders.filter(order => order.id >= startId);
    let selectedSet = false;

    filtered.forEach(order => {
        const option = document.createElement('option');
        option.value = order.id;
        option.textContent = `#${order.id} - ${order.customer_name} - ${new Date(order.created_at).toLocaleDateString('nl-NL')} - Via ${order.order_source}`;
        
        if (preselectId && parseInt(order.id) === parseInt(preselectId)) {
            option.selected = true;
            selectedSet = true;
        }

        orderEnd.appendChild(option);
    });

    if (!selectedSet && orderEnd.options.length > 0) {
        orderEnd.selectedIndex = orderEnd.options.length - 1;
    }
}

    updateOrderEndOptions(selectedStartId, selectedEndId);

    orderStart.addEventListener('change', () => {
        updateOrderEndOptions(parseInt(orderStart.value));
    });
    
        // Functie om geselecteerde items op te slaan via AJAX
        function saveSelectedItems() {
            const selectedItems = [];

            // Verzamel geselecteerde items
            document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
                selectedItems.push(checkbox.value);
            });

            const orderId = document.querySelector('select[name="order_id"]').value; // Haal order ID op
            const channelKey = document.querySelector('select[name="channel_key"]').value; // Haal kanaal op

            fetch('/orders/save-selected-items', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // CSRF-token hier toevoegen
                },
                body: JSON.stringify({
                    order_id: orderId,
                    channel_key: channelKey,
                    selected_items: selectedItems
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Geselecteerde producten zijn opgeslagen!');
                } else {
                    console.error('Er is een fout opgetreden.');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        document.querySelectorAll(".item-checkbox").forEach(checkbox => {
    const mainRow = checkbox.closest("tr");
    const nextRow = mainRow.nextElementSibling;

    function toggleHighlight(state) {
        [mainRow, nextRow].forEach(row => {
            row.classList.toggle("bg-gray-700", state);
            row.classList.toggle("text-white", state);
            row.classList.toggle("line-through", state);
        });
    }

    if (checkbox.checked) {
        toggleHighlight(true);
    }

    checkbox.addEventListener("change", function () {
        toggleHighlight(this.checked);
        saveSelectedItems();
    });
});

    });
</script>

@endsection

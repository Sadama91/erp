@extends('layouts.app')

@section('page_title', 'Bestellingen Inpakken')
@section('content')
<div class="container mx-auto px-4 py-6">

    @if($orders->isEmpty())
        <div class="bg-gray-200 border border-gray-300 rounded-lg p-4">
            Geen bestellingen om in te pakken.
        </div>
    @else
        @php
            $order = $orders->first(); // Haal de eerste bestelling op
        @endphp

        <div class="bg-white shadow-md rounded-lg mb-4">
            <div class="p-4">
                <h2 class="text-lg font-semibold">Bestelling #{{ $order->id }}</h2>
                <p>Status: {{ $order->status }}</p>
                
                <form action="{{ route('orders.send', $order->id) }}" method="POST" onsubmit="return checkPackedItems(this)">
                    @csrf

                    <table class="min-w-full border-collapse rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-gray-200 text-left">
                                <th class="px-3 py-1">Afbeelding</th>
                                <th class="px-3 py-1">SKU</th>
                                <th class="px-3 py-1">Merk</th>
                                <th class="px-3 py-1">Naam</th>
                                <th class="px-3 py-1">Aantal</th>
                                <th class="px-3 py-1">Inpakken?</th>
                            </tr>
                        </thead>
                        <tbody>
    @foreach($order->orderItems as $index => $orderItem)
        @php
            $product = $orderItem->product;
            $primaryImageLink = $product->imageLinks->firstWhere('role', 'primary') ?? $product->imageLinks->first();
            $image = $primaryImageLink?->image;
            $modalId = 'modal_' . $orderItem->id;
            $imageId = 'modalImage_' . $orderItem->id;
        @endphp
        <tr class="hover:bg-gray-50 border-b">
            <td class="px-3 py-1">
                @if ($image)
                    <!-- Trigger thumbnail -->
                    <img src="{{ $image->thumbnail_location }}"
                         alt="Thumbnail"
                         class="w-10 h-10 object-cover cursor-pointer"
                         onclick="openModal('{{ $modalId }}', '{{ $image->location }}', '{{ $imageId }}')">

                    <!-- Modal -->
                    <div id="{{ $modalId }}"
                         class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden"
                         onclick="handleClickOutside(event, '{{ $modalId }}', '{{ $modalId }}_content')">
                        <div id="{{ $modalId }}_content"
                              class="H-96 w-96 bg-white rounded shadow-lg p-4 relative max-w-[400px] max-h-[400px]">
                            <span onclick="closeModal('{{ $modalId }}', '{{ $imageId }}')"
                                  class="absolute top-2 right-3 text-xl cursor-pointer text-gray-600">&times;</span>
                            <img id="{{ $imageId }}" src="" alt="Vergrote afbeelding"
                                 class="max-w-full max-h-[350px] object-contain" />
                        </div>
                    </div>
                @else
                    <span>Geen</span>
                @endif
            </td>
            <td class="px-3 py-1">{{ $product->sku }}</td>
            <td class="px-3 py-1">{{ $product->brand->name }}</td>
            <td class="px-3 py-1">{{ $product->name }}</td>
            <td class="px-3 py-1">{{ $orderItem->quantity }}</td>
            <td class="px-3 py-1">
                <input type="checkbox" name="packed_items[]" value="{{ $product->id }}">
            </td>
        </tr>
    @endforeach
</tbody>

                    </table>

                    <button type="submit" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Verzenden
                    </button>
                </form>
            </div>
        </div>
    @endif


</div>


<!-- JS -->
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
    
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('input[name="packed_items[]"]');
        const sendButton = document.getElementById('sendButton');
        console.log(`Totaal aantal checkboxes:`);

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Controleer of alle checkboxes zijn aangevinkt
                const totalCheckboxes = checkboxes.length; // Totaal aantal checkboxes
                const checkedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked).length; // Aantal aangevinkte checkboxes

                // Log het totaal en het aantal aangevinkte checkboxes
                console.log(`Totaal aantal checkboxes: ${totalCheckboxes}`);
                console.log(`Aantal aangevinkte checkboxes: ${checkedCheckboxes}`);

                // Zet de verzendknop in of uit
                sendButton.disabled = checkedCheckboxes !== totalCheckboxes;
            });
        });
    });

    function checkPackedItems(form) {
        const packedItems = form.querySelectorAll('input[name="packed_items[]"]:checked');
        const totalItems = form.querySelectorAll('input[name="packed_items[]"]').length;

        // Controleer of alle artikelen zijn aangevinkt
        if (packedItems.length === totalItems) {
            return true; // Verzend het formulier
        } else {
            alert('Gelieve alle artikelen aan te vinken voordat je de verzending bevestigt.');
            return false; // Voorkom verzenden van het formulier
        }
    }
</script>


@endsection

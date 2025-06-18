@extends('layouts.app')

@section('page_title', 'Terug op voorraad aanmaken')

@section('content')
<div class="container mx-auto">
    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseOrderForm">
        @csrf
@if($products->count() > 0) 
        <table class="w-full border-collapse border border-gray-300 text-gray-700 sortable">
                    <thead>
                        <tr class="bg-gray-200 text-gray-800">
                            <th class="border p-3 text-left">#</th>
                            <th class="border p-3 text-left"></th>
                            <th class="border p-3 text-left">SKU</th>
                            <th class="border p-3 text-left">Naam</th>
                            <th class="border p-3 text-left">Merk</th>
                            <th class="border p-3 text-left">Categorie</th>
                            <th class="border p-3 text-left">Voorraad</th>
                        </tr>
                    </thead>
                    <tbody>
    @foreach($products as $product)

        @php
            $primaryImageLink = $product->imageLinks->firstWhere('role', 'primary') ?? $product->imageLinks->first();
            $image = $primaryImageLink?->image;
            $modalId = 'modal_' . $product->id;
            $imageId = 'modalImage_' . $product->id;
        @endphp


    <tr class="product-row" data-id="{{ $product->id }}">
        <td class="border p-2 text-center">
            <input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $product->id }}" />
        </td>
        <td class="px-3 py-1">
                @if ($image)
                    <!-- Trigger thumbnail -->
                    <img src="{{ $image->thumbnail_location }}"
                         alt="Thumbnail"
                         class="w-10 h-10 object-cover cursor-pointer"
                         onclick="openModal('{{ $modalId }}', '{{ $image->location }}', '{{ $imageId }}')">
                    <!-- Modal -->
                    <div id="{{ $modalId }}"
                        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden"
                        onclick="handleClickOutside(event, '{{ $modalId }}', '{{ $modalId }}_content')">

                        <div id="{{ $modalId }}_content"
                            class="H-96 w-96 bg-white rounded shadow-lg p-4 relative flex items-center justify-center">

                            <span onclick="closeModal('{{ $modalId }}', '{{ $imageId }}')"
                                class="absolute top-2 right-3 text-xl cursor-pointer text-gray-600">&times;</span>

                            <img id="{{ $imageId }}" src="" alt="Vergrote afbeelding"
                                class="max-h-[300px] object-contain" />
                        </div>
                    </div>


                @else
                    <span>Geen</span>
                @endif
        <td class="border p-2">{{ $product->sku }}</td>
        <td class="border p-2">{{ $product->name }}</td>
        <td class="border p-2">{{ $product->brand->name ?? '-' }}</td>
        <td class="border p-2">{{ $product->subgroup->name ?? '-' }}</td> 
        <td class="border p-2">
            {{ $product->stock->current_quantity ?? 0 }}
            @if($product->on_the_way_quantity > 0 )
                <em>Onderweg: {{ $product->stock->on_the_way_quantity }}</em>
            @endif
        </td>
    </tr>
     @endforeach
        </tbody>

                </table>
            </div>
        @else
            <div class="mt-4 alert alert-info">Geen producten gevonden.</div>
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

    // Event listener voor checkboxes met AJAX-aanroep
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                const productId = this.value;
                // Wanneer aangevinkt, zet back_in_stock op false; anders (bij herselectie) op true
                const backInStock = this.checked ? false : true;
                fetch("{{ route('inventory.back_in_stock') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        back_in_stock: backInStock
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data);
                })
                .catch((error) => {
                    console.error('Error updating back in stock:', error);
                });
            });
        });
         // Markeer geselecteerde checkboxes bij het laden van de pagina
         document.querySelectorAll(".item-checkbox").forEach(checkbox => {
            let row = checkbox.closest("tr");
            if (checkbox.checked) {
                row.classList.add("bg-gray-700", "text-white", "line-through");
            }
            checkbox.addEventListener("change", function() {
                if (this.checked) {
                    row.classList.add("bg-gray-700", "text-white", "line-through");
                } else {
                    row.classList.remove("bg-gray-700", "text-white", "line-through");
                }
                //saveSelectedItems(); // Roep de functie aan om de geselecteerde items op te slaan
            });
        });

    });
    
</script>
@endsection
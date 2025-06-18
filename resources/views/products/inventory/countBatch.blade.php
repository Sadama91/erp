@extends('layouts.app')

@section('page_title', 'Locatie '. $location .' tellen')

@section('content')<div class="container">
    

    <a href="{{ route('inventory.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-500 transition duration-300">Terug naar locaties</a>

    <form action="{{ route('inventory.updateStock') }}" method="POST">
        @csrf
        <div class="overflow-hidden shadow rounded-lg mt-4">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                            <th class="border p-3 text-left">SKU</th>
                            <th class="border p-3 text-left"></th>
                            <th class="border p-3 text-left">Naam</th>
                            <th class="border p-3 text-left">Merk</th>
                            <th class="border p-3 text-left">Actuele voorraad</th>
                            <th class="border p-3 text-left">Onderweg</th>
                            <th class="border p-3 text-left">Telling</th>
                            <th class="border p-3 text-left">Verschil</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)

@php
    $primaryImageLink = $product->imageLinks->firstWhere('role', 'primary') ?? $product->imageLinks->first();
    $image = $primaryImageLink?->image;
    $modalId = 'modal_' . $product->id;
    $imageId = 'modalImage_' . $product->id;
@endphp


<tr class="product-row hover:bg-gray-50" data-id="{{ $product->id }}">

<td class="px-4 py-2 border-b border-gray-200">{{ $product->sku }}</td>
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
             @endif</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $product->name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $product->brand->name ?? null }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $product->stock->current_quantity ?? 0 }}</td>
                            <td class="px-4 py-2 border-b border-gray-200"><em>{{ $product->stock->on_the_way_quantity ?? 0 }}</em></td>
                            <td class="px-4 py-2 border-b border-gray-200">
                                <input type="number" class="new-stock-input" name="stocks[{{ $product->id }}]" value="" min="0">
                            </td>
                            <td class="px-4 py-2 border-b border-gray-200 difference-cell" data-sku="{{ $product->sku }}"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center px-4 py-2 border-b border-gray-200">Geen producten gevonden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <button type="submit" class="mt-4 bg-green-600 text-white px-6 py-2 rounded shadow hover:bg-green-500 transition duration-300">
            Voorraad bijwerken
        </button>
    </form>
</div>


<script>
    document.querySelectorAll('.new-stock-input').forEach(input => {
        const row = input.closest('tr');
        const currentStock = parseInt(row.children[4].textContent) || 0;
        const differenceCell = row.querySelector('.difference-cell');

        // Sla de originele voorraad op
        input.dataset.currentStock = currentStock;

        input.addEventListener('input', function() {
            const newStock = parseInt(this.value) || 0;
            const difference = newStock - parseInt(this.dataset.currentStock);

            // Leeg de cel en verwijder oude kleurklassen bij elke input
            differenceCell.textContent = difference;
            differenceCell.className = 'px-4 py-2 border-b border-gray-200';

            // Voeg de juiste kleur toe op basis van het verschil
            if (difference === 0) {
                differenceCell.classList.add('bg-green-100'); // Groen
            } else if (Math.abs(difference) >= 1 && Math.abs(difference) <= 2) {
                differenceCell.classList.add('bg-yellow-100'); // Geel
            } else if (Math.abs(difference) >= 3 && Math.abs(difference) <= 5) {
                differenceCell.classList.add('bg-orange-100'); // Oranje
            } else if (Math.abs(difference) > 5) {
                differenceCell.classList.add('bg-red-100'); // Rood
            }
        });
    });
</script>
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
</script>

@endsection

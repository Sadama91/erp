@extends('layouts.app')

@section('page_title', 'Picklijst')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Terug naar Bestellingen</a>
    </div>

    @foreach($pickList as $location => $entries)
        <div class="bg-white shadow-md rounded-lg mb-4">
            <div class="cursor-pointer hover:bg-gray-300 p-4 rounded-lg" onclick="toggleTable('{{ $location }}')">
                <strong>Locatie:</strong> {{ $location }} —
                <strong>Aantal producten:</strong> {{ count($entries) }} —
                <strong>Aantal items:</strong> {{ array_sum(array_column($entries, 'quantity')) }}
            </div>
            <div class="p-4" id="table-{{ $location }}">
                <table class="min-w-full border-collapse rounded-lg overflow-hidden">
                <thead onclick="toggleTable('{{ $location }}')">                       
                     <tr class="bg-gray-200 text-left">
                            <th class="px-3 py-1">Afbeelding</th>
                            <th class="px-3 py-1">Aantal</th>
                            <th class="px-3 py-1">SKU</th>
                            <th class="px-3 py-1">Merk</th>
                            <th class="px-3 py-1">Naam</th>
                            <th class="px-3 py-1">Voorraad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            @php
                                $product = $entry['product'];
                                $modalId = 'modal_' . $product->id;
                                $imageId = 'modalImage_' . $product->id;
                                $primaryImageLink = $product->imageLinks->firstWhere('role', 'primary') ?? $product->imageLinks->first();
                                $image = $primaryImageLink?->image;
                            @endphp
                            <tr class="hover:bg-gray-50 border-b">
                                <td class="px-3 py-1">
                                    @if ($image)
                                    <img src="{{ $image->thumbnail_location }}"
                            class="w-10 h-10 object-cover cursor-pointer"
                            onclick="openModal('{{ $modalId }}', '{{ $image->location }}', '{{ $imageId }}')">

                        <!-- Modal -->
<div id="{{ $modalId }}" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div id="{{ $modalId }}_content"
             class="H-96 w-96 bg-white rounded shadow-lg p-4 relative">
            <span onclick="closeModal('{{ $modalId }}', '{{ $imageId }}')"
                  class="absolute top-2 right-3 text-xl cursor-pointer text-gray-600">&times;</span>
            <img id="{{ $imageId }}" src="" alt="Vergrote afbeelding"
                 class="max-w-full max-h-[350px] object-contain cursor-pointer"
                 onclick="closeModal('{{ $modalId }}', '{{ $imageId }}')" />
        </div>
    </div>
</div>



                                    @else
                                        <span>Geen</span>
                                    @endif
                                </td>
                                <td class="px-3 py-1">{{ $entry['quantity'] }}</td>
                                <td class="px-3 py-1">{{ $product->sku }}</td>
                                <td class="px-3 py-1">{{ $product->brand->name ?? '-' }}</td>
                                <td class="px-3 py-1">{{ $product->name }}</td>
                                <td class="px-3 py-1 text-sm text-gray-700">{{ $entry['stock_info'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if(!empty($selectedOrdersArray))
        <div class="mt-4">
            <a href="{{ route('orders.pack', implode(',', $selectedOrdersArray)) }}" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Pak deze bestellingen in</a>
        </div>
    @endif
</div>
@endsection
@section('scripts')<script>
    function openModal(modalId, src, imageId) {
        const modal = document.getElementById(modalId);
        const img = document.getElementById(imageId);

        modal.classList.remove('hidden');
        img.src = src;

       
    }

    function closeModal(modalId, imageId) {
        const modal = document.getElementById(modalId);
        const img = document.getElementById(imageId);

        modal.classList.add('hidden');
        img.src = '';
    }

    function toggleTable(location) {
        const tableWrapper = document.getElementById('table-' + location);
        const table = tableWrapper.querySelector('table');

        if (table.style.display === 'none') {
            table.style.display = 'table';
            tableWrapper.classList.remove('bg-gray-200')
        } else {
            table.style.display = 'none';
            tableWrapper.classList.add('bg-gray-200')
        }
    }

    // Alles standaard tonen
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="table-"]').forEach(function (wrapper) {
            const table = wrapper.querySelector('table');
            if (table) table.style.display = 'table';
        });
    });
</script>

@endsection

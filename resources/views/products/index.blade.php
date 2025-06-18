@extends('layouts.app')
@section('page_title', 'Overzicht producten')

@section('content')
<div class="container mx-auto my-8">
    <!-- Nieuwe productknop -->
    <div class="flex mb-6">
        <a href="{{ route('products.create') }}" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
            <i class="fas fa-plus"></i> Nieuwe product toevoegen
        </a>
        <button id="change-status-btn" class="bg-yellow-600 text-white rounded-lg px-4 py-2 hover:bg-yellow-700 transition duration-200 ml-4" disabled>
            Wijzig status
        </button>

        <button id="assign-location-btn" data-modal-target="#assign-location-modal" class="bg-gray-600 text-white rounded-lg px-4 py-2 hover:bg-green-700 transition duration-200 ml-4" disabled>
            Pick locaties toewijzen
        </button>
        <button id="copy-product-btn" class="bg-gray-600 text-white rounded-lg px-4 py-2 hover:bg-green-700 transition duration-200 ml-4">
            Kopieer geselecteerd product
        </button>
        <button id="bulk-edit-btn" class="bg-gray-600 text-white rounded-lg px-4 py-2 hover:bg-green-700 transition duration-200 ml-4" disabled>
    Bulk bewerken
</button>

    </div>

    <!-- Filtersectie -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form action="{{ route('products.index') }}" method="GET" class="grid grid-cols-4 gap-4">

        <div class="col-span-1">
            <label for="subgroup" class="block text-sm font-medium text-gray-700">Subgroepen</label>
            <select id="subgroup" name="subgroup" multiple  class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                <option value="">Alle subgroepen</option>
                @foreach($subgroups as $subgroup)
                    <option value="{{ $subgroup->id }}" {{ request()->get('subgroup') == $subgroup->id ? 'selected' : '' }}>{{ $subgroup->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-span-1">
            <label for="brand" class="block text-sm font-medium text-gray-700">Merken</label>
            <select id="brand" name="brand" multiple class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                <option value="">Alle merken</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request()->get('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-1">
            <label for="location" class="block text-sm font-medium text-gray-700">Locatie</label>
            <select id="location" name="location[]" multiple class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                <option value="all" {{ request()->get('location') == 'all' ? 'selected' : '' }}>Alle locaties</option>
                <option value="allocated" {{ request()->get('location') == 'all' ? 'selected' : '' }}>Toegewezen locaties</option>
                <option value="none" {{ request()->get('location') == 'none' ? 'selected' : '' }}>Niet toegewezen</option>
        
                @foreach($locations as $location)
                    @php
                        // Zoek de parameter op basis van de value
                        $locationParameter = $locationIds->firstWhere('value', $location);
                    @endphp
        
                    @if($locationParameter) <!-- Controleer of de locatieparameter bestaat -->
                        <option value="{{ $location }}" {{ in_array($location, request()->get('location', [])) ? 'selected' : '' }}>
                            {{ $locationParameter->name }} <!-- Toon de naam van de locatie -->
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
        
        
        <div class="col-span-1">
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status" multiple class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                <option value="">Alle statussen</option>
                @foreach($articleStatuses as $status)
                    <option value="{{ $status->name }}" {{ request()->get('status') == $status->name ? 'selected' : '' }}>
                        {{ $status->name }} - {{ $status->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-span-2">
            <label for="search" class="block text-sm font-medium text-gray-700">Zoek op naam of SKU</label>
            <input type="text" id="search" name="search" value="{{ request()->get('search') }}" placeholder="Zoek op naam of SKU..." class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
        </div>
        <div class="col-span-1">
            <label for="per_page" class="block text-sm font-medium text-gray-700">Aantal weer te geven</label>
            <select id="per_page" name="per_page" class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 w-16">
                <option value="25" {{ request()->get('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request()->get('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request()->get('per_page') == 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ request()->get('per_page') == 200 ? 'selected' : '' }}>200</option>
            </select>
        </div>

        <div class="col-span-1 md:col-span-3 flex items-end">
            <button type="submit" class="btn btn-secondary bg-indigo-600 text-white rounded-md shadow-md hover:bg-indigo-700 w-full py-2">
                Filteren
            </button>
        </div>
    </form>
</div>


    <!-- Productentabel -->
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="table-auto w-full border-collapse">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-2 border-b"><input type="checkbox" id="select-all"></th>
                    @foreach(['sku' => 'SKU', 'name' => 'Naam', 'brand_id' => 'Merk', 'subgroup_id' => 'Subgroep', 'price' => 'Prijs', 'stock' => 'Voorraad', 'location' => 'Locatie','status' => 'Status'] as $field => $label)
                        <th class="text-left px-4 py-2 border-b {{ request()->get('sort_field') == $field ? 'bg-gray-100' : '' }}">
                            <a href="{{ route('products.index', array_merge(request()->all(), [
                                'sort_field' => $field, 
                                'sort_direction' => request()->get('sort_field') == $field && request()->get('sort_direction') == 'asc' ? 'desc' : 'asc'
                            ])) }}" class="hover:underline flex items-center">
                                {{ $label }}
                                @if(request()->get('sort_field') == $field)
                                    <span class="ml-1">
                                        {{ request()->get('sort_direction') == 'asc' ? '▲' : '▼' }}
                                    </span>
                                @endif
                            </a>
                        </th>
                    @endforeach
                    <th class="px-4 py-2 border-b">Acties</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border-b border-gray-200">
                            <input type="checkbox" class="select-product" name="product_ids[]" value="{{ $product->id }}  id="product_{{ $product->id }}" {{ in_array($product->id, $selectedProductIds) ? 'checked' : '' }}">
                        </td>
                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->sku }}</td>
                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->brand->name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->subgroup->name }}</td>@php
    $regularPrice = $product->prices
        ->where('type', 'regular')
        ->whereNull('valid_till')
        ->first();
@endphp

<td class="px-4 py-2 border-b border-gray-200">
    {{ $regularPrice ? '€' . number_format($regularPrice->price, 2, ',', '.') : 'N/A' }}
</td>

                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->stock->current_quantity ?? 0 }}</td>
                        <td class="px-4 py-2 border-b border-gray-200"> {{ optional($product->locationParameter)->name }}
                        </td>
                        <td class="px-4 py-2 border-b border-gray-200">{{ $product->getStatusLabel($product->status) }}</td>
                        <td class="px-4 py-2 border-b border-gray-200">
                            <td class="px-4 py-2 border-b border-gray-200">
    <table>
        <tr>
            <td>
                <a href="{{ route('products.show', $product->id) }}" class="text-green-600 hover:underline">
                    <svg class="w-5 h-5 mr-1" data-feather="eye"></svg>
                </a>
            </td>
            <td>
                <a href="{{ route('products.edit', $product->id) }}" class="text-blue-600 hover:underline">
                    <svg class="w-5 h-5 mr-1" data-feather="edit"></svg>
                </a>
            </td>
            @if ($product->status <= 9)
            <td>
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Weet je zeker dat je dit product wilt verwijderen?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">
                        <svg class="w-5 h-5 mr-1" data-feather="trash-2"></svg>
                    </button>
                </form>
            </td>
            @endif
        </tr>
    </table>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center px-4 py-2 border-b border-gray-200">Geen producten gevonden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginering sectie -->
        <div class="mt-4">
            <div class="flex justify-center items-center mt-2">Selecteer het aantal artikelen dat je wilt weergeven: 
                <form action="{{ route('products.index') }}" method="GET" class="flex items-center">
                    <input type="hidden" name="search" value="{{ request()->get('search') }}">
                    <input type="hidden" name="subgroup" value="{{ request()->get('subgroup') }}">
                    <input type="hidden" name="brand" value="{{ request()->get('brand') }}">
                    <input type="hidden" name="stock_status" value="{{ request()->get('stock_status') }}">
                    <select name="per_page" class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mr-2">
                        <option value="25" {{ request()->get('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request()->get('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request()->get('per_page') == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ request()->get('per_page') == 200 ? 'selected' : '' }}>200</option>
                    </select>
                    <button type="submit" class="btn btn-secondary bg-indigo-600 text-white rounded-md shadow-md hover:bg-indigo-700 px-4 py-2">
                        Toepassen
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    <!-- Modal voor statuswijziging --><!-- Modal voor statuswijziging -->
    <div id="statusModal" class="modal fixed inset-0 z-50 hidden bg-gray-500 bg-opacity-75 flex justify-center items-center">
        <div class="bg-white rounded-lg p-6 w-112 max-h-full overflow-auto">
            <h3 class="text-lg font-semibold mb-4">Wijzig status</h3>
            <form id="statusChangeForm" action="{{ route('products.updateStatus') }}" method="POST">
                @csrf
                <input type="hidden" name="product_skus[]" id="status_product_skus">
                <div class="mb-4">
                    <label for="new_status" class="block mb-2">Nieuwe status:</label>
                    <select name="new_status" id="new_status" class="form-control rounded-md" required>
                        @foreach($articleStatuses as $status)
                            <option value="{{ $status->name }}">{{ $status->name }} - {{ $status->value }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="selected-products-list" class="mb-4">
                    <!-- Dynamische lijst van geselecteerde producten -->
                </div>
                <div class="flex justify-end">
                    <button type="button" class="close-modal-btn bg-gray-300 text-gray-800 rounded-md px-4 py-2 mr-2">Annuleren</button>
                    <button type="submit" class="bg-blue-600 text-white rounded-md px-4 py-2">Opslaan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="assign-location-modal" class="modal fixed inset-0 z-50 hidden bg-gray-500 bg-opacity-75 flex justify-center items-center">
        <div class="bg-white rounded-lg p-6 w-112 max-h-full overflow-auto">
            <h3 class="text-lg font-semibold mb-4">Locaties toewijzen</h3>
            <form id="assign-location-form" action="{{ route('products.bulkAssignLocation') }}" method="POST">
                @csrf
                <input type="hidden" name="product_skus[]" id="location_product_skus">
                <div class="mb-4">
                    <label for="new_location" class="block mb-2">Nieuwe locatie:</label>
                    <select name="new_location" id="new_location" class="form-control rounded-md" required>
                        @foreach($locationIds as $location)
                            <option value="{{ $location->value }}">-{{ $location->value }}-{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="selected-products-list-location" class="mb-4 max-h-48 overflow-y-auto border border-gray-300 p-4 bg-gray-100">
                    <!-- Dynamische lijst van geselecteerde producten -->
                </div>
                <div class="flex justify-end">
                    <button type="button" class="close-modal-btn bg-gray-300 text-gray-800 rounded-md px-4 py-2 mr-2">Annuleren</button>
                    <button type="submit" class="bg-blue-600 text-white rounded-md px-4 py-2">Opslaan</button>
                </div>
            </form>
        </div>
    </div>
<!-- Kopieer Product Modal -->
<div id="copy-product-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96">
        <h2 class="text-xl font-semibold mb-4">Kopieer product</h2>
        <p>Hoeveel kopieën wilt u maken?</p>
        <input type="number" id="copy-quantity" class="w-full border rounded-lg p-2 mt-2" min="1" value="1">
        <div class="flex justify-end mt-4">
            <button id="cancel-copy" class="bg-gray-400 text-white px-4 py-2 rounded-lg mr-2">Annuleren</button>
            <button id="confirm-copy" class="bg-purple-600 text-white px-4 py-2 rounded-lg">Kopieer</button>
        </div>
    </div>
</div>

<!-- bulk edit modal -->
    <div id="bulk-edit-modal" class="modal fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-75 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 w-112 max-h-full overflow-auto">
        <h3 class="text-lg font-semibold mb-4 text-red-700">Bulkbewerking bevestigen</h3>
        <p class="mb-4 text-sm text-gray-600">Je staat op het punt meerdere producten tegelijk te bewerken. Wees voorzichtig — wijzigingen gelden voor alle geselecteerde producten.</p>
        
        <div id="bulk-edit-products-list" class="mb-4 max-h-48 overflow-y-auto border border-gray-300 p-4 bg-gray-100 text-sm"></div>

        <div class="flex justify-end">
            <button type="button" class="close-modal-btn bg-gray-300 text-gray-800 rounded-md px-4 py-2 mr-2">Annuleren</button>
            <button id="confirm-bulk-edit" class="bg-green-600 text-white rounded-md px-4 py-2">Doorgaan</button>
        </div>
    </div>
</div>

    

    
</div>

@endsection
@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    console.log("Script geladen!");

    // --- Elementen verzamelen
    const selectAllCheckbox = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.select-product');
    const changeStatusBtn = document.getElementById('change-status-btn');
    const openModalBtn = document.getElementById('assign-location-btn');
    const bulkEditBtn = document.getElementById('bulk-edit-btn'); 
    const statusModal = document.getElementById('statusModal');
    const locationModal = document.getElementById('assign-location-modal');
    const bulkEditModal = document.getElementById('bulk-edit-modal'); 
    const closeModalBtns = document.querySelectorAll('.close-modal-btn');
    const selectedProductsList = document.getElementById('selected-products-list');
    const selectedProductsListLocation = document.getElementById('selected-products-list-location');
    const bulkEditList = document.getElementById('bulk-edit-products-list'); 
    const confirmBulkEditBtn = document.getElementById('confirm-bulk-edit'); 
const copyButton = document.getElementById("copy-product-btn");
const copyModal = document.getElementById("copy-product-modal");
const confirmCopyBtn = document.getElementById("confirm-copy");
const cancelCopyBtn = document.getElementById("cancel-copy");
const quantityInput = document.getElementById("copy-quantity");

    // --- Modal standaard verbergen
    statusModal.classList.add('hidden');
    locationModal.classList.add('hidden');
    bulkEditModal.classList.add('hidden');

    // --- Select all
    selectAllCheckbox.addEventListener('change', () => {
        const isChecked = selectAllCheckbox.checked;
        productCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
        toggleButtons();
    });

    // --- Checkbox veranderingen activeren knoppen
    productCheckboxes.forEach(checkbox => checkbox.addEventListener('change', toggleButtons));

    function toggleButtons() {
        const anyChecked = [...productCheckboxes].some(cb => cb.checked);
        changeStatusBtn.disabled = !anyChecked;
        openModalBtn.disabled = !anyChecked;
        bulkEditBtn.disabled = !anyChecked; // 🔶 Nieuw
    }

    // --- Status wijzigen modal openen
    changeStatusBtn.addEventListener('click', () => {
        populateModal(productCheckboxes, selectedProductsList, document.getElementById('status_product_skus'));
        statusModal.classList.remove('hidden');
    });

    // --- Locatie toewijzen modal openen
    openModalBtn.addEventListener('click', () => {
        populateModal(productCheckboxes, selectedProductsListLocation, document.getElementById('location_product_skus'));
        locationModal.classList.remove('hidden');
    });

    // --- 🔶 Bulk bewerking openen
    bulkEditBtn.addEventListener('click', () => {
        const selectedProducts = Array.from(productCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                id: cb.value,
                sku: cb.closest('tr').querySelector('td:nth-child(2)').innerText.trim(),
                name: cb.closest('tr').querySelector('td:nth-child(3)').innerText.trim(),
            }));

        if (selectedProducts.length === 0) {
            alert("Selecteer eerst producten.");
            return;
        }

        // Toon lijst in modal
        bulkEditList.innerHTML = selectedProducts.map(p =>
            `<p><strong>${p.sku}</strong> - ${p.name}</p>`
        ).join('');

        bulkEditModal.classList.remove('hidden');

        // Bevestiging
        confirmBulkEditBtn.onclick = () => {
            const ids = selectedProducts.map(p => p.id).join(',');
            window.location.href = `/products/bulk-edit?ids=${ids}`;
        };
    });

    // --- Modals sluiten
    closeModalBtns.forEach(button =>
        button.addEventListener('click', () => {
            button.closest('.modal').classList.add('hidden');
        })
    );

    // --- Hulpmethode: vul modals met geselecteerde producten
    function populateModal(checkboxes, productListContainer, skuInput) {
        const selectedProducts = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => ({
                sku: checkbox.closest('tr').querySelector('td:nth-child(2)').innerText.trim(),
                name: checkbox.closest('tr').querySelector('td:nth-child(3)').innerText.trim(),
            }));

        if (skuInput) {
            skuInput.value = JSON.stringify(selectedProducts.map(p => p.sku));
        }

        if (productListContainer) {
            productListContainer.innerHTML = selectedProducts.map(product =>
                `<p><strong>${product.sku}</strong> - ${product.name}</p>`
            ).join('');
        }
    }

    console.log("Event listeners ingesteld.");

if (copyButton && copyModal && confirmCopyBtn && cancelCopyBtn && quantityInput) {
    copyButton.addEventListener("click", () => {
        const selected = [...productCheckboxes].filter(cb => cb.checked);

        if (selected.length === 0) {
            alert("Selecteer een product om te kopiëren.");
            return;
        }

        if (selected.length > 1) {
            alert("Je kunt slechts één product tegelijk kopiëren.");
            return;
        }

        copyModal.classList.remove("hidden");
    });

    cancelCopyBtn.addEventListener("click", () => {
        copyModal.classList.add("hidden");
    });

    confirmCopyBtn.addEventListener("click", () => {
        const selected = [...productCheckboxes].filter(cb => cb.checked);
        const selectedProductId = selected[0].value;
        const quantity = parseInt(quantityInput.value, 10);

        if (isNaN(quantity) || quantity <= 0) {
            alert("Geef een geldig aantal op.");
            return;
        }

        // Redirect
        window.location.href = `/products/${selectedProductId}/copy?quantity=${quantity}`;
    });
}
});
</script>
@endsection


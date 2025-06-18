@extends('layouts.app')

@section('page_title', 'Product aanmaken')

@section('content')
    <div class="container mx-auto p-6">
        <div class="flex space-x-4 mb-4">
            <a href="{{ route('products.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-400">
                &larr; Terug
            </a>
        </div>

        <form id="editProductForm" action="{{ route('products.store') }}" method="POST">
            @csrf

            @php
                // Indien eerder ingevuld, gebruiken we old('categories')
                $oldCategories = old('categories', []);
                // Voor verkoopkanalen, zorg dat we een array hebben.
                $oldSalesChanels = old('sales_chanel', []);
            @endphp

            {{-- Informatie secties met grid layout --}}
            <div class="grid gap-y-10 sm:mx-auto sm:w-2/3 md:mx-0 md:w-auto md:grid-cols-1 md:gap-x-5 lg:grid-cols-2 xl:grid-cols-2">

                <!-- Cluster 1: Basisgegevens -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Basisgegevens</h2>
                    <label for="name" class="block font-medium text-gray-700">Naam</label>
                    <input type="text" id="name" name="name" class="w-full border-gray-300 rounded-md"
                        value="{{ old('name') }}" required>

                    <label for="sku" class="block font-medium text-gray-700">SKU</label>
                    <input type="text" id="sku" name="sku" class="w-full border-gray-300 rounded-md"
                        placeholder="Wordt bepaald" disabled>

                    <label for="brand_id" class="block font-medium text-gray-700">Merk</label>
                    <select id="brand_id" name="brand_id" class="w-full border-gray-300 rounded-md">
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}"
                                {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}</option>
                        @endforeach
                    </select>
                    
                    <!-- Subgroep selecteren -->
                    <label for="subgroup_id" class="block font-medium text-gray-700">Subgroep</label>
                    <select id="subgroup_id" name="subgroup_id" class="w-full border-gray-300 rounded-md">
                        @foreach ($subgroups as $subgroup)
                            <option value="{{ $subgroup->id }}"
                                {{ old('subgroup_id') == $subgroup->id ? 'selected' : '' }}>
                                {{ $subgroup->name }}</option>
                        @endforeach
                    </select>

                    <label for="location" class="block text-sm font-medium text-gray-700">Locatie</label>
                    <select id="location" name="location" class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                        <option value="" {{ old('location') == '' ? 'selected' : '' }}>Geen</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->value }}" {{ old('location') == $location->value ? 'selected' : '' }}>
                                {{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Groeperingen -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Groeperingen</h2>

                    <!-- Categorieën in scrollbaar venster -->
                    <label class="block font-medium text-gray-700 mt-4">Categorieën</label>
                    <div class="border rounded-md p-2 max-h-60 overflow-y-auto">
                        @foreach ($categories as $category)
                            @if (!$category->parent_id)
                                <div class="flex items-center">
                                    <input type="checkbox" id="category_{{ $category->id }}" name="categories[]"
                                        value="{{ $category->id }}" 
                                        {{ in_array($category->id, $oldCategories) ? 'checked' : '' }}
                                        class="mr-2">
                                    <label for="category_{{ $category->id }}" class="text-gray-700">
                                        {{ $category->name }}</label>
                                </div>
                                @if ($category->children)
                                    <div class="pl-4">
                                        @foreach ($category->children as $child)
                                            <div class="flex items-center">
                                                <input type="checkbox" id="category_{{ $child->id }}"
                                                    name="categories[]" value="{{ $child->id }}"
                                                    {{ in_array($child->id, $oldCategories) ? 'checked' : '' }}
                                                    class="mr-2">
                                                <label for="category_{{ $child->id }}" class="text-gray-600">
                                                    {{ $child->name }}</label>
                                            </div>
                                            @if ($child->children)
                                                <div class="pl-4">
                                                    @foreach ($child->children as $grandchild)
                                                        <div class="flex items-center">
                                                            <input type="checkbox" id="category_{{ $grandchild->id }}"
                                                                name="categories[]" value="{{ $grandchild->id }}"
                                                                {{ in_array($grandchild->id, $oldCategories) ? 'checked' : '' }}
                                                                class="mr-2">
                                                            <label for="category_{{ $grandchild->id }}"
                                                                class="text-gray-500">{{ $grandchild->name }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>

                    <div>
                        <label for="tags" class="block font-medium text-gray-700">Tags</label>
                        <input type="text" id="tags" name="tags" class="w-full border-gray-300 rounded-md p-2"
                            value="{{ old('tags') }}" placeholder="Voer tags in..." autocomplete="off">
                        <div id="tag-suggestions"
                            class="border rounded-md mt-1 bg-white shadow-sm max-h-40 overflow-y-auto hidden"></div>
                        <small class="text-gray-500">Bijvoorbeeld: Varken, Kip</small>
                    </div>
                </div>

                <!-- Cluster: Type & Verkoopkanalen -->
                <div class="bg-white shadow-sm rounded-lg p-4 w-full">
                    <h2 class="text-lg font-bold mb-4">Type & Verkoopkanalen</h2>
                    <div>
                        <label for="product_type" class="block font-medium text-gray-700">Producttype</label>
                        <select id="product_type" name="product_type" class="w-full border-gray-300 rounded-md">
                            @foreach ($productTypes as $productType)
                                <option value="{{ $productType->id }}"
                                    {{ old('product_type') == $productType->id ? 'selected' : '' }}>
                                    {{ $productType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sales_chanel" class="block font-medium text-gray-700">Verkoopkanaal</label>
                        <select id="sales_chanel" name="sales_chanel[]" class="w-full border-gray-300 rounded-md" multiple>
                            @foreach ($salesChanels as $salesChanel)
                                <option value="{{ $salesChanel->id }}"
                                    {{ in_array($salesChanel->id, $oldSalesChanels) ? 'selected' : '' }}>
                                    {{ $salesChanel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <input type="checkbox" id="available_for_web" name="available_for_web" class="border-gray-300 rounded-md" 
                        {{ old('available_for_web', true) ? 'checked' : '' }}>
                        <label for="available_for_web" class="font-medium px-2 text-gray-700">Beschikbaar voor web</label>
                    </div>

                    <div>
                        <label for="vat_rate_id" class="block font-medium text-gray-700">BTW percentage</label>
                        <select id="vat_rate_id" name="vat_rate_id" class="w-full border-gray-300 rounded-md">
                            @foreach ($vatRates as $vatRate)
                                <option value="{{ $vatRate->id }}" {{ old('vat_rate_id') == $vatRate->id ? 'selected' : '' }}>
                                    {{ $vatRate->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Cluster: Inkoop & prijzen -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Inkoop & prijzen</h2>
                    <label for="supplier_id" class="block text-gray-700">Leverancier</label>
                    <select name="supplier_id" id="supplier_id" class="border rounded-lg w-full p-2" required>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}</option>
                        @endforeach
                    </select>

                    <div>
                        <label for="purchase_quantity" class="block font-medium text-gray-700">Inkoopeenheid (per hoeveel koop je in)</label>
                        <input type="number" step="0.01" id="purchase_quantity" name="purchase_quantity"
                            class="w-full border-gray-300 rounded-md" value="{{ old('purchase_quantity') }}" required>
                    </div>
                    <div>
                        <label for="sale_quantity" class="block font-medium text-gray-700">Verkoopeenheid (per hoeveel verkoop je)</label>
                        <input type="number" step="0.01" id="sale_quantity" name="sale_quantity"
                            class="w-full border-gray-300 rounded-md" value="{{ old('sale_quantity') }}" required>
                    </div>

                    <div>
                        <h3>Verkoopprijzen</h3>
                        <div>
                            <label for="regular_price" class="block font-medium text-gray-700">Reguliere Prijs</label>
                            <input type="number" step="0.01" id="regular_price" name="regular_price"
                                class="w-full border-gray-300 rounded-md" value="{{ old('regular_price') }}" required>
                        </div>
                        <div>
                            <label for="vinted_price" class="block font-medium text-gray-700">Vinted Prijs</label>
                            <input type="number" step="0.01" id="vinted_price" name="vinted_price"
                                class="w-full border-gray-300 rounded-md" value="{{ old('vinted_price') }}">
                        </div>
                    </div>
                </div>

                <!-- Cluster: Dimensies en Verzendklasse -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Dimensies & Verzendklasse</h2>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="mb-4">
                            <label for="height" class="block text-gray-700">Hoogte (cm)</label>
                            <input type="number" name="height" id="height" class="border rounded-lg w-full p-2"
                                step="0.01" value="{{ old('height') }}">
                        </div>
                        <div class="mb-4">
                            <label for="width" class="block text-gray-700">Breedte (cm)</label>
                            <input type="number" name="width" id="width" class="border rounded-lg w-full p-2"
                                step="0.01" value="{{ old('width') }}">
                        </div>
                        <div class="mb-4">
                            <label for="depth" class="block text-gray-700">Diepte (cm)</label>
                            <input type="number" name="depth" id="depth" class="border rounded-lg w-full p-2"
                                step="0.01" value="{{ old('depth') }}">
                        </div>
                        <div class="mb-4">
                            <label for="weight" class="block text-gray-700">Gewicht (gr)</label>
                            <input type="number" name="weight" id="weight" class="border rounded-lg w-full p-2"
                                step="1" value="{{ old('weight') }}">
                        </div>
                    </div>
                    <div class="col-span-2 mb-4">
                        <label for="shipping_class" class="block font-medium text-gray-700">Verzendklasse</label>
                        <select id="shipping_class" name="shipping_class" class="w-full border-gray-300 rounded-md">
                            @foreach ($shippingClasses as $shippingClass)
                                <option value="{{ $shippingClass->id }}"
                                    {{ old('shipping_class') == $shippingClass->id ? 'selected' : '' }}>
                                    {{ $shippingClass->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Cluster: Webinformatie -->
                <div class="bg-white shadow-sm rounded-lg p-4 w-full">
                    <h2 class="text-lg font-bold mb-4">Webinformatie</h2>
                    <div>
                        <label for="short_description" class="block font-medium text-gray-700">Korte Beschrijving</label>
                        <textarea id="short_description" name="short_description" class="w-full border-gray-300 rounded-md">{{ old('short_description') }}</textarea>
                    </div>
                    <div>
                        <label for="long_description" class="block font-medium text-gray-700">Lange Beschrijving</label>
                        <textarea id="long_description" name="long_description" class="w-full border-gray-300 rounded-md">{{ old('long_description') }}</textarea>
                    </div>
                    <div>
                        <label for="seo_title" class="block font-medium text-gray-700">SEO Titel</label>
                        <input type="text" id="seo_title" name="seo_title" class="w-full border-gray-300 rounded-md"
                            value="{{ old('seo_title') }}">
                    </div>
                    <div>
                        <label for="seo_description" class="block font-medium text-gray-700">SEO Beschrijving</label>
                        <textarea id="seo_description" name="seo_description" class="w-full border-gray-300 rounded-md">{{ old('seo_description') }}</textarea>
                    </div>
                </div>
            </div>
            <!-- Button voor opslaan -->
            <div class="flex justify-end mt-6">
                <button type="submit"
                    class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-400">Opslaan</button>
                <a href="{{ route('products.index') }}"
                    class="btn bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-400"
                    id="cancelButton">Annuleren</a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tagInput = document.getElementById('tags');
            const suggestionsDiv = document.getElementById('tag-suggestions');

            tagInput.addEventListener('input', function() {
                const query = tagInput.value.trim();
                const tags = query.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                const lastTag = tags[tags.length - 1];
                if (lastTag.length < 2) {
                    suggestionsDiv.classList.add('hidden');
                    return;
                }

                fetch(`/tags/search/?q=${lastTag}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(tag => {
                                const tagOption = document.createElement('div');
                                tagOption.textContent = tag.name;
                                tagOption.classList.add('p-2', 'cursor-pointer', 'hover:bg-gray-200');
                                tagOption.addEventListener('click', function() {
                                    const updatedTags = tags.slice(0, -1).join(', ') + (tags.length > 1 ? ', ' : '') + tag.name + ', ';
                                    tagInput.value = updatedTags;
                                    suggestionsDiv.classList.add('hidden');
                                    tagInput.focus();
                                });
                                suggestionsDiv.appendChild(tagOption);
                            });
                            suggestionsDiv.classList.remove('hidden');
                        } else {
                            suggestionsDiv.classList.add('hidden');
                        }
                    });
            });

            document.addEventListener('click', function(event) {
                if (!tagInput.contains(event.target) && !suggestionsDiv.contains(event.target)) {
                    suggestionsDiv.classList.add('hidden');
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            let form = document.getElementById("editProductForm");
            let cancelButton = document.getElementById("cancelButton");
            let initialFormData = new FormData(form);

            cancelButton.addEventListener("click", function(event) {
                let currentFormData = new FormData(form);
                for (let [key, value] of initialFormData.entries()) {
                    if (currentFormData.get(key) !== value) {
                        event.preventDefault();
                        if (confirm("Er zijn wijzigingen aangebracht. Weet je zeker dat je wilt annuleren?")) {
                            window.location.href = cancelButton.href;
                        }
                        break;
                    }
                }
            });
        });
    </script>
@endsection

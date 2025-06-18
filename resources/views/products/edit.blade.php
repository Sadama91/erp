@extends('layouts.app')

@section('page_title', 'Product bewerken: ' . $product->sku . ' - ' . $product->name)

@section('content')
    <div class="container mx-auto p-6">
        <div class="flex space-x-4 mb-4">
            <a href="{{ route('products.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-400">
                &larr; Terug
            </a>
        </div>

        <form id="editProductForm" action="{{ route('products.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Informatie secties met grid layout --}}
            <div
                class="grid gap-y-10 sm:mx-auto sm:w-2/3 md:mx-0 md:w-auto md:grid-cols-1 md:gap-x-5 lg:grid-cols-2 xl:grid-cols-2">

                <!-- Cluster 1: Basisgegevens -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Basisgegevens</h2>
                    <label for="name" class="block font-medium text-gray-700">Naam</label>
                    <input type="text" id="name" name="name" class="w-full border-gray-300 rounded-md"
                        value="{{ old('name', $product->name) }}" required>

                    <label for="sku" class="block font-medium text-gray-700">SKU</label>
                    <input type="text" id="sku" name="sku" class="w-full border-gray-300 rounded-md"
                        value="{{ old('sku', $product->sku) }}" disabled>

                    <label for="brand_id" class="block font-medium text-gray-700">Merk</label>
                    <select id="brand_id" name="brand_id" class="w-full border-gray-300 rounded-md">
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}"
                                {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Groeperingen -->
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h2 class="text-lg font-bold mb-4">Groeperingen</h2>

                    <!-- Subgroep selecteren -->
                    <label for="subgroup_id" class="block font-medium text-gray-700">Subgroep</label>
                    <select id="subgroup_id" name="subgroup_id" class="w-full border-gray-300 rounded-md">
                        @foreach ($subgroups as $subgroup)
                            <option value="{{ $subgroup->id }}"
                                {{ old('subgroup_id', $product->subgroup_id) == $subgroup->id ? 'selected' : '' }}>
                                {{ $subgroup->name }}</option>
                        @endforeach
                    </select>

                    <!-- Categorieën in scrollbaar venster -->
                    <label class="block font-medium text-gray-700 mt-4">Categorieën</label>

@php
    $selectedCategory = old('categories', $product->categories ?? []);

    // Als het een string is (zoals "["1","4"]"), decode deze netjes
    if (is_string($selectedCategory)) {
        $attempts = 0;
        do {
            $selectedCategory = stripslashes($selectedCategory);
            $decoded = json_decode($selectedCategory, true);

            if (is_array($decoded)) {
                $selectedCategory = $decoded;
                break;
            }

            $selectedCategory = trim($selectedCategory, '"');
            $attempts++;
        } while ($attempts < 3);
    }

    if ($selectedCategory instanceof \Illuminate\Support\Collection) {
        $selectedCategory = $selectedCategory->toArray();
    }

    $selectedCategory = is_array($selectedCategory) ? array_map('intval', $selectedCategory) : [];
@endphp


<div class="border rounded-md p-2 max-h-60 overflow-y-auto">
    @foreach ($categories as $category)
        @if (!$category->parent_id)
            <div class="flex items-center">
                <input type="checkbox" id="category_{{ $category->id }}" name="categories[]"
                    value="{{ $category->id }}"
                    @if (in_array($category->id, $selectedCategory)) checked @endif class="mr-2">
                <label for="category_{{ $category->id }}" class="text-gray-700">{{ $category->name }}</label>
            </div>

            @if ($category->children)
                <div class="pl-4">
                    @foreach ($category->children as $child)
                        <div class="flex items-center">
                            <input type="checkbox" id="category_{{ $child->id }}" name="categories[]"
                                value="{{ $child->id }}"
                                @if (in_array($child->id, $selectedCategory)) checked @endif class="mr-2">
                            <label for="category_{{ $child->id }}" class="text-gray-600">{{ $child->name }}</label>
                        </div>

                        @if ($child->children)
                            <div class="pl-4">
                                @foreach ($child->children as $grandchild)
                                    <div class="flex items-center">
                                        <input type="checkbox" id="category_{{ $grandchild->id }}" name="categories[]"
                                            value="{{ $grandchild->id }}"
                                            @if (in_array($grandchild->id, $selectedCategory)) checked @endif class="mr-2">
                                        <label for="category_{{ $grandchild->id }}" class="text-gray-500">{{ $grandchild->name }}</label>
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
                        <input type="text" id="tags_" name="tags" class="w-full border-gray-300 rounded-md p-2"
                            value="{{ implode(',', $productTags) }}" placeholder="Voer tags in..." autocomplete="off">
                        <div id="tag-suggestions"
                            class="border rounded-md mt-1 bg-white shadow-sm max-h-40 overflow-y-auto hidden"></div>
                        <small class="text-gray-500">Bijvoorbeeld: Varken, Kip</small>
                    </div>
                </div>

                    <!-- Cluster : Type en Verkoopkanalen -->
   @php
    // Producttype veilig ophalen
    $selectedProductType = old('product_type_id', $product->product_type_id ?? $product->product_type);

    // Sales channels normaliseren
    $salesChanel = old('sales_chanel', $product->sales_chanel ?? []);
    if (is_string($salesChanel)) {
        $attempts = 0;
        do {
            $salesChanel = stripslashes($salesChanel);
            $decoded = json_decode($salesChanel, true);

            if (is_array($decoded)) {
                $salesChanel = $decoded;
                break;
            }

            $salesChanel = trim($salesChanel, '"');
            $attempts++;
        } while ($attempts < 3);
    }
    if ($salesChanel instanceof \Illuminate\Support\Collection) {
        $salesChanel = $salesChanel->toArray();
    }
    $salesChanel = array_map('strval', $salesChanel); // Zorg voor stringvergelijking

    // Lijsten met bekende waarden
    $productTypeIds = $productTypes->pluck('id')->map(fn($id) => (string)$id)->toArray();
    $salesChanelIds = $salesChanels->pluck('id')->map(fn($id) => (string)$id)->toArray();

    // Haal de geselecteerde BTW–waarde veilig op
    $selectedVatRate = old('vat_rate_id', $product->vat_rate_id ?? null);
    $vatRateIds = $vatRates->pluck('id')->map(fn($id) => (string)$id)->toArray();
@endphp

<!-- Cluster : Type en Verkoopkanalen en BTW percentage -->
<div class="bg-white shadow-sm rounded-lg p-4 w-full">
    <h2 class="text-lg font-bold mb-4">Type, Verkoopkanalen & BTW</h2>

    <!-- Producttype -->
    <div class="mb-4">
        <label for="product_type" class="block font-medium text-gray-700">Producttype</label>
        <select id="product_type" name="product_type" class="w-full border-gray-300 rounded-md">
            @foreach ($productTypes as $productType)
                <option value="{{ $productType->id }}"
                    @if ((string) $selectedProductType === (string) $productType->id) selected @endif>
                    {{ $productType->name }}
                </option>
            @endforeach
            @if (!in_array((string)$selectedProductType, $productTypeIds))
                <em class="bg-red-100 text-red-700 italic">[Verwijderd type #{{ $selectedProductType }}]</em>
            @endif
        </select>
    </div>

    <!-- Verkoopkanalen -->
    <div class="mb-4">
        <label for="sales_chanel[]" class="block font-medium text-gray-700">Verkoopkanalen</label>
        <select id="sales_chanel[]" name="sales_chanel[]" class="w-full border-gray-300 rounded-md" multiple>
            @foreach ($salesChanels as $salesChanelItem)
                <option value="{{ $salesChanelItem->id }}"
                    @if (in_array((string)$salesChanelItem->id, $salesChanel)) selected @endif>
                    {{ $salesChanelItem->name }}
                </option>
            @endforeach
            {{-- Verwijderde kanalen tonen --}}
            @foreach ($salesChanel as $id)
                @if (!in_array((string)$id, $salesChanelIds))
                    <em class="bg-red-100 text-red-700 italic">[Verwijderd kanaal #{{ $id }}]</em>
                @endif
            @endforeach
        </select>
    </div>

    <!-- BTW percentage -->


                        <input type="checkbox" id="available_for_web" name="available_for_web"
                            class="border-gray-300 rounded-md" checked><label for="available_for_web"
                            class="font-medium px-2 text-gray-700">Beschikbaar voor web</label>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <h2 class="text-lg font-bold mb-4">Inkoop & prijzen</h2>
                        @php
    $selectedSupplierId = old('supplier_id', $product->supplier_id);
    $supplierIds = $suppliers->pluck('id')->map(fn($id) => (string) $id)->toArray();
@endphp

<label for="supplier_id" class="block font-medium text-gray-700">Leverancier</label>
<select name="supplier_id" id="supplier_id" class="border rounded-lg w-full p-2" required>
    @foreach ($suppliers as $supplier)
        <option value="{{ $supplier->id }}"
            @if ((string)$selectedSupplierId === (string)$supplier->id) selected @endif>
            {{ $supplier->name }}
        </option>
    @endforeach
</select>
                            @if(!in_array($selectedSupplierId, $supplierIds))
        <em class="bg-red-100 text-red-700 italic">
            [Verwijderde/onbekende leverancier #{{ $selectedSupplierId }}]
</em>
@endif
 

                        <div>
                            <label for="purchase_quantity" class="block font-medium text-gray-700">Inkoopeenheid (per
                                hoeveel koop je in)</label>
                            <input type="number" step="0.01" id="purchase_quantity" name="purchase_quantity"
                                class="w-full border-gray-300 rounded-md"
                                value="{{ old('purchase_quantity', $product->purchase_quantity) }}" required>
                        </div>
                        <div>
                            <label for="sale_quantity" class="block font-medium text-gray-700">Verkoopeenheid (per hoeveel
                                verkoop je)</label>
                            <input type="number" step="0.01" id="sale_quantity" name="sale_quantity"
                                class="w-full border-gray-300 rounded-md"
                                value="{{ old('sale_quantity', $product->sale_quantity) }}" required>
                        </div>

    <div>
        <label for="vat_rate_id" class="block font-medium text-gray-700">BTW percentage</label>
        <select id="vat_rate_id" name="vat_rate_id" class="w-full border-gray-300 rounded-md">
            @foreach ($vatRates as $vatRate)
                <option value="{{ $vatRate->id }}" {{ ((string)$selectedVatRate === (string)$vatRate->id) ? 'selected' : '' }}>
                    {{ $vatRate->name }}
                </option>
            @endforeach
            @if (!in_array((string)$selectedVatRate, $vatRateIds))
                <em class="bg-red-100 text-red-700 italic">[Verwijderd BTW percentage #{{ $selectedVatRate }}]</em>
            @endif
        </select>
    </div>

                        <div>
                            <h3>Verkoopprijzen</h3>
                            <div>
                                <label for="regular_price" class="block font-medium text-gray-700">Reguliere Prijs</label>
                                <input type="number" step="0.01" id="regular_price" name="regular_price"
                                    class="w-full border-gray-300 rounded-md"
                                    value="{{ old('regular_price', $regularPrice) }}" required>
                            </div>
                            <div>
                                <label for="vinted_price" class="block font-medium text-gray-700">Vinted Prijs</label>
                                <input type="number" step="0.01" id="vinted_price" name="vinted_price"
                                    class="w-full border-gray-300 rounded-md"
                                    value="{{ old('vinted_price', $vintedPrice) }}">
                            </div>
                        </div>
                        
                    </div>

                    <!-- Cluster 3: Dimensies en Verzendklasse -->
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <h2 class="text-lg font-bold mb-4">Dimensies & Verzendklasse</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="height" class="block text-gray-700">Hoogte (cm)</label>
                                <input type="number" name="height" id="height" class="border rounded-lg w-full p-2"
                                    step="0.01" value="{{ old('height', $product->height) }}">
                            </div>
                            <div class="mb-4">
                                <label for="width" class="block text-gray-700">Breedte (cm)</label>
                                <input type="number" name="width" id="width" class="border rounded-lg w-full p-2"
                                    step="0.01" value="{{ old('width', $product->width) }}">
                            </div>
                            <div class="mb-4">
                                <label for="depth" class="block text-gray-700">Diepte (cm)</label>
                                <input type="number" name="depth" id="depth" class="border rounded-lg w-full p-2"
                                    step="0.01" value="{{ old('depth', $product->depth) }}">
                            </div>
                            <div class="mb-4">
                                <label for="weight" class="block text-gray-700">Gewicht (gr)</label>
                                <input type="number" name="weight" id="weight" class="border rounded-lg w-full p-2"
                                    step="1" value="{{ old('weight', $product->weight) }}">
                            </div>
                        </div>@php
    $selectedShippingClass = old('shipping_class', $product->shipping_class);
    $shippingClassIds = $shippingClasses->pluck('id')->map(fn($id) => (string) $id)->toArray();
@endphp

<div class="col-span-2 mb-4">
    <label for="shipping_class" class="block font-medium text-gray-700">Verzendklasse</label>
    <select id="shipping_class" name="shipping_class" class="w-full border-gray-300 rounded-md">
        @foreach ($shippingClasses as $shippingClass)
            <option value="{{ $shippingClass->id }}"
                @if ((string)$selectedShippingClass === (string)$shippingClass->id) selected @endif>
                {{ $shippingClass->name }}
            </option>
        @endforeach
        </select>

        @if (!in_array((string)$selectedShippingClass, $shippingClassIds) && $selectedShippingClass)
            <em class="bg-red-100 text-red-700 italic">
                [Verwijderde/verwijderde verzendklasse #{{ $selectedShippingClass }}]
</em>
        @endif
</div>

                    </div>

                    <!-- Cluster 4: Webinformatie -->
                    <div class="bg-white shadow-sm rounded-lg p-4 w-full">
                        <h2 class="text-lg font-bold mb-4">Webinformatie</h2>
                        <div>
                            <label for="short_description" class="block font-medium text-gray-700">Korte
                                Beschrijving</label>
                            <textarea id="short_description" name="short_description" class="w-full border-gray-300 rounded-md">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>
                        <div>
                            <label for="long_description" class="block font-medium text-gray-700">Lange
                                Beschrijving</label>
                            <textarea id="long_description" name="long_description" class="w-full border-gray-300 rounded-md">{{ old('long_description', $product->long_description) }}</textarea>
                        </div>
                        <div>
                            <label for="seo_title" class="block font-medium text-gray-700">SEO Titel</label>
                            <input type="text" id="seo_title" name="seo_title"
                                class="w-full border-gray-300 rounded-md"
                                value="{{ old('seo_title', $product->seo_title) }}">
                        </div>
                        <div>
                            <label for="seo_description" class="block font-medium text-gray-700">SEO Beschrijving</label>
                            <textarea id="seo_description" name="seo_description" class="w-full border-gray-300 rounded-md">{{ old('seo_description', $product->seo_description) }}</textarea>
                        </div>
                    </div>

                </div><!-- Button voor opslaan -->
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
                // Splits de tags op basis van komma's en verwijder lege waarden
                const tags = query.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
                // Pak de laatste tag voor de zoekopdracht
                const lastTag = tags[tags.length - 1];
                console.log(query, tags, lastTag);
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
                                tagOption.classList.add('p-2', 'cursor-pointer',
                                    'hover:bg-gray-200');
                                tagOption.addEventListener('click', function() {

                                    const updatedTags = tags.slice(0, -1).join(', ') + (
                                            tags.length > 1 ? ', ' : '') + tag.name +
                                        ', ';
                                    tagInput.value = updatedTags;
                                    suggestionsDiv.classList.add('hidden');
                                    tagInput
                                .focus(); // Zet de focus terug op het invoerveld
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
                        if (confirm(
                            "Er zijn wijzigingen aangebracht. Weet je zeker dat je wilt annuleren?")) {
                            window.location.href = cancelButton.href;
                        }
                        break;
                    }
                }
            });
        });
    </script>
@endsection

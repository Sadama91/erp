@extends('layouts.app')

@section('content')
<div class="overflow-x-auto">
    <form id="productForm" method="POST" action="{{ route('products.bulkUpdate') }}">
        @csrf
        @method('PUT')
        <table class="table-fixed w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border" style="width: 40px;">Negeer</th>
                    <th class="border" style="width: 80px;">SKU</th>
                    <th class="border" style="width: 250px;">Algemeen</th>
                    <th class="border" style="width: 200px;">Categorieën</th>
                    <th class="border" style="width: 300px;">Verkoop & prijs</th>
                    <th class="border" style="width: 250px;">Logistiek</th>
                    <th class="border" style="width: 400px;">Beschrijvingen</th>
                    <th class="border" style="width: 350px;">SEO</th>
                </tr>
            </thead>
            <?php // dd($productsData, $salesChanels); ?>
            <tbody>
                @foreach($productsData as $index => $data)
                    @php
                        $product = $data['product'];
                        $old = old('products.'.$index, []);
                        $val = fn($key, $default) => data_get($old, $key, $default);
                        // categories
                        $selCats = $val('categories', $data['selectedCategory'] ?? []);
                        if(is_string($selCats)) { $selCats = json_decode($selCats, true) ?? []; }
                        // channels
                        $selCh = $val('sales_chanel', $data['product']->sales_chanel ?? []);
                        if(is_string($selCh)) { $selCh = json_decode($selCh, true) ?? []; }
                    @endphp
                    <tr class="odd:bg-white even:bg-gray-50">
                        {{-- Negeer --}}
                        <td class="border border-gray-300 p-1 text-center">
                            <input type="hidden" name="products[{{ $index }}][ignore]" value="0">
                            <input type="checkbox" name="products[{{ $index }}][ignore]" value="1" {{ $val('ignore', 0) ? 'checked' : '' }}>
                        </td>

                        {{-- SKU --}}
                        <td class="border border-gray-300 p-1">
                            {{ $product->sku }}
                            <input type="hidden" name="products[{{ $index }}][sku]" value="{{ $product->sku }}">
                        </td>

                        {{-- Algemeen --}}
                        <td class="border border-gray-300 p-1 space-y-2">
                            {{-- Merk --}}
                            <label class="block font-medium text-gray-700">Merk</label>
                            <select name="products[{{ $index }}][brand_id]" class="w-full border-gray-300 required-field" required>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}" {{ $b->id == $val('brand_id', $product->brand_id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                                @if(!in_array($val('brand_id', $product->brand_id), $brands->pluck('id')->toArray()))
                                    <option value="{{ $val('brand_id', $product->brand_id) }}" selected class="bg-red-100 text-red-700 italic">[Onbekend merk #{{ $val('brand_id', $product->brand_id) }}]</option>
                                @endif
                            </select>

                            {{-- Producttitel --}}
                            <label class="block font-medium text-gray-700">Producttitel</label>
                            <input type="text" name="products[{{ $index }}][name]" class="w-full border-gray-300 required-field" placeholder="Naam" required
                                value="{{ $val('name', $product->name) }}">

                            {{-- Type product --}}
                            <label class="block font-medium text-gray-700">Type product</label>
                            <select name="products[{{ $index }}][product_type]" class="w-full border-gray-300 required-field" required>
                                @foreach($productTypes as $t)
                                    <option value="{{ $t->id }}" {{ $t->id == $val('product_type', $product->product_type_id ?? '') ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                                @if(!in_array($val('product_type', $product->product_type_id ?? ''), $productTypes->pluck('id')->toArray()))
                                    <option value="{{ $val('product_type', $product->product_type_id ?? '') }}" selected class="bg-red-100 italic">[Onbekend type #{{ $val('product_type', $product->product_type_id ?? '') }}]</option>
                                @endif
                            </select>

                            {{-- Subgroep --}}
                            <label class="block font-medium text-gray-700">Subgroep</label>
                            <select name="products[{{ $index }}][subgroup_id]" class="w-full border-gray-300 required-field">
                                @foreach($subgroups as $sg)
                                    <option value="{{ $sg->id }}" {{ $sg->id == $val('subgroup_id', $product->subgroup_id) ? 'selected' : '' }}>{{ $sg->name }}</option>
                                @endforeach
                                @if(!in_array($val('subgroup_id', $product->subgroup_id), $subgroups->pluck('id')->toArray()))
                                    <option value="{{ $val('subgroup_id', $product->subgroup_id) }}" selected class="bg-red-100 italic">[Onbekende subgroep #{{ $val('subgroup_id', $product->subgroup_id) }}]</option>
                                @endif
                            </select>

                            {{-- Locatie --}}
                            <label class="block text-sm font-medium text-gray-700">Locatie</label>
                            <select name="products[{{ $index }}][location]" class="w-full form-control rounded-md border-gray-300 p-2">
                                <option value="">Geen</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->value }}" {{ $loc->value == $val('location', $product->location) ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Categorieën --}}
                        <td class="border border-gray-300 p-1">
                            {{-- Tags --}}
                            <label class="block font-medium text-gray-700">Tags</label>
                            <input type="text" id="tags_{{ $index }}" name="products[{{ $index }}][tags]"
                                class="w-full border-gray-300 rounded-md p-2 required-field" placeholder="Voer tags in..." autocomplete="off" required
                                value="{{ $val('tags', implode(',', $data['productTags'])) }}">
                            <div id="tag-suggestions_{{ $index }}" class="border rounded-md mt-1 bg-white shadow-sm max-h-40 overflow-y-auto hidden"></div>

                            {{-- Categories tree --}}
                            <div class="mt-2 border rounded-md p-2 max-h-60 overflow-y-auto required-field">
                                @foreach($categories as $cat)
                                    @if(!$cat->parent_id)
                                        <div class="flex items-center">
                                            <input type="checkbox" name="products[{{ $index }}][categories][]" value="{{ $cat->id }}" class="mr-2 category-checkbox"
                                                {{ in_array($cat->id, (array)$selCats) ? 'checked' : '' }}>
                                            <label>{{ $cat->name }}</label>
                                        </div>
                                        @if($cat->children)
                                            <div class="pl-4">
                                                @foreach($cat->children as $child)
                                                    <div class="flex items-center">
                                                        <input type="checkbox" name="products[{{ $index }}][categories][]" value="{{ $child->id }}" class="mr-2 category-checkbox"
                                                            {{ in_array($child->id, (array)$selCats) ? 'checked' : '' }}>
                                                        <label class="text-gray-600">{{ $child->name }}</label>
                                                    </div>
                                                    @if($child->children)
                                                        <div class="pl-4">
                                                            @foreach($child->children as $gc)
                                                                <div class="flex items-center">
                                                                    <input type="checkbox" name="products[{{ $index }}][categories][]" value="{{ $gc->id }}" class="mr-2 category-checkbox"
                                                                        {{ in_array($gc->id, (array)$selCats) ? 'checked' : '' }}>
                                                                    <label class="text-gray-500">{{ $gc->name }}</label>
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
                        </td>

                        {{-- Verkoop & Prijs --}}
                        <td class="border border-gray-300 p-1 space-y-2">
                            {{-- Verkoopkanalen --}}
                            <label class="block font-medium text-gray-700">Verkoopkanalen</label>
                            <select name="products[{{ $index }}][sales_chanel][]" multiple size="3" class="w-full p-1 border-gray-300 required-field sales-channel"
                                required>
                                @foreach($salesChanels as $ch)
                                    <option value="{{ $ch->id }}" {{ in_array((string)$ch->id, array_map('strval', (array)$selCh)) ? 'selected' : '' }}>{{ $ch->name }}</option>
                                @endforeach
                            </select>
                            @foreach((array)$selCh as $chId)
                                @if(!in_array((string)$chId, $salesChanels->pluck('id')->map(fn($i)=>(string)$i)->toArray()))
                                    <div class="text-red-700 italic">[Onbekend kanaal #{{ $chId }}]</div>
                                @endif
                            @endforeach

                            {{-- Beschikbaar op web --}}
                            <input type="hidden" name="products[{{ $index }}][available_for_web]" value="0">
                            <label class="inline-flex items-center mt-1">
                                <input type="checkbox" name="products[{{ $index }}][available_for_web]" value="1" {{ $val('available_for_web', $product->available_for_web) ? 'checked' : '' }}>
                                <span class="ml-2">Website</span>
                            </label>

                            {{-- BTW percentage --}}
                            <label class="block font-medium text-gray-700">BTW percentage</label>
                            <select name="products[{{ $index }}][vat_rate_id]" class="w-full p-1 border-gray-300 required-field" size="1" required>
                                @foreach($vatRates as $vat)
                                    <option value="{{ $vat->id }}" {{ $vat->id == $val('vat_rate_id', $product->vat_rate_id) ? 'selected' : '' }}>{{ $vat->name }}</option>
                                @endforeach
                            </select>

                            {{-- Eenheden --}}
                            <div class="flex space-x-2">
                                <div class="flex-1">
                                    <label class="block font-medium text-gray-700">Eenheid in</label>
                                    <input type="number" name="products[{{ $index }}][purchase_quantity]" class="w-full border-gray-300 required-field" min="1" required
                                        value="{{ $val('purchase_quantity', $product->purchase_quantity) }}">
                                </div>
                                <div class="flex-1">
                                    <label class="block font-medium text-gray-700">Eenheid verkoop</label>
                                    <input type="number" name="products[{{ $index }}][sale_quantity]" class="w-full border-gray-300 required-field" min="1" required
                                        value="{{ $val('sale_quantity', $product->sale_quantity) }}">
                                </div>
                            </div>

                            {{-- Prijzen --}}
                            <div class="flex space-x-2">
                                <div class="flex-1">
                                    <label class="block font-medium text-gray-700">Reguliere prijs (€)</label>
                                    <input type="text" name="products[{{ $index }}][regular_price]" class="w-full border-gray-300 required-field price-field" pattern="^\d+(\.\d{1,2})?$" required
                                        value="{{ $val('regular_price', $data['regularPrice']) }}">
                                </div>
                                <div class="flex-1">
                                    <label class="block font-medium text-gray-700">Vinted prijs (€)</label>
                                    <input type="text" name="products[{{ $index }}][vinted_price]" class="w-full border-gray-300 price-field" pattern="^\d+(\.\d{1,2})?$"
                                        value="{{ $val('vinted_price', $data['vintedPrice']) }}">
                                </div>
                            </div>
                        </td>

                        {{-- Logistiek --}}
                        <td class="border border-gray-300 p-1 space-y-2">
                            {{-- Leverancier --}}
                            <label class="block font-medium text-gray-700">Leverancier</label>
                            <select name="products[{{ $index }}][supplier_id]" class="w-full border-gray-300 required-field" required>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ $sup->id == $val('supplier_id', $product->supplier_id) ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                            @if(!in_array($val('supplier_id', $product->supplier_id), $suppliers->pluck('id')->toArray()))
                                <div class="text-red-700 italic">[Onbekende leverancier #{{ $val('supplier_id', $product->supplier_id) }}]</div>
                            @endif

                            {{-- Verzendklasse --}}
                            <label class="block font-medium text-gray-700">Verzendklasse</label>
                            <select name="products[{{ $index }}][shipping_class]" class="w-full border-gray-300">
                                @foreach($shippingClasses as $cls)
                                    <option value="{{ $cls->id }}" {{ $cls->id == $val('shipping_class', $product->shipping_class) ? 'selected' : '' }}>{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            @if(!in_array($val('shipping_class', $product->shipping_class), $shippingClasses->pluck('id')->toArray()))
                                <div class="text-red-700 italic">[Onbekende verzendklasse #{{ $val('shipping_class', $product->shipping_class) }}]</div>
                            @endif

                            {{-- Afmetingen --}}
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="products[{{ $index }}][depth]" class="border-gray-300 dimension-field" placeholder="Lengte (cm)" value="{{ $val('depth', $product->depth) }}">
                                <input type="text" name="products[{{ $index }}][width]" class="border-gray-300 dimension-field" placeholder="Breedte (cm)" value="{{ $val('width', $product->width) }}">
                                <input type="text" name="products[{{ $index }}][height]" class="border-gray-300 dimension-field" placeholder="Hoogte (cm)" value="{{ $val('height', $product->height) }}">
                                <input type="text" name="products[{{ $index }}][weight]" class="border-gray-300 dimension-field" placeholder="Gewicht (g)" value="{{ $val('weight', $product->weight) }}">
                            </div>
                        </td>

                        {{-- Beschrijvingen --}}
                        <td class="border border-gray-300 p-1 space-y-2">
                            <label class="block font-medium text-gray-700">Korte omschrijving</label>
                            <textarea name="products[{{ $index }}][short_description]" class="w-full border-gray-300" rows="2">{{ $val('short_description', $product->short_description) }}</textarea>
                            <label class="block font-medium text-gray-700">Lange omschrijving</label>
                            <textarea name="products[{{ $index }}][long_description]" class="w-full border-gray-300" rows="4">{{ $val('long_description', $product->long_description) }}</textarea>
                        </td>

                        {{-- SEO --}}
                        <td class="border border-gray-300 p-1 space-y-2">
                            <label class="block font-medium text-gray-700">SEO Titel</label>
                            <input type="text" name="products[{{ $index }}][seo_title]" class="w-full border-gray-300 seo-title" maxlength="60"
                                value="{{ $val('seo_title', $product->seo_title) }}">
                            <label class="block font-medium text-gray-700">Focus keyword</label>
                            <input type="text" name="products[{{ $index }}][focus_keyword]" class="w-full border-gray-300"
                                value="{{ $val('focus_keyword', $product->focus_keyword) }}">
                            <label class="block font-medium text-gray-700">SEO omschrijving</label>
                            <textarea name="products[{{ $index }}][seo_description]" class="w-full border-gray-300 seo-description" rows="2" maxlength="160">{{ $val('seo_description', $product->seo_description) }}</textarea>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Opslaan</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')


<script>
    document.getElementById('productForm').addEventListener('submit', function(event) {
        let isValid = true;

        // Controleer verplichte velden
        document.querySelectorAll('.required-field').forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('border-red-500', 'bg-red-100');
            } else {
                input.classList.remove('border-red-500', 'bg-red-100');
            }
        });
       // Controleer verkoopkanalen
       document.querySelectorAll('.sales-channel').forEach(select => {
            if (select.selectedOptions.length === 0) {
                isValid = false;
                select.classList.add('border-red-500', 'bg-red-100');
            } else {
                select.classList.remove('border-red-500', 'bg-red-100');
            }
        });
 // Controleer of ten minste één categorie is geselecteerd per product
 document.querySelectorAll('.border.rounded-md.p-2.max-h-60').forEach(categoryDiv => {
            const checkboxes = categoryDiv.querySelectorAll('.category-checkbox');
            const checked = Array.from(checkboxes).some(checkbox => checkbox.checked);

            if (!checked) {
                isValid = false;
                categoryDiv.classList.add('border-red-500', 'bg-red-100');
            } else {
                categoryDiv.classList.remove('border-red-500', 'bg-red-100');
            }
        });
        // Controleer of prijzen correcte getallen zijn
        document.querySelectorAll('.price-field').forEach(input => {
            if (input.value && !/^\d+(\.\d{1,2})?$/.test(input.value)) {
                isValid = false;
                input.classList.add('border-red-500', 'bg-red-100');
            } else {
                input.classList.remove('border-red-500', 'bg-red-100');
            }
        });

        if (!isValid) {
            event.preventDefault();
            alert('Vul alle verplichte velden correct in.');
        }
        // Controleer dimensievelden (moeten numeriek zijn)
        document.querySelectorAll('.dimension-field').forEach(input => {
            if (input.value && isNaN(parseFloat(input.value))) {
                isValid = false;
                input.classList.add('border-red-500', 'bg-red-100');
            } else {
                input.classList.remove('border-red-500', 'bg-red-100');
            }
        });

        // Controleer SEO-titel lengte (max 60 tekens)
        document.querySelectorAll('.seo-title').forEach(input => {
            if (input.value.length > 60) {
                isValid = false;
                input.classList.add('border-red-500', 'bg-red-100');
            } else {
                input.classList.remove('border-red-500', 'bg-red-100');
            }
        });

        // Controleer SEO-omschrijving lengte (max 160 tekens)
        document.querySelectorAll('.seo-description').forEach(input => {
            if (input.value.length > 160) {
                isValid = false;
                input.classList.add('border-red-500', 'bg-red-100');
            } else {
                input.classList.remove('border-red-500', 'bg-red-100');
            }
        });

        if (!isValid) {
            event.preventDefault();
            alert('Vul alle verplichte velden correct in. Controleer ook de dimensies en SEO-velden.');
        }
    });
</script>
@endsection

@section('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="tags_"]').forEach(tagInput => {
        const index = tagInput.id.split('_')[1];
        const suggestionsDiv = document.getElementById(`tag-suggestions_${index}`);

        tagInput.addEventListener('input', function () {
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
                            tagOption.addEventListener('click', function () {
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

        document.addEventListener('click', function (event) {
            if (!tagInput.contains(event.target) && !suggestionsDiv.contains(event.target)) {
                suggestionsDiv.classList.add('hidden');
            }
        });
    });
});
</script>
@endsection
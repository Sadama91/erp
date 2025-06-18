{{-- resources/views/products/partials/form-fields.blade.php --}}
<div>
    {{-- Categorie, Merk, Subgroep en Tags --}}
    @include('products.partials.categories-brands')

    {{-- Prijs, Verzendklasse en Voorraad --}}
    @include('products.partials.pricing-stock')

    {{-- Afmetingen --}}
    @include('products.partials.dimensions')

    {{-- Website --}}
    <h2 class="text-xl font-semibold mb-2">Website</h2>
    <div class="mb-4">
        <label for="to_website" class="block text-gray-700">Naar web (J/N)</label>
        <select name="to_website" id="to_website" class="border rounded-lg w-full p-2" required>
            <option value="Y" {{ old('to_website', $product->to_website ?? '') == 'Y' ? 'selected' : '' }}>Ja</option>
            <option value="N" {{ old('to_website', $product->to_website ?? '') == 'N' ? 'selected' : '' }}>Nee</option>
        </select>
    </div>
</div>

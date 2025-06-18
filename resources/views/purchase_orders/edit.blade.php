@extends('layouts.app')

@section('page_title', 'Inkooporder bewerken: #'.$purchaseOrder->id)

@section('content')
<div class="container mx-auto">
    <form action="{{ route('purchases.update', $purchaseOrder->id) }}" method="POST" id="purchaseOrderForm" onsubmit="return prepareForm()">
        @csrf
        @method('PUT')
        <input type="hidden" name="status" value="{{ $purchaseOrder->status }}">
        <!-- Dit veld bevat later de JSON-string met alle producten -->
        <input type="hidden" name="products" id="products">

        <!-- Bovenste sectie met datum, leverancier, titel en opmerkingen -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Linker kolom -->
            <div>
                <div class="mb-4">
                    <label for="order_date" class="block text-sm font-medium text-gray-700">Datum</label>
                    <input type="date" name="date" id="date" value="{{ old('date', $purchaseOrder->date) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" {{ $editable ? '' : 'readonly' }} required>
                </div>

                <div class="mb-4">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Leverancier</label>
                    <select name="supplier_id" id="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    Status: {{ $statuses[$purchaseOrder->status] ?? 'onbekende fout' }}
                </div>
            </div>

            <!-- Rechter kolom -->
            <div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Opmerkingen</label>
                    <textarea name="notes" id="notes" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" {{ $editable || $purchaseOrder->status >= 2 ? '' : 'disabled' }}>{{ old('remarks', $purchaseOrder->notes) }}</textarea>
                </div>
            </div>
        </div>

        @if($purchaseOrder->status < 3)
        <!-- Productzoekfunctie -->
        <div class="mb-4">
            <label for="product_search" class="block text-sm font-medium text-gray-700">Zoek Product</label>
            <input type="text" id="product_search" placeholder="Zoek op productnaam of SKU" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" oninput="searchProducts()">
            <ul id="product_list" class="mt-2 border border-gray-300 rounded-md max-h-60 overflow-y-auto hidden"></ul>
        </div>
        @endif
        <!-- Tabel met geselecteerde producten (alleen bewerkbaar) -->
        @if ($editable)
        <h3 class="font-semibold mb-2">Producten</h3>
        <table class="min-w-full mb-4">
            <thead>
                <tr>
                    <th>Aantal</th>
                    <th>SKU</th>
                    <th>Artikel</th>
                    <th>Verkoop</th>
                    <th>Inkoopprijs (incl. BTW)</th>
                    <th>Inkoopprijs (excl. BTW)</th>
                    <th>Totale Inkoop</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <!-- Deze tbody wordt via JavaScript gevuld -->
            <tbody id="selected_products"></tbody>
        </table>
        @else
            <!-- Indien niet bewerkbaar: toon de statische weergave -->
            <p class="text-sm text-gray-600">Producten kunnen niet worden aangepast in deze status.</p>
            <h3 class="font-semibold mb-2">Producten</h3>
            <table class="min-w-full mb-4">
                <thead>
                    <tr>
                        <th>Aantal</th>
                        <th>SKU</th>
                        <th>Artikel</th>
                        <th>Verkoop</th>
                        <th>Inkoopprijs (incl. BTW)</th>
                        <th>Inkoopprijs (excl. BTW)</th>
                        <th>Totale Inkoop</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseOrder->purchaseOrderItems as $item)
                    <tr>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->sku }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td>€{{ number_format($item->price->price, 2) }}</td>
                        <td>€{{ number_format($item->price_incl_unit, 2) }}</td>
                        <td>€{{ number_format($item->price_incl_unit / 1.21, 2) }}</td>
                        <td>€{{ number_format($item->price_incl_unit * $item->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Opslaan en Annuleren knoppen -->
        <div>
            <a href="{{ route('purchases.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded">Annuleren</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">Opslaan</button>
        </div>
    </form>
</div>

@php
    // Bereid de bestaande producten voor zodat ze in de JS-variabele kunnen worden geladen
    $existingProducts = $purchaseOrder->purchaseOrderItems->map(function($item) {
         return [
             'id' => $item->product_id,
             'sku' => $item->sku,
             'name' => $item->product->name,
             'priceIncl' => $item->price_incl_bulk,
             'sell_price' => $item->price->price ?? 0,
             'purchaseQuantity' => $item->product->purchase_quantity,
             'sellQuantity' => $item->product->sale_quantity,
             'quantity' => $item->quantity / max(1, ($item->product->purchase_quantity / $item->product->sale_quantity)),
         ];
    });
@endphp

<script>
    // Zorg dat de benodigde DOM-elementen beschikbaar zijn
    const searchResults = document.getElementById('product_list');
    const productTable = document.getElementById('selected_products');
    // Laad de bestaande producten in de selectedProducts-array
    const selectedProducts = @json($existingProducts);

    // Functie om de producten in de tabel weer te geven
    function updateSelectedProducts() {
        productTable.innerHTML = '';
        selectedProducts.forEach((product, index) => {
            const totalIncl = (product.priceIncl * product.quantity).toFixed(2);
            const sellFactor = (product.purchaseQuantity / product.sellQuantity);
            const totalSellPrice = (sellFactor * (product.sell_price * product.quantity)).toFixed(2);
            const priceExcl = (product.priceIncl / 1.21).toFixed(2);

            // Update de totale verkoopprijs in het product-object
            product.totalSellPrice = totalSellPrice;

            productTable.innerHTML += `
                <tr>
                    <td>
                        <input type="number" value="${product.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                    </td>
                    <td>${product.sku}</td>
                    <td>${product.name}</td>
                    <td>€ ${product.sell_price}</td>
                    <td>
                        <input type="number" step="any" value="${product.priceIncl}" onchange="updatePrice(${index}, this.value)" class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                    </td>
                    <td>
                        <input type="number" step="any" value="${priceExcl}" onchange="updatePriceExcl(${index}, this.value)" class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
                    </td>
                    <td>€ ${totalIncl}</td>
                    <td>€ ${totalSellPrice}</td>
                    <td>
                        <button type="button" onclick="removeProduct(${index})" class="bg-red-500 text-white px-2 py-1 rounded-md">Verwijderen</button>
                    </td>
                </tr>
            `;
        });
        // Update het verborgen veld met de JSON-string
        document.getElementById('products').value = JSON.stringify(selectedProducts);
    }

    // Functie om de totale waarden bij te werken (als je extra totalen wilt tonen)
    function updateTotals() {
        // Voorbeeld: als je totalen in de view hebt staan (zorg dat je elementen met id "total_items", etc. hebt)
        // Hier kun je de totalen berekenen en in de DOM plaatsen.
    }

    // Functie om de hoeveelheid van een product bij te werken
    function updateQuantity(index, quantity) {
        selectedProducts[index].quantity = parseInt(quantity);
        updateSelectedProducts();
        updateTotals();
    }

    // Functie om de prijs (inclusief) van een product bij te werken
    function updatePrice(index, newPrice) {
        selectedProducts[index].priceIncl = parseFloat(newPrice);
        updateSelectedProducts();
        updateTotals();
    }

    // Functie om de prijs (exclusief) van een product bij te werken (op basis van de exclusieve invoer)
    function updatePriceExcl(index, newPriceExcl) {
        const newPriceIncl = parseFloat(newPriceExcl) * 1.21;
        selectedProducts[index].priceIncl = parseFloat(newPriceIncl.toFixed(2));
        updateSelectedProducts();
        updateTotals();
    }

    // Functie om een product te verwijderen
    function removeProduct(index) {
        selectedProducts.splice(index, 1);
        updateSelectedProducts();
        updateTotals();
    }

    // Functie om de prijzen te berekenen (wanneer in de Blade-inputs wijzigingen optreden)
    function calculatePrice(itemId) {
        const priceInclRaw = document.querySelector(`[name="products[${itemId}]\\[price_incl_bulk\\]"]`).value;
        const priceExclRaw = document.querySelector(`[name="products[${itemId}]\\[price_excl_bulk\\]"]`).value;

        const priceIncl = parseFloat(priceInclRaw.replace(',', '.'));
        const priceExcl = parseFloat(priceExclRaw.replace(',', '.'));

        if (!isNaN(priceIncl) && priceIncl > 0) {
            const calculatedExcl = (priceIncl / 1.21).toFixed(2);
            document.querySelector(`[name="products[${itemId}]\\[price_excl_bulk\\]"]`).value = calculatedExcl;
        }
        if (!isNaN(priceExcl) && priceExcl > 0) {
            const calculatedIncl = (priceExcl * 1.21).toFixed(2);
            document.querySelector(`[name="products[${itemId}]\\[price_incl_bulk\\]"]`).value = calculatedIncl;
        }

        const priceFieldIncl = document.querySelector(`[name="products[${itemId}]\\[price_incl_bulk\\]"]`);
        const priceFieldExcl = document.querySelector(`[name="products[${itemId}]\\[price_excl_bulk\\]"]`);

        if (priceIncl <= 0) {
            priceFieldIncl.classList.add('border-red-500');
        } else {
            priceFieldIncl.classList.remove('border-red-500');
        }
        if (priceExcl <= 0) {
            priceFieldExcl.classList.add('border-red-500');
        } else {
            priceFieldExcl.classList.remove('border-red-500');
        }
    }

    // Functie om producten te zoeken
    async function searchProducts() {
        const query = document.getElementById('product_search').value;
        if (!query) {
            searchResults.classList.add('hidden');
            return;
        }
        try {
            const response = await fetch(`{{ route('products.search') }}?query=${query}&inStock=all`);
            const products = await response.json();
            const filteredProducts = products.map(product => {
                return {
                    id: product.id,
                    name: product.name,
                    subgroupName: product.subgroup_name,
                    sku: product.sku,
                    brandName: product.brand_name,
                    sell_price: product.sell_price,
                    purchaseQuantity: product.purchase_quantity,
                    sellQuantity: product.sale_quantity,
                    lastPurchasePrice: parseFloat(product.last_purchase_price)
                };
            });
            displayProducts(filteredProducts);
        } catch (error) {
            console.error("Error fetching products:", error);
        }
    }

    // Functie om zoekresultaten weer te geven
    function displayProducts(products) {
        const productContainer = document.getElementById('product_list');
        productContainer.innerHTML = '';
        productContainer.classList.remove('hidden');
        products.forEach(product => {
            const productItem = document.createElement('li');
            productItem.classList.add('product-item', 'p-2', 'border-b', 'border-gray-300');
            productItem.innerHTML = `
                <div>
                    <span class="font-bold">${product.sku}</span> - 
                    <span>${product.subgroupName}</span> - 
                    <span>${product.brandName}</span> - 
                    <span>${product.name}</span>
                </div>
            `;
            productItem.addEventListener('click', () => {
                selectProduct(product.id, product.name, product.sku, product.lastPurchasePrice, product.sell_price, product.purchaseQuantity, product.sellQuantity);
            });
            productContainer.appendChild(productItem);
        });
    }

    // Functie om een product te selecteren
    function selectProduct(id, name, sku, priceIncl, sell_price, purchaseQuantity, sellQuantity) {
        const existingProductIndex = selectedProducts.findIndex(p => p.id === id);
        if (existingProductIndex !== -1) {
            alert('Dit product is al geselecteerd.');
            return;
        }
        selectedProducts.push({ id, name, sku, priceIncl, sell_price, purchaseQuantity, sellQuantity, quantity: 1 });
        updateSelectedProducts();
        updateTotals();
        document.getElementById('product_search').value = '';
        document.getElementById('product_list').classList.add('hidden');
    }

    // Voorbereiden van het formulier voor verzending
    function prepareForm() {
        for (let product of selectedProducts) {
            if (product.priceIncl <= 0) {
                alert('De inkoopprijs moet groter zijn dan 0.');
                return false;
            }
        }
        document.getElementById('products').value = JSON.stringify(selectedProducts);
        return true;
    }

    // Initialiseer de tabel bij paginalaad
    updateSelectedProducts();
    updateTotals();
</script>
@endsection

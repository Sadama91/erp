@extends('layouts.app')

@section('page_title', 'Bestellingen aanmaken')

@section('content')
<div class="container mx-auto">
    <form action="{{ route('orders.store') }}" method="POST" id="orderForm" onsubmit="return prepareForm()">
        @csrf
        
        <div class="grid grid-cols-3 gap-4 mb-6 bg-white p-4 rounded-lg shadow w-full">
            <!-- Datum en bestelling informatie -->
                <table class="w-full">
                    <tr>
                        <td class="w-1/8">
                            <label for="date" class="block text-sm font-medium text-gray-700">Datum</label>
                        </td>
                        <td class="w-1/8">
                            <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300 w-48" required>
                        </td>
                        <td class="w-1/8">
                            <label for="customer_name" class="block text-sm font-medium text-gray-700">Klant Naam</label>
                        </td>
                        <td class="w-1/8"> 
                            <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300 w-48" required>
                        </td>
                        <td class="w-1/8">
                            <label for="customer_address" class="block text-sm font-medium text-gray-700">Adres Klant</label>
                        </td>
                        <td class="w-1/8">
                            <input type="text" name="customer_address" id="customer_address" value="{{ old('customer_address') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300 w-48">
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/8">
                            <label for="order_source" class="block mt-4 text-sm font-medium text-gray-700">Besteld Via</label>
                        </td>
                        <td class="w-1/8">
                            <select name="order_source" id="order_source" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300" required>
                                <option value="">Selecteer een optie</option>
                                @foreach ($salesChannels as $channel)
                                    <option value="{{ $channel->value }}" {{ old('order_source') == $channel->value ? 'selected' : '' }}>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="w-1/8">
                            <label for="username" class="block text-sm font-medium text-gray-700">(Vinted) gebruikersnaam</label>
                        </td>
                        <td class="w-1/8">
                            <input type="text" name="username" id="username" value="{{ old('username') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300">
                        </td>
                        <td class="w-1/8">
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">Postcode</label>
                        </td>
                        <td class="w-1/8">
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300">
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/8">
                            <label for="shipping_method" class="block text-sm font-medium text-gray-700">Verzonden Via</label>
                        </td>
                        <td class="w-1/8">
                            <select name="shipping_method" id="shipping_method" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300" required>
                                <option value="">Selecteer een optie</option>
                                @foreach ($shippingMethods as $method)
                                    <option value="{{ $method->value }}" {{ old('shipping_method') == $method->value ? 'selected' : '' }}>
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td rowspan="2" class="w-1/8">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Opmerkingen</label>
                        </td>
                        <td rowspan="2" class="w-1/8">
                            <textarea name="notes" id="notes" rows="3" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300">{{ old('notes') }}</textarea>
                        </td>
                        <td class="w-1/8">
                            <label for="city" class="block text-sm font-medium text-gray-700">Plaats</label>
                        </td>
                        <td class="w-1/8">
                            <input type="text" name="city" id="city" value="{{ old('city') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300">
                        </td>
                    </tr>
                    <tr>
                        <td class="w-1/8">
                            <label for="shipping_cost" class="block text-sm font-medium text-gray-700">Verzendkosten</label>
                        </td>
                        <td class="w-1/8">
                            <input type="number" name="shipping_cost" id="shipping_cost" value="{{ old('shipping_cost') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300" step="0.01">
                        </td>
                        <td class="w-1/8">
                            <label for="country" class="block text-sm font-medium text-gray-700">Land</label>
                        </td>
                        <td class="w-1/8">
                            <input type="text" name="country" id="country" value="{{ old('country') }}" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300">
                        </td>
                    </tr>
                </table>
        </div>
        

        <div class="mb-6">
        <!-- Productzoekfunctie -->
        <table class="w-full">
            <tr>
                <td class="w-2/3">
                        <label for="product_search" class="block text-sm font-medium text-gray-700">Zoek Product</label>
                        <input type="text" id="product_search" placeholder="Zoek op SKU, merk of omschrijving" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-200" oninput="searchProducts()">
                        <ul id="product_list" class="mt-2 border border-gray-300 rounded-md max-h-60 overflow-y-auto hidden"></ul>
                </td>
                <td class="w-1/3 align-bottom">
                    <button 
                    type="button" 
                    class="btn btn-primary bg-indigo-300 hover:bg-indigo-500 rounded-lg transition duration-200 mx-4 px-2 py-2 align-bottom" 
                    onclick="openTotalSalesModal()">
                    Totale Verkoopprijs Verdelen
                </button>
                
                </td>
            </tr>
        </table>
    </div>
        <!-- Totale waarden sectie -->
        <div class="mb-4">
            <h3 class="font-semibold">Totale Informatie</h3>
            <table class="min-w-full mb-2">
                <tbody>
                    <tr>
                        <td>Aantal Items: <span id="total_items">0</span></td>
                        <td>Totale verkoop (incl. BTW): <span id="total_sales_incl">0.00</span></td>
                        <td>Totale inkoopwaarde: <span id="total_purchase">0.00</span></td>
                        <td>Marge: <span id="total_margin">0.00</span> (<span id="total_margin_percent">0.00%</span>)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Tabel voor geselecteerde producten -->
        
        <div class="mb-6 bg-white p-4 rounded-lg shadow w-full">
            <table class="min-w-full mb-6 border-collapse border rounded-md border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border rounded-lg px-4 py-2 text-left">Aantal</th>
                        <th class="border px-4 py-2 text-left">SKU - Merk - Omschrijving</th>
                        <th class="border px-4 py-2 text-right">Verkoop</th>
                        <th class="border px-4 py-2 text-right">Inkoopprijs</th>
                        <th class="border px-4 py-2 text-right">Totaal prijs</th>
                        <th class="border px-4 py-2 text-center">Actie</th>
                    </tr>
                </thead>
                <tbody id="selected_products">
                    <!-- Geselecteerde producten worden hier toegevoegd -->
                </tbody>
            </table>

            <!-- Actieknoppen -->
           <!-- Actieknoppen -->
            <div class="flex justify-end space-x-4">
      <a href="{{ url()->previous() }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-500 transition duration-300">
          &larr; Terug
      </a>
    <button type="button" onclick="resetAllProducts()" class="bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg px-4 py-2 transition duration-200">Reset selectie</button>
                <button type="submit" name="concept_opslaan" onclick="prepareForm()" class="bg-gray-300 hover:bg-gray-500 rounded-lg transition duration-200 px-2 py-2">Concept opslaan</button>
                <button type="submit" name="definitief_opslaan" onclick="prepareForm()" class="bg-green-500 hover:bg-green-800 rounded-lg transition duration-200 px-2 py-2">Definitief opslaan</button>
            </div>

        </div>
    </form>
</div>

<!-- Modal HTML -->
<div id="totalSalesModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Totale Verkoopprijs</h2>
            <p>Voer de totale verkoopprijs in:</p>
            <input 
                type="number" 
                id="totalSalesInput" 
                class="w-full border rounded p-2 mt-2" 
                placeholder="Bijvoorbeeld: 100" 
                min="0" 
                step="0.01">
            <div class="flex justify-end mt-4">
                <button 
                    class="btn btn-secondary mr-2" 
                    onclick="closeTotalSalesModal()">
                    Annuleren
                </button>
                <button 
                    class="btn btn-primary" 
                    onclick="applyTotalSales()">
                    Toepassen
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openTotalSalesModal() {
    if (selectedProducts.length === 0) {
        alert('Selecteer minimaal één artikel om de verkoopprijs te verdelen.');
        return;
    }
    document.getElementById('totalSalesModal').classList.remove('hidden');
}

function closeTotalSalesModal() {
    document.getElementById('totalSalesModal').classList.add('hidden');
}
function applyTotalSales() {
    // Stap 1: comma's vervangen door punt
    const rawInput = document.getElementById('totalSalesInput').value.replace(',', '.');
    const totalSalesInput = parseFloat(rawInput);

    if (isNaN(totalSalesInput) || totalSalesInput <= 0) {
        alert('Voer een geldig bedrag in.');
        return;
    }

    const totalQuantity = selectedProducts.reduce((sum, p) => sum + parseInt(p.quantity || 1), 0);
    if (totalQuantity === 0) {
        alert('Geen artikelen geselecteerd.');
        return;
    }

    const totalOriginalPrice = selectedProducts.reduce((sum, p) => sum + (parseFloat(p.sell_price) * parseInt(p.quantity || 1)), 0);

    // Verkoopprijs per stuk, ongerond
    const unitPriceRaw = totalSalesInput / totalQuantity;
    let runningTotal = 0;

    selectedProducts.forEach((product, index) => {
        const quantity = parseInt(product.quantity || 1);
        // Bereken het aandeel van dit product in het totaal, gebaseerd op de originele verkoopprijs
        const productOriginalTotal = parseFloat(product.sell_price) * quantity;

        const productRatio = productOriginalTotal / totalOriginalPrice;

        // Stap 5: Verdeel de totale verkoopprijs naar rato over de producten
        const subtotal = totalSalesInput * productRatio;

        // Interne waarde
        product.subtotal = subtotal.toFixed(2);

        // Haal het invoerveld voor het subtotaal op en werk de waarde bij
        const subtotalInput = document.querySelector(`input[name="products[${index}][subtotal]"]`);
        if (subtotalInput) {
            subtotalInput.value = product.subtotal; // Zet de subtotaal waarde in het invoerveld
        }

        // Werk de subtotaal op de regel bij
        const subtotalCell = document.querySelector(`#product-row-${index} .subtotal-cell`);
        if (subtotalCell) {
            subtotalCell.innerText = `€ ${product.subtotal}`; // Werk de subtotaal weer in de tabel
        }
    });

    updateTotals();
    closeTotalSalesModal();
}


    feather.replace();
    const searchResults = document.getElementById('product_list');
    const productTable = document.getElementById('selected_products');
    const selectedProducts = [];

    // Functie om producten te zoeken
async function searchProducts() {
    const query = document.getElementById('product_search').value;
    const selectedProducts = getSelectedProductIds();  // Haal de geselecteerde product ID's op

    if (!query) {
        searchResults.classList.add('hidden');
        return;
    }

    try {
        // Voeg de geselecteerde producten toe aan de zoek-URL
        const url = new URL("{{ route('products.search') }}");
        url.searchParams.append('query', query);
        selectedProducts.forEach(id => url.searchParams.append('selectedProducts[]', id));

        const response = await fetch(url);
        const products = await response.json();

        // Verwerk de producten
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
                lastPurchasePrice: parseFloat(product.last_purchase_unit_price),
                LongDescription: `${product.sku} - ${product.brand_name} - ${product.name}`, // Samengestelde omschrijving
                stock: product.stock || 0,
            };
        });

        console.log(filteredProducts);
        displayProducts(filteredProducts); // Toon de gefilterde producten
    } catch (error) {
        console.error("Error fetching products:", error);
    }
}

// Functie om de geselecteerde product-ID's op te halen
function getSelectedProductIds() {
    // Bijvoorbeeld: Dit zou een array moeten zijn van de geselecteerde product-ID's.
    // Dit kun je aanpassen op basis van je specifieke implementatie.
    return selectedProducts.map(product => product.id);
}


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
                selectProduct(product);
            });


            productContainer.appendChild(productItem);
            
        });
    }
    function selectProduct(product) {
    const existingProductIndex = selectedProducts.findIndex(p => p.id === product.id);
    if (existingProductIndex !== -1) {
        alert('Dit product is al geselecteerd.');
        return;
    }

    selectedProducts.push({ 
        id: product.id, 
        name: product.name, 
        sku: product.sku, 
        lastPurchaseUnitPrice: product.lastPurchasePrice, 
        sell_price: product.sell_price, 
        purchaseQuantity: product.purchaseQuantity, 
        sellQuantity: product.sellQuantity, 
        quantity: 1, 
        discount: 0, 
        originalSellPrice: product.sell_price,
        LongDescription: product.LongDescription,
        stock: product.stock || 0,
    });

    updateSelectedProducts();
    updateTotals();

    document.getElementById('product_list').innerHTML = '';
    document.getElementById('product_list').classList.add('hidden');
    document.getElementById('product_search').value = '';
}


function updateSelectedProducts() {
    const productTableBody = document.getElementById('selected_products');
    productTableBody.innerHTML = ''; // Leeg de tabel

    selectedProducts.forEach((product, index) => {
        const sellPrice = parseFloat(product.sell_price); // afgerond
        const quantity = parseInt(product.quantity) || 1;
          const discount = parseFloat(product.discount) || 0;
        const lastPurchaseUnitPrice = parseFloat(product.lastPurchaseUnitPrice) || 0.00;

        // Bereken het subtotaal
        const subtotal = (sellPrice * quantity * (1 - discount / 100)).toFixed(2);
        product.subtotal = subtotal; // Werk het productobject bij

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="number" name="products[${index}][quantity]" value="${quantity}" 
                    min="1" 
                    class="w-16 text-center rounded"
                    onchange="updateQuantity(${index}, this.value)">
                <input type="hidden"
                        name="products[${index}][id]" 
                        value="${product.id}">
            </td>
            <td>${product.LongDescription || '-'}</td>
            <td>
                <input type="hidden"
                        name="products[${index}][originalSellPrice]" 
                        value="${sellPrice.toFixed(2)}">
                €<input type="number" 
                        name="products[${index}][sell_price]" 
                        value="${sellPrice.toFixed(2)}" 
                        step="0.01" 
                        class="w-20 text-right border rounded"
                        onchange="updateSellPrice(${index}, this.value)">
            </td>
            <td>
                <input type="hidden"
                        name="products[${index}][lastPurchaseUnitPrice]" 
                        value="${lastPurchaseUnitPrice.toFixed(2)}">
                        €${lastPurchaseUnitPrice.toFixed(2)}
                <input type="hidden"
                        name="products[${index}][discount]" 
                        value="${lastPurchaseUnitPrice.toFixed(2)}">
                        </td>
            
            <td>
                €<input type="number" 
                        name="products[${index}][subtotal]" 
                        value="${subtotal}" 
                        step="0.01" 
                        class="w-20 text-right border rounded">
            </td>
            <td>
                <table><tr><td>
                <a href="#" onclick="resetProduct(${index})" class="text-gray-500 hover:text-gray-700 inline-flex items-center" hovertitle="Reset deze rij">
                    <svg class="w-5 h-5 mr-1" data-feather="rotate-ccw"></svg>
                </a>
                </td><td>
                    
                <a href="#" onclick="removeProduct(${index})" class="text-red-500 hover:underline" title="Verwijderen dit product">
                    <svg class="w-5 h-15 mr-1" data-feather="trash-2"></svg>
                </a>
                </td></tr></table>
            </td>

        `;
        productTableBody.appendChild(row);
        feather.replace();
    });

    updateTotals(); // Update de totale prijs en andere samenvattingen
}
function updateTotals() {
    const totalItems = selectedProducts.reduce((sum, product) => sum + product.quantity, 0);

    const totalSalesIncl = selectedProducts.reduce((sum, product) => {
        const totalPrice = parseFloat(product.subtotal) || 0.00; // Gebruik product.subtotal om de totalen op te tellen
        return sum + totalPrice;
    }, 0).toFixed(2);

    const totalPurchase = selectedProducts.reduce((sum, product) => {
        const lastPurchasePrice = parseFloat(product.lastPurchaseUnitPrice) || 0.00;
        const quantity = parseInt(product.quantity) || 1;
        return sum + (lastPurchasePrice * quantity);
    }, 0).toFixed(2);

    const totalMargin = (totalSalesIncl - totalPurchase).toFixed(2);
    const totalMarginPercent = (totalSalesIncl > 0 ? (totalMargin / totalSalesIncl * 100).toFixed(2) : '0') + '%';

    // Update de UI met de berekende waarden
    document.getElementById('total_items').innerText = totalItems;
    document.getElementById('total_sales_incl').innerText = `€${totalSalesIncl}`;
    document.getElementById('total_purchase').innerText = `€${totalPurchase}`;
    document.getElementById('total_margin').innerText = `€${totalMargin}`;
    document.getElementById('total_margin_percent').innerText = totalMarginPercent;
}

function resetProduct(index) {
    selectedProducts[index].quantity = 1;
    selectedProducts[index].sell_price = selectedProducts[index].originalSellPrice;
    updateSelectedProducts();
}


function updateQuantity(index, value) {
    const input = document.querySelector(`input[name="products[${index}][quantity]"]`);
    const maxStock = selectedProducts[index].stock || Infinity;
    let newQuantity = parseInt(value) || 1;

    if (newQuantity > maxStock) {
        newQuantity = maxStock;
        selectedProducts[index].quantity = newQuantity;

        // Reset input value
        input.value = newQuantity;

        // Tooltip toevoegen
        input.title = "Er zijn in totaal "+ maxStock +" op voorraad";

        // Rood randje en animatie
        input.classList.remove('border-gray-300', 'border');
input.classList.add('border-red-500', 'ring-2', 'ring-red-500', 'animate-pulse');

        // Na 5 seconden: styling weghalen
        setsTimeout(() => {
    input.classList.remove('border-red-500', 'ring-2', 'ring-red-500', 'animate-pulse');
    input.classList.add('border', 'border-gray-300'); // voeg originele border terug toe
}, 50000);

    } else {
        selectedProducts[index].quantity = newQuantity;
    }
// Zoek of er al een foutmelding bestaat, anders maken we die aan
let warning = document.getElementById(`quantity-warning-${index}`);
if (!warning) {
    warning = document.createElement('div');
    warning.id = `quantity-warning-${index}`;
    warning.className = 'text-sm text-red-600 mt-1';
    input.parentElement.appendChild(warning);
}
warning.innerText = 'Maximaal op voorraad: ' + maxStock + ' stuks';
warning.classList.remove('hidden');

    updateSelectedProducts();
}



    function updateSellPrice(index, value) {
        selectedProducts[index].sell_price = parseFloat(value) || 0.00;
        updateSelectedProducts();
    }

    function updateDiscount(index, value) {
        selectedProducts[index].discount = parseInt(value) || 0;
        updateSelectedProducts();
    }

    function removeProduct(index) {
        selectedProducts.splice(index, 1);
        updateSelectedProducts();
    }


    function prepareForm() {
        document.getElementById('products').value = JSON.stringify(selectedProducts);
        return true;
    }
    function resetAllProducts() {
    if (confirm("Weet je zeker dat je alle geselecteerde producten wilt verwijderen?")) {
        selectedProducts.length = 0;
        updateSelectedProducts();
        updateTotals();
    }
}

</script>

@endsection

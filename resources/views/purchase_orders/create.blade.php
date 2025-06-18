@extends('layouts.app')

@section('page_title', 'Inkooporders aanmaken')

@section('content')
<div class="container mx-auto">
    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseOrderForm">
        @csrf

        <!-- Bovenste sectie met datum, leverancier, titel en opmerkingen -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Linker kolom -->
            <div>
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-700">Datum</label>
                    <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
                </div>

                <div class="mb-4">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Leverancier</label>
                    <select name="supplier_id" id="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
                        <option value="" disabled selected>Kies een leverancier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Rechter kolom -->
            <div>
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Opmerkingen</label>
                    <textarea name="notes" id="notes" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Totale waarden sectie -->
        <div class="mb-4">
            <h3 class="font-semibold">Totale Informatie</h3>
            <table class="min-w-full mb-2">
                <tbody>
                    <tr>
                        <td>Aantal Items: <span id="total_items">0</span></td>
                        <td>Totale inkoop (incl. BTW): €<span id="total_purchase_incl">0.00</span></td>
                        <td>Totale verkoopwaarde: €<span id="total_sales">0.00</span></td>
                        <td>Marge: €<span id="total_margin">0.00</span> (<span id="total_margin_percent">0.00%</span>)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Productzoekfunctie -->
        <div class="mb-4">
            <label for="product_search" class="block text-sm font-medium text-gray-700">Zoek Product</label>
            <input type="text" id="product_search" placeholder="Zoek op productnaam of SKU" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" oninput="searchProducts()">
            <ul id="product_list" class="mt-2 border border-gray-300 rounded-md max-h-60 overflow-y-auto hidden"></ul>
        </div>

        <!-- Tabel voor geselecteerde producten -->
        <table class="min-w-full mb-4">
  <thead>
    <tr>
      <th class="w-8 text-left">Aantal</th>
      <th class="w-8 text-left">SKU</th>
      <th class="w-64 text-left">Artikel</th>
      <th class="w-20 text-left">Eenheden</th>
      <th class="w-28 text-left">Prijs (incl. BTW)</th>
      <th class="w-28 text-left">Prijs (excl. BTW)</th>
      <th class="w-32 text-left">Totale Inkoop</th>
      <th class="w-20 text-left">Verkoopwaarde</th>
      <th class="w-24 text-left">Acties</th>
    </tr>
  </thead>

            <tbody id="selected_products">
                <!-- Geselecteerde producten worden hier toegevoegd -->
            </tbody>
        </table>

        <!-- Verborgen invoerveld voor geselecteerde producten -->
        <input type="hidden" name="products" id="products" value="{{ old('products') }}">

        <button type="submit" name="concept_opslaan" class="btn btn-secondary bg-yellow-500 hover:bg-yellow-800 rounded-lg transition duration-200 px-2 py-2">Opslaan</button>
         </form>
</div>

<script>
 const searchResults = document.getElementById('product_list'); // Correcte ID
const productTable = document.getElementById('selected_products');
const selectedProducts = [];

// Functie om producten te zoeken
async function searchProducts() {
    const query = document.getElementById('product_search').value; // Haal de zoekopdracht op
    if (!query) {
        searchResults.classList.add('hidden'); // Verberg als er geen query is
        return;
    }

    try {
        const response = await fetch(`{{ route('products.search') }}?query=${query}&inStock=all`);
        const products = await response.json();
        // Filter en weergeven van producten
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
                lastPurchasePrice: parseFloat(product.last_purchase_price) // Zorg ervoor dat de prijs als float wordt weergegeven
            };
        });

        displayProducts(filteredProducts);
    } catch (error) {
        console.error("Error fetching products:", error);
    }
}

function displayProducts(products) {
    const productContainer = document.getElementById('product_list');
    productContainer.innerHTML = ''; // Maak de container leeg voordat je nieuwe producten toevoegt
    productContainer.classList.remove('hidden'); // Zorg ervoor dat de lijst zichtbaar is

    products.forEach(product => {
        const productItem = document.createElement('li'); // Gebruik 'li' voor een lijstitem

        productItem.classList.add('product-item', 'p-2', 'border-b', 'border-gray-300'); // Voeg wat styling toe
        productItem.innerHTML = `
            <div>
                <span class="font-bold">${product.sku}</span> - 
                <span>${product.subgroupName}</span> - 
                <span>${product.brandName}</span> - 
                <span>${product.name}</span>
            </div>
        `;

        // Voeg een click event toe aan het productItem om het product te selecteren
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
    document.getElementById('product_search').value = ''; // Reset het zoekveld
    searchResults.classList.add('hidden'); // Verberg de lijst
}

// Functie om de geselecteerde producten bij te werken
function updateSelectedProducts() {
    const tbody = productTable;
    tbody.innerHTML = '';

    selectedProducts.forEach((product, index) => {
        const totalIncl = (product.priceIncl * product.quantity).toFixed(2);
        const sellFactor = (product.sellQuantity / product.purchaseQuantity);
        const totalSellPrice = (sellFactor * (product.sell_price * product.quantity)).toFixed(2); // Corrigeer deze regel
        const priceExcl = (product.priceIncl / 1.21).toFixed(2);

        // Update de totale verkoopprijs in het product object
        product.totalSellPrice = totalSellPrice;
        const totalUnits = sellFactor * product.quantity;

        tbody.innerHTML += `
 <tr>
    <td>
      <input type="number" value="${product.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" class="w-16 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
    </td>
    <td>${product.sku}</td>
    <td>${product.name}</td>
    <td>${sellFactor} (${totalUnits})</td>
    <td>
      <input type="number" value="${product.priceIncl}" step="0.01" onchange="updatePrice(${index}, this.value)" class="w-20 border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200">
    </td>
    <td>€ ${priceExcl}</td>
    <td>€ ${totalIncl}</td>
    <td>€ ${totalSellPrice}</td>
    <td>
      <button type="button" onclick="removeProduct(${index})" class="bg-red-500 text-white px-2 py-1 rounded-md">
        <!-- Trash Icon (Heroicons outline) -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V4a1 1 0 011-1h6a1 1 0 011 1v3" />
        </svg>
      </button>
    </td>
  </tr>
        `;
    });

    document.getElementById('products').value = JSON.stringify(selectedProducts); // Update het verborgen veld
}

// Functie om de hoeveelheid van een product bij te werken
function updateQuantity(index, quantity) {
    selectedProducts[index].quantity = parseInt(quantity);
    updateSelectedProducts();
    updateTotals();
}

// Functie om de prijs van een product bij te werken
function updatePrice(index, newPrice) {
    selectedProducts[index].priceIncl = parseFloat(newPrice);
    updateSelectedProducts();
    updateTotals();
}

// Functie om de totale waarden bij te werken
function updateTotals() {
    const totalItems = selectedProducts.reduce((total, product) => total + product.quantity, 0);
    const totalPurchaseIncl = selectedProducts.reduce((total, product) => total + (product.priceIncl * product.quantity), 0).toFixed(2);
    
    // Gebruik de juiste berekening voor de totale verkoopprijs
    const totalSales = selectedProducts.reduce((total, product) => total + parseFloat(product.totalSellPrice), 0).toFixed(2);

    const totalMargin = (totalSales - totalPurchaseIncl).toFixed(2);
    const totalMarginPercent = totalSales > 0 ? ((totalMargin / totalSales) * 100).toFixed(2) : 0;

    document.getElementById('total_items').innerText = totalItems;
    document.getElementById('total_purchase_incl').innerText = totalPurchaseIncl;
    document.getElementById('total_sales').innerText = totalSales;
    document.getElementById('total_margin').innerText = totalMargin;
    document.getElementById('total_margin_percent').innerText = totalMarginPercent + '%';
}

// Functie om een product te verwijderen
function removeProduct(index) {
    selectedProducts.splice(index, 1);
    updateSelectedProducts();
    updateTotals();
}

// Voorbereiden van het formulier voor verzending
function prepareForm() {
    // Controleer of er geselecteerde producten zijn
    if (selectedProducts.length === 0) {
        alert('Je moet ten minste één product selecteren.');
        return false; // Voorkom verzending
    }

    // Controleer of de inkoopprijs altijd groter is dan 0
    for (let product of selectedProducts) {
        if (product.priceIncl <= 0) {
            alert('De inkoopprijs moet groter zijn dan 0.');
            return false;
        }
    }

    return true; // Sta verzending toe
}

</script>
@endsection

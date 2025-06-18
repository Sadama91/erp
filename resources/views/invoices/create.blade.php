@extends('layouts.app')

@section('page_title', 'Nieuwe factuur')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-6">Nieuwe factuur</h2>

    <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" id="invoice-form">
        @csrf
        @if(session('warning'))
            <div class="mb-4 bg-yellow-100 text-yellow-800 px-4 py-2 rounded">
                {{ session('warning') }}
            </div>
        @endif

        @if(isset($fromPurchaseOrderId))
            <input type="hidden" name="purchase_order_id" value="{{ $fromPurchaseOrderId }}">
        @endif

        <!-- Factuur info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Referentie (extern)</label>
                    <input type="text" name="invoice_reference" id="invoice_reference" value="{{ old('invoice_reference') }}" class="w-full border p-2 rounded">
                </div>
                <div> 
                    <label class="block font-semibold mb-1">Type</label>
                    <select name="type" class="w-full border p-2 rounded" required>
                        @foreach($invoiceTypes as $type)
                            <option value="{{ $type->value }}" {{ old('type', 'purchase') == $type->value ? 'selected' : '' }}>
                                {{ $type->name ?? $type->value }}
                            </option>
                        @endforeach
                    </select>
                </div>     
            </div>        
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <p>Plaats waar geboekt wordt. Bij organisatie, komt het van de rekening. Anders wordt het voorgeschoten.
                </div>
                <div> 
                    <label class="block font-semibold mb-1">Boeken op</label>
                    <select name="booking_account" class="w-full border p-2 rounded" required>
                    <option value="" disabled {{ old('booking_account') == '' ? 'selected' : '' }}>-- Geen --</option>
                        <option value="org" @if(old('booking_account') == 'org') selected ?? @endif>Organisatie</option>
                        <option value="sanne"@if(old('booking_account') == 'sanne') selected ?? @endif>Sanne</option>
                        <option value="sander"@if(old('booking_account') == 'sander') selected ?? @endif>Sander</option>
                    </select>
                </div>     
            </div>
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Datum</label>
                    <input type="text" name="date" id="date" value="{{ old('date', date('d-m-Y')) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300 w-48">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Vervaldatum</label>
                    <input type="text" name="due_date" id="due_date" value="{{ old('due_date') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-200 focus:border-blue-300 w-48">
                </div> 
            </div>
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Leverancier</label>
                    <select name="supplier_id" class="w-full border p-2 rounded">
                        <option value="">-- Geen --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}"
                                {{ old('supplier_id', $prefillSupplier ?? '') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div> 
                <label class="block font-semibold mb-1"><em>alleen bij onbekende leverancier</em></label>
                    <input type="text" name="name" id="unknown-supplier-name" value="{{ old('name') }}" class="w-full border p-2 rounded">          
                </div>     
            </div>
            <div>
                <label class="block font-semibold mb-1">Inkooporder koppelen <em>(optioneel)</em></label>
                <select id="purchase_order_id" name="purchase_order_id" class="border p-2 rounded w-full">
                    <option value="">-- Geen --</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}"
                        @if(!$createFrom == null)
                            @if($createFrom->id == $po->id)
                                selected
                            @endif
                        @endif
                        >
                            #{{ $po->id }} - {{ $po->supplier->name }} ({{ $po->purchaseOrderItems->count() }} items)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Checkbox boven de regels -->
        <div class="mb-4">
            <label>
                <input type="checkbox" id="price-mode-checkbox" checked>
                Prijzen zijn inclusief BTW
            </label>
        </div>
        <input type="hidden" name="amount_excl_vat_total" id="amount_excl_vat_total" value="0">
        <input type="hidden" name="amount_incl_vat_total" id="amount_incl_vat_total" value="0">
        <input type="hidden" name="vat_total" id="vat_total" value="0">

        <!-- Factuurregels -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Factuurregels</h3>
            <table class="w-full table-auto border border-gray-300 text-sm" id="invoice-lines">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">Product</th>
                        <th class="border px-2 py-1">Omschrijving</th>
                        <th class="border px-2 py-1">Aantal</th>
                        <th class="border px-2 py-1">Prijs</th>
                        <th class="border px-2 py-1">BTW %</th>
                        <th class="border px-2 py-1">Totaal</th>
                        <th class="border px-2 py-1"></th>
                    </tr>
                </thead>
                <tbody id="lines-body">
                    <!-- Dynamisch gevuld via JS -->
                </tbody>
            </table>
            <button type="button" onclick="addLine()" class="mt-2 bg-blue-500 text-white px-3 py-1 rounded">
                + Regel
            </button>
        </div>

        <!-- Subtotalen -->
        <div id="subtotal" class="text-right font-bold mt-4">
            Subtotaal: Excl: € 0,00 | BTW: € 0,00 | Incl: € 0,00
        </div>
        <div id="total-price-sum" class="text-right font-bold mt-2">
            Totaalprijs (aantal * prijs): € 0,00
        </div>

        <!-- Status -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block font-semibold mb-1">Status</label>
                <select name="status" class="w-full border p-2 rounded" required>
                    @foreach($invoiceStatuses as $status)
                        <option value="{{ $status->value }}" {{ old('status', 'open') == $status->value ? 'selected' : '' }}>
                            {{ $status->name ?? $status->value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Ondersteunende documenten -->
        <div class="mb-6">
            <label class="block font-semibold mb-1">Ondersteunende documenten</label>
            <input type="file" name="supportingDocuments[]" multiple class="w-full border p-2 rounded">
        </div>

        <div class="flex justify-between">
            <a href="{{ route('invoices.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                Annuleren
            </a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                Factuur opslaan
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Haal de oude factuurregels op; als er geen zijn, is dit een lege array
    const oldInvoiceLines = @json(old('invoice_lines', []));
    
    if(oldInvoiceLines.length > 0) {
        oldInvoiceLines.forEach(line => addLine(line));
    } else if (purchaseOrderSelect && purchaseOrderSelect.value) {
        loadPurchaseOrder();
    }
    
    const priceModeCheckbox = document.getElementById('price-mode-checkbox');
    if (priceModeCheckbox) {
        priceModeCheckbox.addEventListener('change', updateLineTotals);
    }
});
// Verkrijg de producten en BTW tarieven vanuit de controller
const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->sku . ' - ' . $p->name]));
const vatRatesRaw = @json($vatRates->map(fn($rate) => ['value' => $rate->value, 'label' => $rate->label]));
const purchaseOrderSelect = document.getElementById('purchase_order_id');
if (purchaseOrderSelect) {
  purchaseOrderSelect.addEventListener('change', loadPurchaseOrder);
}

const vatRates = vatRatesRaw.map(rate => {
    let value = parseFloat(rate.value);
    return {
        value: value,
        label: (value * 100).toFixed(0) + '%'
    };
});

let lineIndex = 0;

function addLine(data = {}) {
    const row = document.createElement('tr');

    let productOptions = `<option value="">-</option>`;
    products.forEach(p => {
        const selected = data.product_id == p.id ? 'selected' : '';
        productOptions += `<option value="${p.id}" ${selected}>${p.label}</option>`;
    });

    let vatOptions = `<option value="">-</option>`;
    vatRates.forEach(rate => {
        const selected = rate.value == (data.tax_rate ?? 0.21) ? 'selected' : '';
        vatOptions += `<option value="${rate.value}" ${selected}>${rate.label}</option>`;
    });

    row.innerHTML = `
        <td class="border px-2 py-1">
            <select name="invoice_lines[${lineIndex}][product_id]" class="border rounded w-full">
                ${productOptions}
            </select>
        </td>
        <td class="border px-2 py-1">
            <input type="text" name="invoice_lines[${lineIndex}][description]" value="${data.description || ''}" class="border w-full rounded px-2">
        </td>
        <td class="border px-2 py-1">
            <input type="number" min="0" name="invoice_lines[${lineIndex}][quantity]" value="${data.quantity || 1}" class="border w-full rounded px-2">
        </td>
        <td class="border px-2 py-1">
            <input type="number" step="0.01" min="0" name="invoice_lines[${lineIndex}][unit_price]" value="${data.unit_price ?? ''}" class="border w-full rounded px-2">
        </td>
        <td class="border px-2 py-1">
            <select name="invoice_lines[${lineIndex}][vat_rate]" class="border rounded w-full">
                ${vatOptions}
            </select>
        </td>
        <td class="border px-2 py-1 text-right">
            <span class="line-total">€ 0,00</span>
            <!-- Verborgen velden voor de berekende bedragen -->
            <input type="hidden" name="invoice_lines[${lineIndex}][amount_excl_vat_total]" class="line-amount_excl" value="0">
            <input type="hidden" name="invoice_lines[${lineIndex}][amount_incl_vat_total]" class="line-amount_incl" value="0">
            <input type="hidden" name="invoice_lines[${lineIndex}][total_vat]" class="line-total_vat" value="0">
        </td>
        <td class="border px-2 py-1 text-center">
            <button type="button" onclick="this.closest('tr').remove(); updateLineTotals();" class="text-red-600">✕</button>
        </td>
    `;
    document.getElementById('lines-body').appendChild(row);
    lineIndex++;

    // Voeg event listeners toe zodat bij wijziging de totalen worden bijgewerkt.
    row.querySelectorAll('input[name$="[quantity]"], input[name$="[unit_price]"]').forEach(input => {
        input.addEventListener('input', updateLineTotals);
    });
    row.querySelector('select[name$="[vat_rate]"]').addEventListener('change', updateLineTotals);

    updateLineTotals();
}
function updateLineTotals() {
    let totalExclAll = 0, totalVatAll = 0, totalInclAll = 0, totalPriceOnly = 0;
    const priceModeIsIncl = document.getElementById('price-mode-checkbox').checked;
    
    const rows = document.querySelectorAll('#invoice-lines tbody tr');
    rows.forEach(row => {
        const quantityInput = row.querySelector('input[name$="[quantity]"]');
        const unitPriceInput = row.querySelector('input[name$="[unit_price]"]');
        const taxRateSelect = row.querySelector('select[name$="[vat_rate]"]');

        const quantity = parseFloat(quantityInput.value) || 0;
        const taxRate = parseFloat(taxRateSelect.value) || 0;
        let unitPriceIncl = 0, unitPriceExcl = 0, vatPerUnit = 0;
        const inputUnitPrice = parseFloat(unitPriceInput.value) || 0;
        
        if (priceModeIsIncl) {
            unitPriceIncl = inputUnitPrice;
            unitPriceExcl = unitPriceIncl / (1 + taxRate);
            vatPerUnit = unitPriceIncl - unitPriceExcl;
        } else {
            unitPriceExcl = inputUnitPrice;
            unitPriceIncl = unitPriceExcl * (1 + taxRate);
            vatPerUnit = unitPriceIncl - unitPriceExcl;
        }

        const totalExcl = quantity * unitPriceExcl;
        const totalVat = quantity * vatPerUnit;
        const totalIncl = quantity * unitPriceIncl;
        totalExclAll += totalExcl;
        totalVatAll += totalVat;
        totalInclAll += totalIncl;
        totalPriceOnly += quantity * inputUnitPrice;

        const lineTotalCell = row.querySelector('.line-total');
        lineTotalCell.innerText = `Excl: € ${totalExcl.toFixed(2)} | BTW: € ${totalVat.toFixed(2)} | Incl: € ${totalIncl.toFixed(2)}`;
        console.log(lineTotalCell);
        // Update de verborgen velden voor deze regel:
        row.querySelector('.line-amount_excl').value = totalExcl.toFixed(2);
        row.querySelector('.line-amount_incl').value = totalIncl.toFixed(2);
        row.querySelector('.line-total_vat').value = totalVat.toFixed(2);
    });
    
    document.getElementById('subtotal').innerText = `Subtotaal: Excl: € ${totalExclAll.toFixed(2)} | BTW: € ${totalVatAll.toFixed(2)} | Incl: € ${totalInclAll.toFixed(2)}`;
    document.getElementById('total-price-sum').innerText = `Totaalprijs (aantal * prijs): € ${totalPriceOnly.toFixed(2)}`;
    
    // Update de verborgen inputs voor het gehele formulier, indien gewenst.
    document.getElementById('amount_excl_vat_total').value = totalExclAll.toFixed(2);
    document.getElementById('amount_incl_vat_total').value = totalInclAll.toFixed(2);
    document.getElementById('vat_total').value = totalVatAll.toFixed(2);
}


// Form validatie met JS voordat we versturen
document.getElementById('invoice-form').addEventListener('submit', function(e) {
    let errors = [];

    // Validatie: Als leverancier niet geselecteerd is, moet het veld voor "name" ingevuld zijn.
    let supplierSelect = document.querySelector('select[name="supplier_id"]');
    let unknownSupplierName = document.getElementById('unknown-supplier-name');
    if (!supplierSelect.value.trim() && (!unknownSupplierName.value || unknownSupplierName.value.trim() === '')) {
        errors.push("Vul de naam in als er geen leverancier is geselecteerd.");
    }

    // Validatie: Er moet ten minste één factuurregel zijn met ingevulde prijs (en/of product)
    let validLineFound = false;
    document.querySelectorAll('#lines-body tr').forEach(row => {
        // Hier kun je bijvoorbeeld controleren of de prijs is ingevuld en groter is dan 0
        const unitPriceInput = row.querySelector('input[name$="[unit_price]"]');
           if (
            unitPriceInput && unitPriceInput.value.trim() !== "" && parseFloat(unitPriceInput.value) > 0
        ) {
            validLineFound = true;
        }
    });
    if (!validLineFound) {
        errors.push("Voeg ten minste één geldige factuurregel toe.");
    }

    // Validatie: De vervaldatum mag niet vóór de boekdatum liggen.
    let dateValue = document.querySelector('input[name="date"]').value;
    let dueDateValue = document.querySelector('input[name="due_date"]').value;
    if (dateValue && dueDateValue) {
        function parseDutchDate(str) {
            let parts = str.split('-'); // verwacht dd-mm-yyyy
            if (parts.length === 3) {
                return new Date(parts[2], parts[1] - 1, parts[0]);
            }
            return null;
        }
        let dateObj = parseDutchDate(dateValue);
        let dueDateObj = parseDutchDate(dueDateValue);
        if (dateObj && dueDateObj && dueDateObj < dateObj) {
            errors.push("De vervaldatum mag niet voor de boekdatum liggen.");
        }
    }

    if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join("\n"));
    }
});

function loadPurchaseOrder(event) {
  // Als er een event is, gebruik dan event.target, anders het element via getElementById
  const poSelect = event ? event.target : document.getElementById('purchase_order_id');
  if (!poSelect) return;
  
  const purchaseOrderId = poSelect.value;
  const supplierId = poSelect.selectedOptions[0]?.getAttribute('data-supplier-id');
  console.log('Inkooporder geselecteerd:', purchaseOrderId);
  
  // Update de leverancier select als er een supplierId aanwezig is
  if (supplierId) {
    const supplierSelect = document.querySelector('select[name="supplier_id"]');
    if (supplierSelect) {
      supplierSelect.value = supplierId;
    }
  }
  
  // Als er geen purchase order is geselecteerd, stoppen we hier.
  if (!purchaseOrderId) return;
  
  // Haal de inkooporder-items op via fetch
  fetch(`/purchaseOrders/${purchaseOrderId}/items`)
    .then(res => res.json())
    .then(data => {
      // Stel de datum in indien beschikbaar (controleer of het formaat geschikt is)
      const dateField = document.querySelector('input[name="date"]');
      if (dateField && data.date) {
        dateField.value = data.date; // Pas eventueel conversie toe indien nodig
      }
      // Wis bestaande factuurregels
      const linesBody = document.getElementById('lines-body');
      if (linesBody) {
        linesBody.innerHTML = '';
      }
      // Reset de lineIndex (zorg dat deze globaal beschikbaar is)
      lineIndex = 0;
  
      // Voeg elk item als factuurregel toe
      if (data.items && Array.isArray(data.items)) {
        data.items.forEach(item => {
          // Voeg de regel toe via de addLine functie
          addLine({
            product_id: item.product_id,
            description: item.product ? item.product.name : '',
            quantity: item.quantity,
            unit_price: item.price_incl_unit,
            tax_rate: item.tax_rate,
          });
        });
      }
    })
    .catch(err => console.error('Error fetching purchase order items:', err));
}
  
// Zorg dat na DOMContentLoaded ook de functie wordt aangeroepen (bijvoorbeeld als er al een waarde is)
document.addEventListener('DOMContentLoaded', () => {
  // Als er via old() of een ander mechanisme regels voor de factuur al aanwezig zijn, kun je deze toevoegen.
  // Anders: probeer de inkooporder-items te laden (als er een purchase order is geselecteerd)
  if (purchaseOrderSelect && purchaseOrderSelect.value) {
    loadPurchaseOrder();
  }
  
  // Event listener voor de prijsmodus checkbox, indien aanwezig.
  const priceModeCheckbox = document.getElementById('price-mode-checkbox');
  if (priceModeCheckbox) {
    priceModeCheckbox.addEventListener('change', updateLineTotals);
  }
});
</script>
@endsection

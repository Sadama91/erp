@extends('layouts.app')

@section('page_title', 'Factuur bewerken')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Factuur #{{ $invoice->invoice_number }} bewerken</h1>
        <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center text-sm text-gray-600 hover:underline">
            <svg class="w-5 h-5 inline mr-1" data-feather="arrow-left"></svg> Terug
        </a>
    </div>

    <form method="POST" action="{{ route('invoices.update', $invoice) }}" enctype="multipart/form-data" id="invoice-form">
        @csrf
        @method('PUT')

        @if(isset($fromPurchaseOrderId))
            <input type="hidden" name="purchase_order_id" value="{{ $fromPurchaseOrderId }}">
        @endif

        <!-- Factuurgegevens -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Kolom 1: Factuurnummer, referentie, type en status -->
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Factuurnummer</label>
                    <span class="w-full border p-2 rounded">{{ $invoice->invoice_number }}</span>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Referentie (extern)</label>
                    <input type="text" name="invoice_reference" id="invoice_reference" value="{{ old('invoice_reference', $invoice->invoice_reference) }}" class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block font-semibold mb-1">Type</label>
                    <select name="type" class="w-full border p-2 rounded" required>
                        @foreach($invoiceTypes as $type)
                            <option value="{{ $type->value }}" {{ old('type', $invoice->type) == $type->value ? 'selected' : '' }}>
                                {{ $type->name ?? $type->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Status</label>
                    <select name="status" class="w-full border p-2 rounded" required>
                        @foreach($invoiceStatuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', $invoice->status) == $status->value ? 'selected' : '' }}>
                                {{ $status->name ?? $status->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Kolom 2: Datum en vervaldatum -->
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Datum</label>
                    <input type="date" name="date" value="{{ old('date', $invoice->date->format('Y-m-d')) }}" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block font-semibold mb-1">Vervaldatum</label>
                    <input type="date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="w-full border p-2 rounded">
                </div>
            </div>

            <!-- Kolom 3: Leverancier en inkooporder koppelen -->
            <div class="inline-grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold mb-1">Leverancier</label>
                    <select name="supplier_id" class="w-full border p-2 rounded">
                        <option value="">-- Geen --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $invoice->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-1"><em>alleen bij onbekende leverancier</em></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $invoice->name ?? '') }}" class="w-full border p-2 rounded">
                </div>
                <div class="col-span-2">
                    <label class="block font-semibold mb-1">Inkooporder koppelen <em>(optioneel)</em></label>
                    <select id="purchase_order_id" name="purchase_order_id" class="border p-2 rounded w-full">
                        <option value="">-- Geen --</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}" {{ old('purchase_order_id', $invoice->purchase_order_id) == $po->id ? 'selected' : '' }}>
                                #{{ $po->id }} - {{ $po->supplier->name }} ({{ $po->purchaseOrderItems->count() }} items)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Checkbox voor prijsmodus -->
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
                    <!-- Regels worden dynamisch toegevoegd via JS -->
                </tbody>
            </table>
            <button type="button" id="add-line" class="mt-2 bg-blue-500 text-white px-3 py-1 rounded">
                + Regel toevoegen
            </button>
        </div>

        <!-- Subtotalen -->
        <div id="subtotal" class="text-right font-bold mt-4">
            Subtotaal: Excl: € 0,00 | BTW: € 0,00 | Incl: € 0,00
        </div>
        <div id="total-price-sum" class="text-right font-bold mt-2">
            Totaalprijs (aantal * prijs): € 0,00
        </div>

        <!-- Bestaande bijlagen -->
        <div class="mb-6">
            <label class="block font-semibold mb-1">Bestaande bijlagen</label>
            <ul class="list-disc pl-5 text-sm">
                @forelse ($invoice->linking_documents ?? [] as $doc)
                    <li class="flex items-center justify-between">
                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-blue-600 hover:underline">
                            {{ $doc->file_name }} ({{ number_format($doc->file_size / 1024, 1) }} KB)
                        </a>
                        <form action="{{ route('documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('Bijlage verwijderen?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:underline text-xs ml-2">
                                <svg class="w-4 h-4 inline" data-feather="trash-2"></svg> Verwijder
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-gray-500">Geen bijlagen</li>
                @endforelse
            </ul>
        </div>

        <!-- Nieuwe documenten uploaden -->
        <div class="mb-6">
            <label class="block font-semibold mb-1">Nieuwe bijlagen toevoegen</label>
            <input type="file" name="documents[]" multiple class="w-full border p-2 rounded" accept="application/pdf,image/*">
            @error('documents.*') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Actieknoppen -->
        <div class="flex justify-between mt-6">
            <a href="{{ route('invoices.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                Annuleren
            </a>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                Factuur opslaan
            </button>
        </div>
    </form>
</div>
<template id="line-template">
  <tr class="invoice-line border-t">
    <td class="border px-2 py-1">
      <select name="invoice_lines[__index__][product_id]" class="w-full border rounded">
        <option value="">–</option>
        @foreach($products as $product)
          <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
        @endforeach
      </select>
    </td>
    <td class="border px-2 py-1">
      <input type="text" name="invoice_lines[__index__][description]" class="w-full border rounded px-2" placeholder="Omschrijving">
    </td>
    <td class="border px-2 py-1">
      <input type="number" name="invoice_lines[__index__][quantity]" value="1" min="0" step="any" class="w-full border rounded px-2">
    </td>
    <td class="border px-2 py-1">
      <input type="number" name="invoice_lines[__index__][unit_price]" min="0" step="0.01" class="w-full border rounded px-2">
    </td>
    <td class="border px-2 py-1">
      <select name="invoice_lines[__index__][vat_rate]" class="w-full border rounded">
        <option value="">–</option>
        @foreach($vatRates as $rate)
          <option value="{{ $rate->value }}">{{ (float)$rate->value * 100 }}%</option>
        @endforeach
      </select>
    </td>
    <td class="border px-2 py-1 text-right">
      <span class="line-total">€ 0,00</span>
      <input type="hidden" name="invoice_lines[__index__][amount_excl_vat_total]" class="line-amount_excl" value="0">
      <input type="hidden" name="invoice_lines[__index__][amount_incl_vat_total]" class="line-amount_incl" value="0">
      <input type="hidden" name="invoice_lines[__index__][total_vat]" class="line-total_vat" value="0">
    </td>
    <td class="border px-2 py-1 text-center">
      <button type="button" class="remove-line text-red-600">✕</button>
    </td>
  </tr>
</template>

@endSection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  console.log("DOM loaded, starting invoice line setup...");

  let lineIndex = 0;

  const products = @json($products->map(fn($p) => [
    'id' => $p->id,
    'label' => $p->sku . ' - ' . $p->name
  ]));
  console.log("Products:", products);

  const vatRatesRaw = @json($vatRates->map(fn($rate) => [
    'value' => $rate->value,
    'label' => $rate->label
  ]));
  const vatRates = vatRatesRaw.map(rate => {
    let value = parseFloat(rate.value);
    return {
      value: value,
      label: (value * 100).toFixed(0) + '%'
    };
  });
  console.log("VAT Rates:", vatRates);

  // Functie om een factuurregel toe te voegen.
  function addLine(data = {}) {
  console.log("Adding line, data:", data, "lineIndex:", lineIndex);
  const template = document.getElementById('line-template');
  if (!template) {
    console.error("Template 'line-template' niet gevonden.");
    return;
  }
  let clone = document.importNode(template.content, true);
  let clonedRow = clone.firstElementChild;
  if (!clonedRow) {
    console.error("Geen <tr> gevonden in template.");
    return;
  }
  // Vervang alle voorkomens van __index__ in de outerHTML
  let html = clonedRow.outerHTML.replace(/__index__/g, lineIndex);
  const tempTbody = document.createElement('tbody');
  tempTbody.innerHTML = html.trim();
  let newRow = tempTbody.firstElementChild;
  if (!newRow) {
    console.error("Geen <tr> gevonden na parsing.");
    return;
  }
  
  // Vul de velden met data als beschikbaar
  const prodSelect = newRow.querySelector(`select[name="invoice_lines[${lineIndex}][product_id]"]`);
  if (data.product_id && prodSelect) {
    prodSelect.value = data.product_id;
  }
  const descInput = newRow.querySelector(`input[name="invoice_lines[${lineIndex}][description]"]`);
  if (data.description && descInput) {
    descInput.value = data.description;
  }
  const qtyInput = newRow.querySelector(`input[name="invoice_lines[${lineIndex}][quantity]"]`);
  if (data.quantity && qtyInput) {
    qtyInput.value = data.quantity;
  }
  const priceInput = newRow.querySelector(`input[name="invoice_lines[${lineIndex}][unit_price]"]`);
  // Als er geen unit_price is meegegeven, maar wel amount_incl_vat_total en quantity, bereken deze dan
  if ((data.unit_price === undefined || data.unit_price === "" || data.unit_price === null) 
      && data.amount_incl_vat_total && data.quantity) {
    const calculatedPrice = (parseFloat(data.amount_incl_vat_total) / parseFloat(data.quantity)).toFixed(2);
    if (priceInput) {
      priceInput.value = calculatedPrice;
    }
  } else if (data.unit_price !== undefined && priceInput) {
    priceInput.value = data.unit_price;
  }
  
  const vatSelect = newRow.querySelector(`select[name="invoice_lines[${lineIndex}][vat_rate]"]`);
  if (data.vat_rate !== undefined && vatSelect) {
    vatSelect.value = data.vat_rate;
  }
  
  // Voeg de nieuwe regel toe aan het tbody-element
  document.getElementById('lines-body').appendChild(newRow);
  console.log("Nieuwe regel aangemaakt:", newRow.outerHTML);
  console.log("Aantal regels na toevoegen:", document.querySelectorAll('#lines-body tr.invoice-line').length);
  
  // Voeg eventlisteners toe voor deze regel
  newRow.querySelectorAll('input[name$="[quantity]"], input[name$="[unit_price]"]').forEach(input => {
    input.addEventListener('input', updateLineTotals);
  });
  const vatField = newRow.querySelector('select[name$="[vat_rate]"]');
  if (vatField) {
    vatField.addEventListener('change', updateLineTotals);
  }
  const removeBtn = newRow.querySelector('.remove-line');
  if (removeBtn) {
    removeBtn.addEventListener('click', () => {
      console.log("Regel verwijderd:", newRow.outerHTML);
      newRow.remove();
      updateLineTotals();
    });
  }
  
  lineIndex++;
  updateLineTotals();
}


  // Functie om de totalen bij te werken; wordt pas aangeroepen bij wijzigingen
  function updateLineTotals() {
    console.log("updateLineTotals aangeroepen.");
    let totalExclAll = 0, totalVatAll = 0, totalInclAll = 0, totalPriceOnly = 0;
    const priceModeIsIncl = document.getElementById('price-mode-checkbox') ? document.getElementById('price-mode-checkbox').checked : false;
    
    document.querySelectorAll('#invoice-lines tbody tr.invoice-line').forEach(row => {
      const quantity = parseFloat(row.querySelector('input[name$="[quantity]"]').value) || 0;
      const unitPrice = parseFloat(row.querySelector('input[name$="[unit_price]"]').value) || 0;
      const taxRate = parseFloat(row.querySelector('select[name$="[vat_rate]"]').value) || 0;
      
      let unitPriceExcl = 0, unitPriceIncl = 0, vatPerUnit = 0;
      if (priceModeIsIncl) {
        unitPriceIncl = unitPrice;
        unitPriceExcl = unitPriceIncl / (1 + taxRate);
        vatPerUnit = unitPriceIncl - unitPriceExcl;
      } else {
        unitPriceExcl = unitPrice;
        unitPriceIncl = unitPriceExcl * (1 + taxRate);
        vatPerUnit = unitPriceIncl - unitPriceExcl;
      }
      
      const totalExcl = quantity * unitPriceExcl;
      const totalVat = quantity * vatPerUnit;
      const totalIncl = quantity * unitPriceIncl;
      totalExclAll += totalExcl;
      totalVatAll += totalVat;
      totalInclAll += totalIncl;
      totalPriceOnly += quantity * unitPrice;
      
      const lineTotalSpan = row.querySelector('.line-total');
      if (lineTotalSpan) {
        lineTotalSpan.innerText = `Excl: € ${totalExcl.toFixed(2)} | BTW: € ${totalVat.toFixed(2)} | Incl: € ${totalIncl.toFixed(2)}`;
      }
      const amountExclInput = row.querySelector('.line-amount_excl');
      if (amountExclInput) amountExclInput.value = totalExcl.toFixed(2);
      const amountInclInput = row.querySelector('.line-amount_incl');
      if (amountInclInput) amountInclInput.value = totalIncl.toFixed(2);
      const totalVatInput = row.querySelector('.line-total_vat');
      if (totalVatInput) totalVatInput.value = totalVat.toFixed(2);
    });
    
    const subtotalEl = document.getElementById('subtotal');
    if (subtotalEl) {
      subtotalEl.innerText = `Subtotaal: Excl: € ${totalExclAll.toFixed(2)} | BTW: € ${totalVatAll.toFixed(2)} | Incl: € ${totalInclAll.toFixed(2)}`;
    }
    const totalSumEl = document.getElementById('total-price-sum');
    if (totalSumEl) {
      totalSumEl.innerText = `Totaalprijs (aantal * prijs): € ${totalPriceOnly.toFixed(2)}`;
    }
    
    document.getElementById('amount_excl_vat_total').value = totalExclAll.toFixed(2);
    document.getElementById('amount_incl_vat_total').value = totalInclAll.toFixed(2);
    document.getElementById('vat_total').value = totalVatAll.toFixed(2);
    
    console.log("Totalen bijgewerkt:", {
      subtotal: subtotalEl.innerText,
      totalPrice: totalSumEl.innerText
    });
  }
  
  // Laad bestaande factuurregels: Eerst old()-data, anders de invoiceLines-relatie
  let existingLines = [];
  @if(old('invoice_lines'))
    existingLines = @json(old('invoice_lines'));
  @elseif($invoice->invoiceLines->count())
    existingLines = @json($invoice->invoiceLines);
  @endif
  console.log("Bestaande regels:", existingLines);
  if (existingLines.length) {
    existingLines.forEach(line => addLine(line));
  } else {
    addLine();
  }
  
  // Stel lineIndex in op het aantal regels dat nu aanwezig is
  lineIndex = document.querySelectorAll('#lines-body tr.invoice-line').length;
  console.log("lineIndex ingesteld op:", lineIndex);
  
  // Eventlistener voor de prijsmodus checkbox
  const priceModeCheckbox = document.getElementById('price-mode-checkbox');
  if (priceModeCheckbox) {
    priceModeCheckbox.addEventListener('change', updateLineTotals);
  }
  
  // Eventlistener voor de knop "+ Regel toevoegen"
  const addLineBtn = document.getElementById('add-line');
  if (addLineBtn) {
    addLineBtn.addEventListener('click', () => {
      addLine();
    });
  }
  
  // Vervang feather icons
  feather.replace();
});
</script>
@endsection

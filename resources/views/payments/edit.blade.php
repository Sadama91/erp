@extends('layouts.app')

@section('page_title', 'Betaling Bewerken')

@section('content')
<div class="container mx-auto px-4 py-6">
  <h1 class="text-2xl font-bold mb-6">Betaling Bewerken</h1>
  <form action="{{ route('payments.update', $payment->id) }}" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf
    @method('PUT')
    <!-- Datum -->
    <div class="mb-4">
      <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Datum</label>
      <input type="date" name="date" id="date" value="{{ old('date', $payment->date) }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
    </div>
    <!-- Bedrag -->
    <div class="mb-4">
      <label for="amount" class="block text-gray-700 text-sm font-bold mb-2">Bedrag</label>
      <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $payment->amount) }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
    </div>
    <!-- Type -->
    <div class="mb-4">
      <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Type</label>
      <select name="type" id="type" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
        <option value="incoming" {{ old('type', $payment->type) === 'incoming' ? 'selected' : '' }}>Ontvangen</option>
        <option value="outgoing" {{ old('type', $payment->type) === 'outgoing' ? 'selected' : '' }}>Betaling</option>
      </select>
    </div>
    <!-- Methode (dropdown) -->
    <div class="mb-4">
      <label for="method" class="block text-gray-700 text-sm font-bold mb-2">Methode</label>
      <select name="method" id="method" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
        @foreach($paymentMethods as $pm)
          <option value="{{ $pm->value }}" {{ old('method', $payment->method) == $pm->value ? 'selected' : '' }}>
            {{ $pm->name }}
          </option>
        @endforeach
      </select>
    </div>
    <!-- Referentie -->
    <div class="mb-4">
      <label for="reference" class="block text-gray-700 text-sm font-bold mb-2">Referentie</label>
      <input type="text" name="reference" id="reference" value="{{ old('reference', $payment->reference) }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
    </div>
    <!-- Klant -->
    <div class="mb-4">
      <label for="customer_id" class="block text-gray-700 text-sm font-bold mb-2">Klant</label>
      <select name="customer_id" id="customer_id" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
        <option value="">-- Selecteer klant --</option>
        @foreach($customers as $customer)
          <option value="{{ $customer->id }}" {{ old('customer_id', $payment->customer_id) == $customer->id ? 'selected' : '' }}>
            {{ $customer->name }}
          </option>
        @endforeach
      </select>
    </div>
    <!-- Leverancier -->
    <div class="mb-4">
      <label for="supplier_id" class="block text-gray-700 text-sm font-bold mb-2">Leverancier</label>
      <select name="supplier_id" id="supplier_id" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline" required>
        @foreach($suppliers as $supplier)
          <option value="{{ $supplier->id }}" {{ old('supplier_id', $payment->supplier_id) == $supplier->id ? 'selected' : '' }}>
            {{ $supplier->name }}
          </option>
        @endforeach
      </select>
    </div>
    <!-- Journal Entry -->
    <div class="mb-4">
      <label for="journal_entry_id" class="block text-gray-700 text-sm font-bold mb-2">Journal Entry</label>
      <select name="journal_entry_id" id="journal_entry_id" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
        <option value="">-- Selecteer journal entry --</option>
        @foreach($journalEntries as $journal)
          <option value="{{ $journal->id }}" {{ old('journal_entry_id', $payment->journal_entry_id) == $journal->id ? 'selected' : '' }}>
            {{ $journal->title ?? $journal->id }}
          </option>
        @endforeach
      </select>
    </div>

    <!-- Facturen koppelen -->
    <h3 class="text-xl font-semibold mt-6 mb-2">Facturen koppelen</h3>
    <p class="text-gray-600 mb-4">Optioneel: Werk gekoppelde facturen bij. Indien een factuur niet bestaat, wordt deze aangemaakt.</p>
    <div id="invoices-wrapper">
      @if(old('invoices'))
        @foreach(old('invoices') as $index => $invoice)
          <div class="invoice-item mb-4 border p-4 rounded">
            <div class="mb-2">
              <label class="block text-gray-700 text-sm font-bold mb-1">Factuur ID</label>
              <input type="number" name="invoices[{{ $index }}][id]" value="{{ $invoice['id'] }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-2">
              <label class="block text-gray-700 text-sm font-bold mb-1">Bedrag</label>
              <input type="number" step="0.01" name="invoices[{{ $index }}][amount]" value="{{ $invoice['amount'] }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>
            <button type="button" class="btn-remove-invoice bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded inline-flex items-center">
              <i data-feather="trash-2" class="mr-1"></i> Verwijder
            </button>
          </div>
        @endforeach
      @elseif($payment->invoices->count())
        @foreach($payment->invoices as $index => $invoice)
          <div class="invoice-item mb-4 border p-4 rounded">
            <div class="mb-2">
              <label class="block text-gray-700 text-sm font-bold mb-1">Factuur ID</label>
              <input type="number" name="invoices[{{ $index }}][id]" value="{{ $invoice->id }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>
            <div class="mb-2">
              <label class="block text-gray-700 text-sm font-bold mb-1">Bedrag</label>
              <input type="number" step="0.01" name="invoices[{{ $index }}][amount]" value="{{ $invoice->pivot->amount }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
            </div>
            <button type="button" class="btn-remove-invoice bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded inline-flex items-center">
              <i data-feather="trash-2" class="mr-1"></i> Verwijder
            </button>
          </div>
        @endforeach
      @else
        <div class="invoice-item mb-4 border p-4 rounded">
          <div class="mb-2">
            <label class="block text-gray-700 text-sm font-bold mb-1">Factuur ID</label>
            <input type="number" name="invoices[0][id]" value="{{ old('invoices.0.id') }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
          </div>
          <div class="mb-2">
            <label class="block text-gray-700 text-sm font-bold mb-1">Bedrag</label>
            <input type="number" step="0.01" name="invoices[0][amount]" value="{{ old('invoices.0.amount') }}" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
          </div>
        </div>
      @endif
    </div>
    <button type="button" id="add-invoice" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center mb-6">
      <i data-feather="plus" class="mr-2"></i> Voeg nog een factuur toe
    </button>

    <div>
      <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <i data-feather="save" class="mr-2"></i> Opslaan
      </button>
    </div>
  </form>
</div>
<script>
  feather.replace();
  document.getElementById('add-invoice').addEventListener('click', function() {
    var wrapper = document.getElementById('invoices-wrapper');
    var index = wrapper.getElementsByClassName('invoice-item').length;
    var html = `
      <div class="invoice-item mb-4 border p-4 rounded">
        <div class="mb-2">
          <label class="block text-gray-700 text-sm font-bold mb-1">Factuur ID</label>
          <input type="number" name="invoices[${index}][id]" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-2">
          <label class="block text-gray-700 text-sm font-bold mb-1">Bedrag</label>
          <input type="number" step="0.01" name="invoices[${index}][amount]" class="shadow border rounded w-full py-2 px-3 focus:outline-none focus:shadow-outline">
        </div>
        <button type="button" class="btn-remove-invoice bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-2 rounded inline-flex items-center">
          <i data-feather="trash-2" class="mr-1"></i> Verwijder
        </button>
      </div>`;
    wrapper.insertAdjacentHTML('beforeend', html);
    feather.replace();
  });
  
  document.addEventListener('click', function(e) {
    if(e.target && (e.target.classList.contains('btn-remove-invoice') || e.target.closest('.btn-remove-invoice'))){
      e.target.closest('.invoice-item').remove();
    }
  });
</script>
@endsection

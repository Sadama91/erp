@extends('layouts.app')

@section('page_title', 'Betaling Details')

@section('content')
<div class="container mx-auto px-4 py-6">
  <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <h1 class="text-2xl font-bold mb-4">Betaling #{{ $payment->id }}</h1>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Datum:</span> {{ $payment->date }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Bedrag:</span> {{ $payment->amount }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Type:</span> {{ $payment->type }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Methode:</span>
      @php
        $selectedMethod = $paymentMethods->firstWhere('value', $payment->method);
      @endphp
      {{ $selectedMethod ? $selectedMethod->label : $payment->method }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Referentie:</span> {{ $payment->reference }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Klant:</span>
      @if($payment->customer_id)
        @php
          $customer = $customers->firstWhere('id', $payment->customer_id);
        @endphp
        {{ $customer ? $customer->name : $payment->customer_id }}
      @else
        N.v.t.
      @endif
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Leverancier:</span>
      @php
        $supplier = $suppliers->firstWhere('id', $payment->supplier_id);
      @endphp
      {{ $supplier ? $supplier->name : $payment->supplier_id }}
    </div>
    <div class="mb-4">
      <span class="font-semibold text-gray-700">Journal Entry:</span>
      @if($payment->journal_entry_id)
        @php
          $journal = $journalEntries->firstWhere('id', $payment->journal_entry_id);
        @endphp
        {{ $journal ? ($journal->title ?? $journal->id) : $payment->journal_entry_id }}
      @else
        N.v.t.
      @endif
    </div>
  </div>

  <h2 class="text-xl font-bold mb-4">Gekoppelde Facturen</h2>
  @if($payment->invoices->count())
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white border border-gray-200">
        <thead>
          <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
            <th class="py-3 px-4 text-left">Factuur ID</th>
            <th class="py-3 px-4 text-left">Bedrag</th>
          </tr>
        </thead>
        <tbody class="text-gray-700 text-sm">
          @foreach($payment->invoices as $invoice)
            <tr class="border-b border-gray-200 hover:bg-gray-50">
              <td class="py-3 px-4">{{ $invoice->id }}</td>
              <td class="py-3 px-4">{{ $invoice->pivot->amount }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <p class="text-gray-600">Er zijn geen facturen gekoppeld aan deze betaling.</p>
  @endif

  <div class="mt-6">
    <a href="{{ route('payments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
      <i data-feather="arrow-left" class="mr-2"></i> Terug
    </a>
  </div>
</div>
<script>
  feather.replace();
</script>
@endsection

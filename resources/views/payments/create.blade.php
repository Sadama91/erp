@extends('layouts.app')

@section('page_title', 'Factuur Betalen: #' . $invoice->id)

@section('content')
<div class="max-w-md mx-auto bg-white p-6 shadow rounded">
    <h1 class="text-xl font-bold mb-4">Betaling voor Factuur #{{ $invoice->id }}</h1>
    <form action="{{ route('invoices.storePayment', $invoice) }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block font-semibold">Datum</label>
            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="w-full border p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block font-semibold">Bedrag</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->total) }}" class="w-full border p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block font-semibold">Betaalmethode</label>
            <input type="text" name="method" value="{{ old('method') }}" placeholder="Bijv. bankoverschrijving" class="w-full border p-2 rounded">
        </div>
        <div class="mb-4">
            <label class="block font-semibold">Selecteer Betaalrekening</label>
            <select name="bank_account_id" class="w-full border p-2 rounded">
                <option value="">-- Kies een bankrekening --</option>
                @foreach ($bankAccounts as $bank)
                    <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                        {{ $bank->name }} ({{ $bank->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <!-- Referentie wordt automatisch gegenereerd; eventueel tonen als readonly -->
        <div class="mb-4">
            <label class="block font-semibold">Referentie</label>
            <input type="text" name="reference" value="{{ $invoice->id . ' ' . ($invoice->supplier->name ?? '') . ' ' . ($invoice->reference ?? '') }}" readonly class="w-full border p-2 rounded">
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">Betaling Registreren</button>
    </form>
</div>
@endsection

@extends('layouts.app')

@section('page_title', 'Belastingvoorbereiding Bewerken')

@section('content')
<div class="container mx-auto px-4">
  <h1 class="text-2xl font-bold mb-4">Belastingvoorbereiding Bewerken</h1>
  <form action="{{ route('tax_preparations.update', $taxPreparation) }}" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf
    @method('PUT')
    <div class="mb-4">
      <label for="year" class="block text-gray-700 text-sm font-bold mb-2">Jaar</label>
      <input type="text" name="year" id="year" placeholder="bijv. 2025" value="{{ old('year', $taxPreparation->year) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
    </div>
    <div class="mb-4">
      <label for="revenues" class="block text-gray-700 text-sm font-bold mb-2">Totale Omzet</label>
      <input type="number" step="0.01" name="revenues" id="revenues" placeholder="bijv. 10000.00" value="{{ old('revenues', $taxPreparation->revenues) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
    </div>
    <div class="mb-4">
      <label for="expenses" class="block text-gray-700 text-sm font-bold mb-2">Totale Kosten</label>
      <input type="number" step="0.01" name="expenses" id="expenses" placeholder="bijv. 5000.00" value="{{ old('expenses', $taxPreparation->expenses) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
    </div>
    <div class="mb-4">
      <label for="profit" class="block text-gray-700 text-sm font-bold mb-2">Winst</label>
      <input type="number" step="0.01" name="profit" id="profit" placeholder="bijv. 5000.00" value="{{ old('profit', $taxPreparation->profit) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">
    </div>
    <div class="mb-4">
      <label for="data" class="block text-gray-700 text-sm font-bold mb-2">Extra Details (JSON)</label>
      <textarea name="data" id="data" rows="4" placeholder='{"aftrekposten": "...", "correcties": "..."}' class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline">{{ old('data', json_encode($taxPreparation->data)) }}</textarea>
    </div>
    <div class="flex items-center justify-between">
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <i data-feather="save" class="mr-2"></i> Bijwerken
      </button>
      <a href="{{ route('tax_preparations.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
        Annuleren
      </a>
    </div>
  </form>
</div>
@endsection
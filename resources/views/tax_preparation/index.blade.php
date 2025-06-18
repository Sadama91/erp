@extends('layouts.app')

@section('page_title','Belastingvoorbereidingen')

@section('content')
<div class="container mx-auto px-4">
  <h1 class="text-2xl font-bold mb-4">Belastingvoorbereidingen Overzicht</h1>
  
  <div class="mb-4">
    <a href="{{ route('tax_preparations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
      <i data-feather="plus" class="mr-2"></i> Nieuwe Voorbereiding
    </a>
  </div>
  
  <!-- Filterformulier op jaar -->
  <form method="GET" action="{{ route('tax_preparations.index') }}" class="mb-4">
    <div class="flex items-center space-x-2">
      <input type="text" name="year" placeholder="Filter op jaar" value="{{ request('year') }}" class="border border-gray-300 p-2 rounded">
      <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <i data-feather="filter" class="mr-2"></i> Filter
      </button>
    </div>
  </form>

  <!-- Overzichtstabel met kerncijfers -->
  <table class="min-w-full bg-white">
    <thead class="bg-gray-800 text-white">
      <tr>
        <th class="py-3 px-4 uppercase font-semibold text-sm">Jaar</th>
        <th class="py-3 px-4 uppercase font-semibold text-sm">Omzet</th>
        <th class="py-3 px-4 uppercase font-semibold text-sm">Kosten</th>
        <th class="py-3 px-4 uppercase font-semibold text-sm">Winst</th>
        <th class="py-3 px-4 uppercase font-semibold text-sm">Acties</th>
      </tr>
    </thead>
    <tbody class="text-gray-700">
      @foreach($taxPreparations as $tax)
      <tr>
        <td class="py-3 px-4">{{ $tax->year }}</td>
        <td class="py-3 px-4">{{ number_format($tax->revenues, 2) }}</td>
        <td class="py-3 px-4">{{ number_format($tax->expenses, 2) }}</td>
        <td class="py-3 px-4">{{ number_format($tax->profit, 2) }}</td>
        <td class="py-3 px-4">
          <a href="{{ route('tax_preparations.show', $tax) }}" class="text-blue-500 hover:text-blue-700 mr-2">
            <i data-feather="eye"></i>
          </a>
          <a href="{{ route('tax_preparations.edit', $tax) }}" class="text-yellow-500 hover:text-yellow-700 mr-2">
            <i data-feather="edit"></i>
          </a>
          <form action="{{ route('tax_preparations.destroy', $tax) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Weet je zeker dat je deze voorbereiding wilt verwijderen?')">
              <i data-feather="trash-2"></i>
            </button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <div class="mt-4">
    {{ $taxPreparations->links() }}
  </div>
</div>
@endsection
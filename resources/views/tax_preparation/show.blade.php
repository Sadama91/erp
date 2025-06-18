@extends('layouts.app')

@section('page_title', 'Belastingvoorbereiding Details ' .$taxPreperation->year)

@section('content')
<div class="container mx-auto px-4">
  <h1 class="text-2xl font-bold mb-4">Belastingvoorbereiding Details</h1>
  <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    <div class="mb-4">
      <strong class="text-gray-700">Jaar:</strong> {{ $taxPreparation->year }}
    </div>
    <div class="mb-4">
      <strong class="text-gray-700">Totale Omzet:</strong> {{ number_format($taxPreparation->revenues, 2) }}
    </div>
    <div class="mb-4">
      <strong class="text-gray-700">Totale Kosten:</strong> {{ number_format($taxPreparation->expenses, 2) }}
    </div>
    <div class="mb-4">
      <strong class="text-gray-700">Winst:</strong> {{ number_format($taxPreparation->profit, 2) }}
    </div>
    <div class="mb-4">
      <strong class="text-gray-700">Extra Details:</strong>
      @if(!empty($taxPreparation->data))
        <pre class="bg-gray-100 p-4 rounded">{{ json_encode($taxPreparation->data, JSON_PRETTY_PRINT) }}</pre>
      @else
        <span>N.v.t.</span>
      @endif
    </div>
    <!-- Koppeling naar ondersteunende documenten indien van toepassing -->
    <div class="mb-4">
      <a href="{{ route('supporting_documents.index', ['documentable_type' => 'App\Models\TaxPreparation', 'documentable_id' => $taxPreparation->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <i data-feather="file"></i> Ondersteunende Documenten
      </a>
    </div>
  </div>
  <div class="flex space-x-4">
    <a href="{{ route('tax_preparations.edit', $taxPreparation) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
      <i data-feather="edit" class="mr-2"></i> Bewerken
    </a>
    <a href="{{ route('tax_preparations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
      <i data-feather="arrow-left" class="mr-2"></i> Terug naar Overzicht
    </a>
  </div>
</div>
@endsection

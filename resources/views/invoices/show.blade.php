@extends('layouts.app')

@section('page_title', 'Factuur bekijken: #'.$invoice->id)

@section('content')
<div class="max-w-5xl mx-auto p-4" x-data="{ tab: 'details' }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('invoices.index') }}" class="flex items-center text-sm text-gray-600 hover:underline">
            <svg class="w-5 h-5 mr-1" data-feather="arrow-left"></svg> Terug
        </a>
        <h1 class="text-2xl font-bold">Factuur #{{ $invoice->id }}</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b mb-4">
        <nav class="flex space-x-6 text-sm font-medium">
            <button @click="tab = 'details'" :class="tab === 'details' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="file-text"></svg> Details
            </button>
            <button @click="tab = 'files'" :class="tab === 'files' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="paperclip"></svg> Bijlagen
            </button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="activity"></svg> Auditlog
            </button>
        </nav>
    </div>
    <!-- Tab Content -->
    <div>
        {{-- Tab: Details --}}
        <div x-show="tab === 'details'" x-cloak class="space-y-6">
            <!-- Algemene gegevens in een grid -->
          <div class="bg-white shadow rounded p-6">
    <!-- Factuurgegevens in een grid -->
    <div class="table w-full">
        <div class="table-row">
            <div class="table-cell">
                <span class="font-semibold">Referentie intern</span>
                <span>{{ $invoice->invoice_number }}</span>
            </div>
            <div class="table-cell">
                <span class="font-semibold">Referentie extern:</span>
                <span>{{ $invoice->invoice_reference }}</span>
            </div>
        </div>
        <div class="table-row">
            <div class="table-cell">
                <span class="font-semibold">Datum:</span>
                <span>{{ $invoice->date->format('d-m-Y') }}</span>
            </div>
            <div class="table-cell">
                <span class="font-semibold">Vervaldatum:</span>
                <span>{{ $invoice->date->format('d-m-Y') }}</span>
            </div>
        </div>
        <div class="table-row">
            <div class="table-cell">
                <span class="font-semibold">Type:</span>
                <span>{{ ucfirst($invoice->type) }}</span>
            </div>
            <div class="table-cell">
                <span class="font-semibold">Status:</span>
                <span>{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>
        <div class="table-row">
            <div class="table-cell">
                <span class="font-semibold">Leverancier:</span>
                <span>{{ $invoice->supplier->name ?? '-' }}</span>
            </div>
            <div class="table-cell">
                <span class="font-semibold">Naam:</span>
                <span>{{ $invoice->name ?? '-' }}</span>
            </div>
        </div>
        <div class="table-row">
            <div class="table-cell">
                <span class="font-semibold">Inkooporder:</span>
                <span>{{ $invoice->purchase_order_id ?? '-' }}</span>
            </div>
            </div>
            </div>

    </div>

    <!-- Totaal overzicht -->
    <div class="mt-6 border-t pt-4">
        <div class="flex justify-between items-center">
            <span class="inline-grid w-full text-right grid-cols-2 gap-3">
                <span>
                    <span class="font-semibold text-lg">Totaalbedrag exclusief:</span>
                    <span class="text-xl font-bold text-blue-600">
                        € {{ number_format($invoice->invoiceLines->sum('amount_excl_vat_total'), 2) }}
                    </span>
                </span>
                <span>
                    <span class="font-semibold text-lg">Totaalbedrag BTW:</span>
                    <span class="text-xl font-bold text-blue-600">
                        € {{ number_format($invoice->invoiceLines->sum('total_vat'), 2) }}
                    </span>
                </span>
                <span>
                </span>
                <span>
                    <span class="font-semibold text-lg">Totaalbedrag inclusief:</span>
                    <span class="text-xl font-bold text-blue-600">
                        € {{ number_format($invoice->invoiceLines->sum('amount_incl_vat_total'), 2) }}
                    </span>
                </span>
            </span>

        </div>
    </div>

            <!-- Factuurregels -->
            <div>
                <h2 class="font-semibold text-lg mb-2">Factuurregels</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-left">Omschrijving</th>
                                <th class="px-4 py-2 text-right">Aantal</th>
                                <th class="px-4 py-2 text-right">Stuks prijs (excl)</th>
                                <th class="px-4 py-2 text-right">BTW</th>
                                <th class="px-4 py-2 text-right">Totaal (excl)</th>
                                <th class="px-4 py-2 text-right">BTW totaal</th>
                                <th class="px-4 py-2 text-right">Totaal (incl)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->invoiceLines as $line)
                            @php
                                $unitPriceIncl = $line->amount_incl_vat_total/$line->quantity;
                                $unitPriceExcl = $line->amount_excl_vat_total/$line->quantity;

                            @endphp
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $line->product->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $line->description }}</td>
                                    <td class="px-4 py-2 text-right">{{ $line->quantity }}</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format(($unitPriceExcl), 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">{{ (100*$line->vat_rate) }}%</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($line->amount_excl_vat_total, 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($line->total_vat, 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($line->amount_incl_vat_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                                <tr class="border-t font-bold">
                                    <td class="px-4 py-2"></td>
                                    <td class="px-4 py-2"></td>
                                    <td class="px-4 py-2 text-right">{{$invoice->invoiceLines->sum('quantity')}}</td>
                                    <td class="px-4 py-2 text-right"></td>
                                    <td class="px-4 py-2 text-right"></td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($invoice->invoiceLines->sum('amount_excl_vat_total'), 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($invoice->invoiceLines->sum('total_vat'), 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">€ {{ number_format($invoice->invoiceLines->sum('amount_incl_vat_total'), 2, ',', '.') }}</td>
                                </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Bijlagen --}}
        <div x-show="tab === 'files'" x-cloak class="space-y-4">
            <h2 class="font-semibold text-lg">Bijlagen</h2>
            <ul class="list-disc pl-5 text-sm">
                @php
                    $documents = is_array($invoice->linking_documents) ? $invoice->linking_documents : json_decode($invoice->linking_documents, true);
                @endphp
                @if(is_array($documents))
                @forelse ($documents as $doc)
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
                @else
                    <li class="text-gray-500">Geen bijlagen</li>

                @endif
            </ul>
        </div>

        {{-- Tab: Auditlog --}}
        <div x-show="tab === 'logs'" x-cloak class="space-y-4">
            <h2 class="font-semibold text-lg">Wijzigingsgeschiedenis</h2>
            <div class="space-y-2 text-sm">
                @forelse ($invoice->activities as $log)
                    <div class="border-l-4 border-blue-500 pl-3 py-1 bg-blue-50">
                        <div>
                            <span class="font-semibold">{{ $log->created_at->format('d-m-Y H:i') }}</span> – 
                            {{ $log->description }}
                        </div>
                        <div class="text-gray-600">Door: {{ $log->causer->name ?? 'Onbekend' }}</div>
                    </div>
                @empty
                    <p class="text-gray-500">Geen logs beschikbaar.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Zorg dat Alpine.js goed is geladen, dit voorbeeld gaat ervan uit dat Alpine via CDN wordt ingeladen in je layout.
    feather.replace();
</script>
@endsection

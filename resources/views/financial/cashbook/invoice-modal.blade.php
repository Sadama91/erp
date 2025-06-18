<div class="p-4">
    <h3 class="text-lg font-bold">Factuurgegevens</h3>
    <p><strong>Factuurnummer:</strong> {{ $invoice->invoice_number ?? 'Niet beschikbaar' }}</p>
    <p><strong>Datum:</strong> {{ $invoice->date ?? 'Niet beschikbaar' }}</p>
    <p><strong>Bedrag:</strong> €{{ number_format($invoice->amount, 2) ?? 'Niet beschikbaar' }}</p>
    @if(isset($invoice->customer))
        <p><strong>Klant:</strong> {{ $invoice->customer->name }}</p>
    @endif

    <hr class="my-4">

    @if($invoice->file)
        <div class="mb-4">
            <a href="{{ route('factuur.download', $invoice->id) }}" target="_blank" class="px-4 py-2 bg-blue-500 text-white rounded">
                Download Factuur
            </a>
        </div>
        <div>
            <embed src="{{ asset('storage/invoices/' . $invoice->file) }}" type="application/pdf" width="100%" height="400px">
        </div>
    @else
        <p>Geen factuurbestand beschikbaar.</p>
    @endif
</div>

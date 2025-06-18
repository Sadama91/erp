@extends('layouts.app')

@section('page_title', 'Financiële Transacties')

@section('content')
    <div class="container mx-auto p-4">

        <!-- Filtersectie -->
        <div class="bg-white shadow-md rounded p-4 mb-6">
            <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap gap-4" id="transactionFilterForm">
              
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Van:</label>
                    <input type="date" name="date_from" value="{{ old('date_from', $dateFrom ?? '') }}" class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Tot:</label>
                    <input type="date" name="date_to" value="{{ old('date_to', $dateTo ?? '') }}" class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label for="direction" class="block text-sm font-medium text-gray-700">Type:</label>
                    <select name="direction" class="border rounded px-3 py-2 w-full">
                        <option value="">Alle</option>
                        <option value="in" {{ request('direction') == 'in' ? 'selected' : '' }}>In</option>
                        <option value="out" {{ request('direction') == 'out' ? 'selected' : '' }}>Uit</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
                </div>
            </form>
        </div>

        <!-- Overzicht van totale bedragen en download knop -->
        @if($transactions->count() > 0)
            <div class="bg-gray-900 text-white shadow-md rounded p-6 mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-semibold">Samenvatting</h3>
                    <p class="text-green-400 text-lg font-bold">Totaal In: €{{ number_format($totalIn, 2) }}</p>
                    <p class="text-red-400 text-lg font-bold">Totaal Uit: €{{ number_format($totalOut, 2) }}</p>
                </div>
                <div>
                    <form method="GET" action="{{ route('transactions.download') }}">
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                        <button type="submit" class="bg-green-500 text-white px-4 py-3 rounded hover:bg-green-700">Download CSV</button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Transacties tabel -->
        <div class="overflow-x-auto bg-white shadow-md rounded">
            <table class="min-w-full border-collapse w-full">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border px-4 py-2">Datum</th>
                        <th class="border px-4 py-2">Rekening</th>
                        <th class="border px-4 py-2">Bedrag</th>
                        <th class="border px-4 py-2">Type</th>
                        <th class="border px-4 py-2">Status</th>
                        <th class="border px-4 py-2">Betreft</th>
                        <th class="border px-4 py-2">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="border-b">
                            <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d F Y') }}</td>
                            <td class="border px-4 py-2">{{ $transaction->account->name }}</td>
                            <td class="border px-4 py-2 font-bold">€{{ number_format($transaction->amount, 2) }}</td>
                            <td class="border px-4 py-2">{{ ucfirst($transaction->type) }}</td>
                            <td class="border px-4 py-2">{{ ucfirst($transaction->status) }}</td>
                            <td class="border px-4 py-2">
                                @if(isset($transaction->reference['table']) && isset($transaction->reference['key']))
                                    {{ $transaction->reference['table'] }}: {{ $transaction->reference['key'] }}
                                @else
                                    N.v.t.
                                @endif
                            </td>
                            <td class="border px-4 py-2">
                                @if($transaction->status === 'pending')
                                    <form method="POST" action="{{ route('transactions.finalize', $transaction->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-700">Definitief maken</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Geen transacties gevonden</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginering -->
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
@endsection

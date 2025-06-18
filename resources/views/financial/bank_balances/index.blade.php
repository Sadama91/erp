@extends('layouts.app')

@section('page_title', 'Bank Saldi')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" data-feather="dollar-sign"></svg>
        Bank Saldi
    </h1>

    <div class="bg-white rounded shadow p-4 mb-6">
        <h2 class="font-semibold text-lg mb-2">Saldo per Rekening</h2>
        <table class="min-w-full bg-white">
            <thead class="text-xs font-semibold bg-gray-100 border-b">
                <tr>
                    <th class="px-4 py-2 text-left">Rekening</th>
                    <th class="px-4 py-2 text-left">Peildatum</th>
                    <th class="px-4 py-2 text-right">Saldo</th>
                    <th class="px-4 py-2 text-left">Bron</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr class="border-b text-sm">
                        <td class="px-4 py-2">{{ $balance->account->code }} - {{ $balance->account->name }}</td>
                        <td class="px-4 py-2">{{ $balance->date->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 text-right">&euro; {{ number_format($balance->amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ $balance->source ?? '-' }}</td>
                        <td class="px-4 py-2 text-right">
                            <!-- Mogelijkheid voor bewerken/verwijderen -->
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-2 text-gray-500">Geen bankbalansen beschikbaar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold text-lg mb-2">Voer nieuw banksaldo in</h2>
        <form method="POST" action="{{ route('bank_balances.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Rekening</label>
                    <select name="account_id" class="w-full border-gray-300 rounded">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                {{ $account->code }} - {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Datum</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="w-full border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Saldo (&euro;)</label>
                    <input type="number" name="amount" step="0.01" class="w-full border-gray-300 rounded" value="{{ old('amount') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Bron (optioneel)</label>
                    <input type="text" name="source" value="{{ old('source') }}" class="w-full border-gray-300 rounded">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Opmerkingen (optioneel)</label>
                <textarea name="remarks" rows="2" class="w-full border-gray-300 rounded">{{ old('remarks') }}</textarea>
            </div>
            <div class="pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" data-feather="save"></svg>
                    Opslaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endsection

@extends('layouts.app')

@section('page_title', 'Balans')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6 flex items-center">
        <svg class="w-6 h-6 mr-2 text-blue-600" data-feather="bar-chart-2"></svg>
        Balansoverzicht
    </h1>

    <form method="GET" class="flex items-center gap-2 mb-6">
        <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="border border-gray-300 rounded px-3 py-1 text-sm">
        <button type="submit" class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
            <svg class="w-4 h-4 mr-1" data-feather="refresh-cw"></svg>
            Bijwerken
        </button>
        <a href="{{ route('balance_sheet.export', ['date' => $date]) }}" class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
            <svg class="w-4 h-4 mr-1" data-feather="download"></svg>
            Exporteren
        </a>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 p-4 rounded shadow">
            <h3 class="font-semibold text-lg mb-2">Samenvatting</h3>
            <p><strong>Totale Activa & Kosten:</strong> &euro; {{ number_format($totalAssets, 2, ',', '.') }}</p>
            <p><strong>Totale Passiva, Omzet & Eigen Vermogen:</strong> &euro; {{ number_format($totalLiabilities, 2, ',', '.') }}</p>
        </div>

        <div class="bg-gray-50 p-4 rounded shadow">
            <h3 class="font-semibold text-lg mb-2 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-500" data-feather="credit-card"></svg>
                Controle Rekeningsaldo
            </h3>
            <p><strong>Boekhoudkundig banksaldo:</strong> &euro; {{ number_format($calculatedBankBalance, 2, ',', '.') }}</p>
            <p><strong>Werkelijk banksaldo:</strong> &euro; {{ $actualBankBalance !== null ? number_format($actualBankBalance, 2, ',', '.') : 'Niet beschikbaar' }}</p>
            <p><strong>Openstaand Vinted:</strong> &euro; {{ number_format($vintedBalance ?? 0, 2, ',', '.') }}</p>
            <p><strong>Openstaand overige ontvangsten:</strong> &euro; {{ number_format($toReceiveBalance ?? 0, 2, ',', '.') }}</p>
            <p class="mt-2 border-t pt-2">
                <strong>Verwachte banksaldo:</strong>
                &euro; {{ number_format($expectedBalance, 2, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-xs font-semibold">
                <tr>
                    <th class="px-4 py-2">Rekeningcode</th>
                    <th class="px-4 py-2">Naam</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $account)
                    <tr class="border-t">
                        <td class="px-4 py-2 text-gray-800">{{ $account->code }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $account->name }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ ucfirst($account->type) }}</td>
                        <td class="px-4 py-2 text-right">&euro; {{ number_format($account->balance, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-gray-500">Geen rekeningen gevonden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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

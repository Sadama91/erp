@extends('layouts.app')

@section('page_title', 'Kasboek Overzicht - ' . $year)

@section('content')
<div class="container mx-auto p-6 bg-white shadow-lg rounded-lg">

    <!-- Filter Form -->
    <form method="GET" class="flex space-x-4 mb-6">
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700">Jaar:</label>
            <select name="year" id="year" class="border rounded p-2">
                @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="month" class="block text-sm font-medium text-gray-700">Maand:</label>
            <select name="month" id="month" class="border rounded p-2">
                <option value="">Alle</option>
                @foreach([
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maart', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Augustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December'
                ] as $num => $name)
                    <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Filter</button>
    </form>

    <!-- Transacties -->
    <table class="w-full border border-gray-300 shadow-md rounded-lg">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Datum</th>
                <th class="border p-2">Omschrijving</th>
                <th class="border p-2">Inkomsten (€)</th>
                <th class="border p-2">Uitgaven (€)</th>
                <th class="border p-2 text-center">Factuur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                    <td class="border p-2">{{ $transaction->created_at->format('d-m-Y') }}</td>
                    <td class="border p-2">{{ $transaction->description }}</td>
                    <td class="border p-2 text-green-600 font-bold">{{ $transaction->income ? number_format($transaction->income, 2) : '-' }}</td>
                    <td class="border p-2 text-red-600 font-bold">{{ $transaction->expense ? number_format($transaction->expense, 2) : '-' }}</td>
                    <td class="border p-2 text-center">
                        @if(isset($transaction->invoice))
                            <button onclick="openInvoiceModal({{ $transaction->invoice->id }})">
                                <i class="fas fa-eye text-green-500 text-lg"></i>
                            </button>
                        @else
                            <i class="fas fa-eye text-gray-400 text-lg"></i>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totaaloverzicht -->
    <h3 class="text-lg font-bold mt-6">Totaaloverzicht</h3>
    <p class="text-xl font-bold">Saldo: €{{ number_format($totalBalance, 2) }}</p>
</div>
@endsection

@extends('layouts.app')

@section('page_title', 'BTW Overzicht')

@section('content')
    <div class="container mx-auto p-6 bg-white shadow-lg rounded-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">BTW Overzicht</h2>
        </div>

        <!-- Filter Form - Uitgerekt over de hele breedte -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <select name="year" class="border p-2 rounded w-full">
                <option value="">Alle jaren</option>
                @foreach($years as $availableYear)
                    <option value="{{ $availableYear }}" {{ $year == $availableYear ? 'selected' : '' }}>
                        {{ $availableYear }}
                    </option>
                @endforeach
            </select>
        
            <select name="month" class="border p-2 rounded w-full">
                <option value="">Alle maanden</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                @endfor
            </select>
        
            <select name="quarter" class="border p-2 rounded w-full">
                <option value="">Alle kwartalen</option>
                <option value="1" {{ $quarter == '1' ? 'selected' : '' }}>Q1 (Jan - Mrt)</option>
                <option value="2" {{ $quarter == '2' ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
                <option value="3" {{ $quarter == '3' ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
                <option value="4" {{ $quarter == '4' ? 'selected' : '' }}>Q4 (Okt - Dec)</option>
            </select>
        
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded w-full">Filter</button>
        </form>
        

        <!-- Totalen Weergave -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-100 p-4 rounded shadow">
                <h4 class="text-lg font-semibold">BTW Gerekend (Af te dragen)</h4>
                <p class="text-xl font-bold text-blue-600">&euro;{{ number_format($totalSalesVAT, 2) }}</p>
            </div>
            <div class="bg-green-100 p-4 rounded shadow">
                <h4 class="text-lg font-semibold">BTW Uit (Aftrekbaar)</h4>
                <p class="text-xl font-bold text-green-600">&euro;{{ number_format($totalDeductibleVAT, 2) }}</p>
            </div>
            <div class="bg-red-100 p-4 rounded shadow">
                <h4 class="text-lg font-semibold">BTW resultaat</h4>
                <p class="text-xl font-bold text-red-600">&euro;{{ number_format($btwTeBetalen, 2) }}</p>
            </div>
        </div>

        <!-- BTW Tabel -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 shadow-md rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">Transactie</th>
                        <th class="border p-2">Percentage</th>
                        <th class="border p-2">BTW Gerekend</th>
                        <th class="border p-2">BTW Betaald</th>
                        <th class="border p-2">Datum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxes as $tax)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                            <td class="border p-2">
                                <a href="{{ route('transactions.show', $tax->transaction_id) }}" class="text-blue-500 underline">
                                    Transactie #{{ $tax->transaction_id }}
                                </a>
                            </td>
                            
                            <td class="border p-2">{{ $tax->percentage }}%</td>
                            <td class="border p-2 text-blue-600">
                                @if(!$tax->is_deductible)
                                    &euro;{{ number_format($tax->amount, 2) }}
                                @endif
                            </td>
                            <td class="border p-2 text-green-600">
                                @if($tax->is_deductible)
                                    &euro;{{ number_format($tax->amount, 2) }}
                                @endif
                            </td>
                            <td class="border p-2">{{ $tax->created_at->format('d-m-Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-200 font-bold">
                    <tr>
                        <td class="border p-2 text-right" colspan="3">Totaal:</td>
                        <td class="border p-2 text-blue-600">&euro;{{ number_format($totalSalesVAT, 2) }}</td>
                        <td class="border p-2 text-green-600">&euro;{{ number_format($totalDeductibleVAT, 2) }}</td>
                        <td class="border p-2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Paginatie -->
        <div class="mt-4">
            {{ $taxes->links() }}
        </div>
    </div>
@endsection

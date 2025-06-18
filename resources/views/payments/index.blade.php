@extends('layouts.app')

@section('page_title', 'Betalingen Overzicht')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Betalingen</h1>
        <a href="{{ route('payments.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
            <i data-feather="plus" class="mr-2"></i> Nieuwe Betaling
        </a>
    </div>
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                    <th class="py-3 px-4 text-left">ID</th>
                    <th class="py-3 px-4 text-left">Datum</th>
                    <th class="py-3 px-4 text-left">Bedrag</th>
                    <th class="py-3 px-4 text-left">Type</th>
                    <th class="py-3 px-4 text-left">Methode</th>
                    <th class="py-3 px-4 text-left">Referentie</th>
                    <th class="py-3 px-4 text-center">Acties</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                @foreach($payments as $payment)
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="py-3 px-4">{{ $payment->id }}</td>
                    <td class="py-3 px-4">{{ $payment->date }}</td>
                    <td class="py-3 px-4">{{ $payment->amount }}</td>
                    <td class="py-3 px-4">{{ $payment->type }}</td>
                    <td class="py-3 px-4">{{ $payment->method }}</td>
                    <td class="py-3 px-4">{{ $payment->reference }}</td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('payments.show', $payment->id) }}" class="text-blue-500 hover:text-blue-700 inline-block mr-2" title="Bekijken">
                            <i data-feather="eye"></i>
                        </a>
                        <a href="{{ route('payments.edit', $payment->id) }}" class="text-yellow-500 hover:text-yellow-700 inline-block mr-2" title="Bewerken">
                            <i data-feather="edit"></i>
                        </a>
                        <form action="{{ route('payments.destroy', $payment->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Weet u zeker dat u deze betaling wilt verwijderen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700" title="Verwijderen">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    feather.replace();
</script>
@endsection

@extends('layouts.app')
@section('page_title', 'Boekingen')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-semibold">Boekingen</h1>
    <a href="{{ route('journal-entries.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow">
        <svg class="w-5 h-5 inline mr-1" data-feather="plus"></svg> Nieuwe boeking
    </a>
</div>

<table class="min-w-full bg-white shadow rounded">
    <thead>
        <tr class="text-left border-b">
            <th class="p-2">Datum</th>
            <th class="p-2">Omschrijving</th>
            <th class="p-2">Referentie</th>
            <th class="p-2">Type</th>
            <th class="p-2">Acties</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($entries as $entry)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">{{ $entry->date->format('d-m-Y') }}</td>
                <td class="p-2">{{ $entry->description }}</td>
                <td class="p-2">{{ $entry->reference }}</td>
                <td class="p-2 capitalize">{{ $entry->type }}</td>
                <td class="p-2 flex space-x-2">
                    <a href="{{ route('journal-entries.show', $entry) }}" class="text-blue-600">
                        <svg class="w-5 h-5" data-feather="eye"></svg>
                    </a>
                    <a href="{{ route('journal-entries.edit', $entry) }}" class="text-yellow-600">
                        <svg class="w-5 h-5" data-feather="edit"></svg>
                    </a>
                    <form method="POST" action="{{ route('journal-entries.destroy', $entry) }}" onsubmit="return confirm('Weet je zeker dat je deze boeking wilt verwijderen?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">
                            <svg class="w-5 h-5" data-feather="trash-2"></svg>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">
    {{ $entries->links() }}
</div>
@endsection

@section('scripts')
<script>feather.replace();</script>
@endsection


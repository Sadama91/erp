{{-- resources/views/chart_of_accounts/index.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Rekeningschema')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Rekeningschema</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('chart-of-accounts.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">
        Nieuwe Rekening
    </a>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ouder</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($accounts as $account)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $account->code }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $account->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($account->type) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $account->parent ? $account->parent->name : '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="{{ route('chart-of-accounts.show', $account->id) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white text-sm font-medium py-1 px-3 rounded">
                                Bekijk
                            </a>
                            <a href="{{ route('chart-of-accounts.edit', $account->id) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white text-sm font-medium py-1 px-3 rounded">
                                Bewerk
                            </a>
                            <form action="{{ route('chart-of-accounts.destroy', $account->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Weet je zeker dat je deze rekening wilt verwijderen?');" class="bg-red-500 hover:bg-red-700 text-white text-sm font-medium py-1 px-3 rounded">
                                    Verwijder
                                </button>
                            </form>
                        </td>
                    </tr>
                    {{-- Indien er subrekeningen zijn, toon deze --}}
                    @if($account->children->count())
                        @foreach($account->children as $child)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap pl-10">{{ $child->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap pl-10">{{ $child->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($child->type) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $child->parent ? $child->parent->name : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('chart-of-accounts.show', $child->id) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white text-sm font-medium py-1 px-3 rounded">
                                        Bekijk
                                    </a>
                                    <a href="{{ route('chart-of-accounts.edit', $child->id) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white text-sm font-medium py-1 px-3 rounded">
                                        Bewerk
                                    </a>
                                    <form action="{{ route('chart-of-accounts.destroy', $child->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Weet je zeker dat je deze rekening wilt verwijderen?');" class="bg-red-500 hover:bg-red-700 text-white text-sm font-medium py-1 px-3 rounded">
                                            Verwijder
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

{{-- resources/views/chart_of_accounts/show.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Rekening Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Rekening Details</h1>

    <div class="bg-white shadow rounded p-6 mb-6">
        <h4 class="text-2xl font-bold">{{ $account->name }} ({{ $account->code }})</h4>
        <p class="mt-2"><strong>Type:</strong> {{ ucfirst($account->type) }}</p>
        <p class="mt-2"><strong>Ouderrekening:</strong> {{ $account->parent ? $account->parent->name : 'Geen' }}</p>
    </div>

    @if($account->children->count())
    <div class="mb-6">
        <h3 class="text-xl font-semibold mb-4">Subrekeningen</h3>
        <ul class="bg-white shadow rounded divide-y divide-gray-200">
            @foreach($account->children as $child)
                <li class="p-4 flex justify-between items-center">
                    <span>{{ $child->name }} ({{ $child->code }})</span>
                    <a href="{{ route('chart-of-accounts.show', $child->id) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white text-sm font-medium py-1 px-3 rounded">
                        Bekijk
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <a href="{{ route('chart-of-accounts.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
        Terug naar overzicht
    </a>
</div>
@endsection

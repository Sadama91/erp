{{-- resources/views/chart_of_accounts/create.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Nieuwe Rekening Aanmaken')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Nieuwe Rekening Aanmaken</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chart-of-accounts.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label for="code" class="block text-gray-700 font-bold mb-2">Code</label>
            <input type="text" name="code" class="w-full px-3 py-2 border rounded" value="{{ old('code') }}" required>
        </div>

        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-bold mb-2">Rekeningnaam</label>
            <input type="text" name="name" class="w-full px-3 py-2 border rounded" value="{{ old('name') }}" required>
        </div>

        <div class="mb-4">
            <label for="type" class="block text-gray-700 font-bold mb-2">Type</label>
            <select name="type" class="w-full px-3 py-2 border rounded" required>
                <option value="">Kies een type</option>
                <option value="asset" {{ old('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                <option value="liability" {{ old('type') == 'liability' ? 'selected' : '' }}>Liability</option>
                <option value="equity" {{ old('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                <option value="revenue" {{ old('type') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense</option>
            </select>
        </div>

        <div class="mb-4">
            <label for="parent_id" class="block text-gray-700 font-bold mb-2">Ouderrekening (optioneel)</label>
            <select name="parent_id" class="w-full px-3 py-2 border rounded">
                <option value="">Geen</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('parent_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Aanmaken
        </button>
    </form>
</div>
@endsection

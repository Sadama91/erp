@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 text-2xl font-bold">Nieuwe Parameter Toevoegen</h1>

    <form action="{{ route('parameters.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label for="key" class="block text-sm font-medium text-gray-700">Key</label>
            <select name="key" id="key" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="" disabled selected>Kies een key</option>
                @foreach($existingKeys as $existingKey)
                    <option value="{{ $existingKey }}">{{ $existingKey }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Naam</label>
            <input type="text" name="name" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <div class="mb-4">
            <label for="value" class="block text-sm font-medium text-gray-700">Waarde</label>
            <input type="text" name="value" id="value" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <button type="submit" class="btn btn-primary px-3 py-2 bg-indigo-600 text-white rounded-md">Toevoegen</button>
        <a href="{{ route('parameters.index') }}" class="btn btn-secondary px-3 py-2 bg-gray-600 text-white rounded-md ml-2">Annuleren</a>
    </form>
</div>
@endsection

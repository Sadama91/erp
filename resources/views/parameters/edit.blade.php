@extends('layouts.app')

@section('page_title', 'Parameter bewerken: ' . $parameter->key )
@section('content')
<div class="container">

    <form action="{{ route('parameters.update', $parameter->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="key" class="block text-sm font-medium text-gray-700">Key: {{ $parameter->key }}</label>
            <input type="hidden" name="key" id="key" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" readonly>
            
        </div>
        <div class="mb-4">
            <label for="value" class="block text-sm font-medium text-gray-700">Waarde</label>
            <input type="text" name="value" id="value" value="{{ $parameter->value }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Naam</label>
            <input type="text" name="name" id="name" value="{{ $parameter->name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <button type="submit" class="btn btn-primary px-3 py-2 bg-indigo-600 text-white rounded-md">Bijwerken</button>
        <a href="{{ route('parameters.index') }}" class="btn btn-secondary px-3 py-2 bg-gray-600 text-white rounded-md ml-2">Annuleren</a>
    </form>
</div>
@endsection

@extends('layouts.app')
@section('page_title', 'Nieuwe Merk Toevoegen')

@section('content')
<div class="container mx-auto mt-6">
    <h1 class="text-2xl font-semibold mb-4">Nieuwe Merk Toevoegen</h1>

    <form method="POST" action="{{ route('brands.store') }}" class="bg-white shadow-md rounded-lg p-6">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Naam</label>
            <input 
                type="text" 
                name="name" 
                id="name" 
                value="{{ old('name') }}" 
                required 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:outline-none" 
            />
        </div>

        <div class="mb-4">
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input 
                type="text" 
                name="slug" 
                id="slug" 
                value="{{ old('slug') }}" 
                required 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200 focus:outline-none" 
            />
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
                Opslaan
            </button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.app')
@section('page_title', 'Subgroep bewerken: ' . $subgroup->name)

@section('content')
<div class="container mx-auto mt-6">
    <div class="flex justify-between items-center mb-4">
        <!-- Terug-knop -->
        <a href="{{ route('subgroups.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg shadow hover:bg-gray-200 transition duration-200 ml-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="font-medium">Terug</span>
        </a>
    </div>
    

    <form action="{{ route('subgroups.update', $subgroup->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700">Naam</label>
            <input type="text" name="name" id="name" value="{{ old('name', $subgroup->name) }}" class="border rounded-lg w-full p-2" required>
        </div>

        <div class="mb-4">
            <label for="slug" class="block text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $subgroup->slug) }}" class="border rounded-lg w-full p-2" required>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2 transition duration-200">
            Opslaan
        </button>
    </form>
</div>
@endsection

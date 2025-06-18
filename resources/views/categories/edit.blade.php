@extends('layouts.app')

@section('page_title', 'Categorie Bewerken')

@section('content')
<div class="container mx-auto mt-6">

    <a href="{{ route('categories.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        &larr; Terug naar Categorieën
    </a>

    <h1 class="text-xl font-semibold mb-4">Categorie Bewerken</h1>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Er zijn fouten opgetreden!</strong>
            <ul class="mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('categories.update', $category->id) }}" class="bg-white p-4 rounded shadow">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700">Naam</label>
            <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <div class="mb-4">
            <label for="slug" class="block text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}" required class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition duration-200">
            Bijwerken
        </button>
    </form>
</div>
@endsection

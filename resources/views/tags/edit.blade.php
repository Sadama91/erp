@extends('layouts.app')

@section('page_title', 'Bewerk Tag')

@section('content')
<div class="container mx-auto mt-6">
    <h1 class="text-2xl font-bold mb-4">Bewerk Tag</h1>

    <a href="{{ route('tags.index') }}" class="text-blue-600 hover:text-blue-900 mb-4 inline-block">← Terug naar Tags</a>

    <form action="{{ route('tags.update', $tag->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-gray-700">Naam</label>
            <input type="text" name="name" id="name" value="{{ old('name', $tag->name) }}" class="border rounded-lg w-full p-2" required>
            @error('name')
                <div class="text-red-500">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="slug" class="block text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $tag->slug) }}" class="border rounded-lg w-full p-2" required>
            @error('slug')
                <div class="text-red-500">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2 transition duration-200">
            Opslaan
        </button>
    </form>
</div>
@endsection

@extends('layouts.app')
@section('page_title', 'Product bekijken: ' . $product->name)

@section('content')
<div class="container">
    <h1>Afbeeldingen voor {{ $product->name }}</h1>

    {{-- Basis productinformatie --}}
    <div class="bg-white shadow-md rounded-lg p-4 mb-4">
        <h2 class="text-lg font-bold">{{ $product->name }}</h2>
        <table style="w-1/2">
            <tr>
                <td><strong>Artikelnummer:</strong></td><td> {{ $product->sku }}</td>
                <td><strong>Status:</strong></td><td>{{ $product->status }} </td>
            </tr>
            <tr>
                <td><strong>Merk:</strong></td><td> {{ $product->brand->name }}</td>
                <td><strong>Subgroep:</strong></td><td> {{ $product->subgroup->name }}</td>
            </tr>
            <tr>
                <td><strong>Prijs:</strong></td><td> €{{ number_format($product->regularPrice->price ?? 0, 2) }}</td>
                <td><strong>SKU:</strong></td><td> {{ $product->sku }}</td>
            </tr>
        </table>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($product->imageLinks as $imageLink)
            <div class="bg-white shadow-md rounded-lg p-4">
                @if($imageLink->image)
                    <img src="{{ asset('storage/' . $imageLink->image->location) }}" alt="{{ $imageLink->image->description }}" class="w-full h-32 object-contain mb-2">
                    
                    <a href="{{ asset('storage/' . $imageLink->image->location) }}" class="text-blue-500 underline" target="_blank">Bekijk hoge resolutie</a>
                @else
                    <p class="text-gray-500">Geen afbeelding beschikbaar.</p>
                @endif
                <p><strong>Rol:</strong> {{ ucfirst($imageLink->role) }}</p>
                <p><strong>Publicatie:</strong> {{ ucfirst($imageLink->publication) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Navigatie terug naar product en index --}}
    <div class="mt-4">
        <a href="{{ route('products.show', $product->id) }}" class="text-blue-500 underline">Terug naar product</a>
        <span class="mx-2">|</span>
        <a href="{{ route('products.index') }}" class="text-blue-500 underline">Terug naar index</a>
    </div>
</div>
@endsection

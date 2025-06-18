@extends('layouts.app')

@section('page_title', 'Overzicht Afbeeldingen')

@section('content')
    <div class="container">
        <!-- Succes- en foutmeldingen -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @elseif (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulier voor CSV Uploaden -->
        <div class="mb-4">
            <h4>Upload een CSV-bestand voor Afbeeldingen</h4>
            <form action="{{ route('image.uploadCSV') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <input type="file" class="form-control" name="csv_file" accept=".csv, .txt" required>
                    <button class="btn btn-primary bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-yellow-400 transition duration-300" type="submit">Upload CSV</button>
                </div>
            </form>
        </div>

   <!-- Filtersectie voor afbeeldingen -->
   <div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form action="{{ route('image.index') }}" method="GET" class="grid grid-cols-4 gap-4">

        <!-- Merk Filter -->
        <div class="col-span-1">
            <label for="brand" class="block text-sm font-medium text-gray-700">Merken</label>
            <select id="brand" name="brand" multiple class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                <option value="">Alle merken</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ request()->get('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Zoekbalk voor naam, SKU, merk, en tags -->
        <div class="col-span-2">
            <label for="search" class="block text-sm font-medium text-gray-700">Zoek op Naam, SKU, Merk of Tags</label>
            <input type="text" id="search" name="search" value="{{ request()->get('search') }}" placeholder="Zoek op naam, SKU, merk of tags..." class="w-full form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
        </div>

        <!-- Paginaweergave -->
        <div class="col-span-1">
            <label for="per_page" class="block text-sm font-medium text-gray-700">Aantal weer te geven</label>
            <select id="per_page" name="per_page" class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 w-16">
                <option value="25" {{ request()->get('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request()->get('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request()->get('per_page') == 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ request()->get('per_page') == 200 ? 'selected' : '' }}>200</option>
            </select>
        </div>

        <!-- Filteren button -->
        <div class="col-span-1 md:col-span-3 flex items-end">
            <button type="submit" class="btn btn-secondary bg-indigo-600 text-white rounded-md shadow-md hover:bg-indigo-700 w-full py-2">
                Filteren
            </button>
        </div>
    </form>
</div>



        <!-- Tabel met afbeeldingen en gekoppelde producten -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Afbeelding</th>
                    <th>Gekoppelde Producten</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
    @foreach ($images as $image)
        <tr>
            <td>
                <!-- Toon de thumbnail afbeelding of, als deze niet beschikbaar is, de grote afbeelding -->
                @if ($image->thumbnail_location)
                    <a href="{{ route('image.show', $image->id) }}" target="_blank">
                        <img src="{{ $image->thumbnail_location }}" alt="Afbeelding" style="max-width: 100px; max-height: 100px;">
                    </a>
                @else
                    <a href="{{ $image->location }}" target="_blank">
                        <img src="{{ route('image.show', $image->id) }}" alt="Afbeelding" style="max-width: 150px; max-height: 150px;">
                    </a>
                @endif
            </td>
            <td>
                <!-- Toon de gelinkte producten zonder duplicaten -->
                @foreach ($image->imageLinks->unique('product_id') as $link)
                    <span class="badge bg-primary">
                        <a href="{{ route('products.show', $link->product->id) }}" target="_blank">
                            {{ $link->product->sku }} - {{ $link->product->brand->name }} - {{ $link->product->name }}
                        </a>
                    </span>
                @endforeach
            </td>
            <td>
                <a href="{{ route('image.show', $image->id) }}" class="btn btn-info btn-sm">Bekijken</a>
            </td>
        </tr>
    @endforeach
</tbody>

        </table>
        <!-- Paginering -->
        {{ $images->links() }}
    </div>
@endsection

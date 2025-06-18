@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-6">
    <h2 class="text-2xl font-bold mb-4">Bulk Merken Bewerken</h2>

    <form action="{{ route('brands.bulk-update') }}" method="POST">
        @csrf
        <div class="bg-white shadow rounded-lg p-6">
            @foreach ($brands as $brand)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Naam (ID: {{ $brand->id }})</label>
                    <input type="text" name="brands[{{ $loop->index }}][name]" value="{{ old('brands.'.$loop->index.'.name', $brand->name) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                    <input type="hidden" name="brands[{{ $loop->index }}][id]" value="{{ $brand->id }}" />
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input type="text" name="brands[{{ $loop->index }}][slug]" value="{{ old('brands.'.$loop->index.'.slug', $brand->slug) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
            @endforeach
            <div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Opslaan</button>
            </div>
        </div>
    </form>
</div>
@endsection

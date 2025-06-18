@extends('layouts.app')

@section('page_title', 'Handmatige weborder inlezen')

@section('content')
<div class="max-w-2xl mx-auto p-4 bg-white rounded shadow">
    <h1 class="text-xl font-bold mb-4">Importeer WooCommerce JSON</h1>

    @if($errors->any())
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('orders.manualSubmit') }}">
        @csrf
        <label class="block mb-2 font-semibold">Plak hier de JSON van WooCommerce:</label>
        <textarea name="json" rows="20" class="w-full border rounded p-2 font-mono text-sm" required>{{ old('json') }}</textarea>
        <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Verwerk</button>
    </form>
</div>
@endsection

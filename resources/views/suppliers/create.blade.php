@extends('layouts.app')

@section('page_title', 'Leverancier aanmaken')

@section('content')
<div class="max-w-5xl mx-auto p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('suppliers.index') }}" class="flex items-center text-sm text-gray-600 hover:underline">
            <svg class="w-5 h-5 mr-1" data-feather="arrow-left"></svg> Terug
        </a>
        <h1 class="text-2xl font-bold">Leverancier aanmaken</h1>
    </div>

    <!-- Formulier -->
    <div class="bg-white shadow rounded p-6">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf

            <!-- Naam en Status -->
            <div class="grid grid-cols-3 gap-6 mb-6">
                <div class="col-span-2">
                    <label for="name" class="block font-medium text-gray-700">Naam</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="status" class="block font-medium text-gray-700">Status</label>
                    <select name="status" id="status" 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Actief</option>
                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactief</option>
                    </select>
                </div>
            </div>

            <!-- Telefoon, Website, Contact Info -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="telephone" class="block font-medium text-gray-700">Telefoonnummer</label>
                    <input type="text" name="telephone" id="telephone" value="{{ old('telephone') }}" 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <label for="website" class="block font-medium text-gray-700 mt-4">Website</label>
                    <input type="url" name="website" id="website" value="{{ old('website') }}" 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <label for="purchase_via" class="block font-medium text-gray-700 mt-4">Inkoop Via</label>
                    <input type="text" name="purchase_via" id="purchase_via" value="{{ old('purchase_via') }}" 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="contact_info" class="block font-medium text-gray-700">Contact Info</label>
                    <textarea name="contact_info" id="contact_info" rows="8"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('contact_info') }}</textarea>
                </div>
            </div>

            <!-- Betaaltermijn, Voorwaarden en Opmerkingen -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="payment_days" class="block font-medium text-gray-700">Betaaltermijn (dagen)</label>
                    <input type="text" name="payment_days" id="payment_days" value="{{ old('payment_days', 30) }}" 
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <label for="terms" class="block font-medium text-gray-700 mt-4">Voorwaarden</label>
                    <textarea name="terms" id="terms" rows="6"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('terms') }}</textarea>
                </div>
                <div>
                    <label for="remarks" class="block font-medium text-gray-700">Opmerkingen</label>
                    <textarea name="remarks" id="remarks" rows="10"
                        class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" 
                    class="w-full py-2 px-4 bg-indigo-600 text-white rounded shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Aanmaken
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    feather.replace();
</script>
@endsection

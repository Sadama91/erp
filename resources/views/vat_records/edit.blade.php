@extends('layouts.app')

@section('page_title', 'VATRecord Bewerken')


@section('content')
<div class="container mx-auto px-4 py-8">

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('vat_records.update', $vatRecord->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700">Recordable Type</label>
                <input type="text" name="recordable_type" value="{{ old('recordable_type', $vatRecord->recordable_type) }}" class="mt-1 block w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-gray-700">Recordable ID</label>
                <input type="number" name="recordable_id" value="{{ old('recordable_id', $vatRecord->recordable_id) }}" class="mt-1 block w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-gray-700">Account ID</label>
                <input type="number" name="account_id" value="{{ old('account_id', $vatRecord->account_id) }}" class="mt-1 block w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-gray-700">Tax Rate</label>
                <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $vatRecord->tax_rate) }}" class="mt-1 block w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-gray-700">Tax Amount</label>
                <input type="number" step="0.01" name="tax_amount" value="{{ old('tax_amount', $vatRecord->tax_amount) }}" class="mt-1 block w-full border-gray-300 rounded">
            </div>
            <div>
                <label class="block text-gray-700">Category</label>
                <select name="category" class="mt-1 block w-full border-gray-300 rounded">
                    <option value="">Selecteer categorie</option>
                    <option value="sales" {{ old('category', $vatRecord->category) == 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="purchase" {{ old('category', $vatRecord->category) == 'purchase' ? 'selected' : '' }}>Purchase</option>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded inline-flex items-center">
                <i data-feather="save" class="mr-2"></i> Bijwerken
            </button>
            <a href="{{ route('vat_records.index') }}" class="ml-4 text-gray-700 inline-flex items-center">
                <i data-feather="arrow-left" class="mr-1"></i> Terug
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    feather.replace()
</script>
@endsection

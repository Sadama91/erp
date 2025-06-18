@extends('layouts.app')

@section('page_title','Overzicht VATRecords')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Overzicht VATRecords</h1>
        <a href="{{ route('vat_records.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded inline-flex items-center">
            <i data-feather="plus" class="mr-2"></i>
            Nieuw VATRecord
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Recordable Type</th>
                    <th class="py-2 px-4 border-b">Tax Rate</th>
                    <th class="py-2 px-4 border-b">Tax Amount</th>
                    <th class="py-2 px-4 border-b">Category</th>
                    <th class="py-2 px-4 border-b">Acties</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vatRecords as $record)
                    <tr class="hover:bg-gray-100">
                        <td class="py-2 px-4 border-b">{{ $record->id }}</td>
                        <td class="py-2 px-4 border-b">{{ $record->recordable_type }}</td>
                        <td class="py-2 px-4 border-b">{{ $record->tax_rate }}</td>
                        <td class="py-2 px-4 border-b">{{ $record->tax_amount }}</td>
                        <td class="py-2 px-4 border-b">{{ $record->category }}</td>
                        <td class="py-2 px-4 border-b">
                            <a href="{{ route('vat_records.show', $record->id) }}" class="text-blue-500 hover:text-blue-700 inline-flex items-center mr-2">
                                <i data-feather="eye" class="mr-1"></i> Bekijken
                            </a>
                            <a href="{{ route('vat_records.edit', $record->id) }}" class="text-yellow-500 hover:text-yellow-700 inline-flex items-center mr-2">
                                <i data-feather="edit" class="mr-1"></i> Bewerken
                            </a>
                            <form action="{{ route('vat_records.destroy', $record->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Weet je zeker dat je dit wilt verwijderen?')" class="text-red-500 hover:text-red-700 inline-flex items-center">
                                    <i data-feather="trash-2" class="mr-1"></i> Verwijderen
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    feather.replace()
</script>
@endsection

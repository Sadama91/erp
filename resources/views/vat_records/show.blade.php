@extends('layouts.app')

@section('page_title', 'VATRecord Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow rounded p-6">
        <div class="mb-4">
            <span class="font-semibold">ID:</span> {{ $vatRecord->id }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Recordable Type:</span> {{ $vatRecord->recordable_type }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Recordable ID:</span> {{ $vatRecord->recordable_id }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Account ID:</span> {{ $vatRecord->account_id }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Tax Rate:</span> {{ $vatRecord->tax_rate }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Tax Amount:</span> {{ $vatRecord->tax_amount }}
        </div>
        <div class="mb-4">
            <span class="font-semibold">Category:</span> {{ $vatRecord->category }}
        </div>
    </div>
    <div class="mt-6">
        <a href="{{ route('vat_records.index') }}" class="text-gray-700 inline-flex items-center">
            <i data-feather="arrow-left" class="mr-1"></i> Terug
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    feather.replace()
</script>
@endsection

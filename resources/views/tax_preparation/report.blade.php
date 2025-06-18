@extends('layouts.app')

@section('page_title', 'BTW-Aangifte Rapportage')

@section('content')
<div class="container mx-auto px-4 py-8">
     <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">Categorie</th>
                    <th class="py-2 px-4 border-b">Tax Rate</th>
                    <th class="py-2 px-4 border-b">Totaal BTW</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                    <tr class="hover:bg-gray-100">
                        <td class="py-2 px-4 border-b">{{ $data->category }}</td>
                        <td class="py-2 px-4 border-b">{{ $data->tax_rate }}</td>
                        <td class="py-2 px-4 border-b">{{ $data->total_tax }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
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

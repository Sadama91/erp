@extends('layouts.app')

@section('page_title', 'Balans Overzicht')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Balans Overzicht</h1>
    
    @foreach($balanceSheet as $section)
        <div class="mb-6 border rounded shadow bg-white p-4" x-data="{ open: false }">
            <div class="flex justify-between items-center cursor-pointer" @click="open = !open">
                <h2 class="text-xl font-semibold">{{ $section['title'] }}</h2>
                <button class="text-blue-600" type="button" x-text="open ? 'Verbergen' : 'Toon details'"></button>
            </div>
            <p class="text-2xl font-bold mt-2">€ {{ number_format((float)$section['total'], 2, ',', '.') }}</p>
            <div x-show="open" class="mt-4">
                @if(isset($section['details']) && is_array($section['details']))
                    <ul class="space-y-1">
                        @foreach($section['details'] as $detail)
                            <li class="text-sm text-gray-700">
                                <strong>{{ $detail['name'] }}:</strong>
                                € {{ number_format((float)($detail['amount']), 2, ',', '.') }}
                            </li>
                        @endforeach
                    </ul>
                @endif

            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection

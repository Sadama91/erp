@extends('layouts.app')

@section('page_title', 'Voorraad telling per locatie')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Voorraad telling opstarten</h1>


    <div class="overflow-hidden shadow rounded-lg">
        <div class="overflow-hidden shadow rounded-lg">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 text-left">Naam</th>
                        <th class="px-4 py-2 text-left">Key</th>
                        <th class="px-4 py-2 text-left">Gekoppelde Producten</th>
                        <th class="px-4 py-2 text-left">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $location)
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border-b border-gray-200 w-3/6">{{ $location->name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $location->value }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $location->linkedProductsCount }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 flex items-center">
                                <!-- Link naar productpagina met status 70 of lager -->
                                @if($location->lowStockProducts->isNotEmpty())
                                    <a href="{{ url('products?location[]=' . $location->value . '&search=&per_page=200') }}" title="Bekijk producten" class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="eye">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM12 10a2 2 0 100 4 2 2 0 000-4z"></path>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-gray-400" title="Geen producten met status 70 of lager">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="eye-off">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.003 17.003A9.978 9.978 0 0012 16a9.978 9.978 0 00-5.003 1.003M4.218 4.218l15.566 15.566M4.218 4.218a9.978 9.978 0 00-.172 1.103c0 1.43.293 2.781.846 4.014M12 4c-2.211 0-4.212.9-5.684 2.344M16 8a9.978 9.978 0 012.966 1.557M12 12c0 .51-.013 1.008-.038 1.5"></path>
                                        </svg>
                                    </span>
                                @endif<!-- Tellen Actie als link -->                               
                                 <a href="{{ route('inventory.countBatch', ['location' => $location->value]) }}" class="text-green-500 hover:underline" title="Tellen">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="list">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path>
                                    </svg>
                                    Tellen
                                </a>
                                
                                
                                
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </div>
    
</div>

@endsection


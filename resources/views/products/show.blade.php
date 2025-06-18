@extends('layouts.app')
@section('page_title', 'Product informatie: ' . $product->name)

@section('content')
<div class="container mx-auto py-6">
    <!-- Header -->
    <header class="space-y-2">
        <div class="flex space-x-4 mb-4">
            <a href="{{ url()->previous() }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-500 transition duration-300">
                &larr; Terug
            </a>
            <a href="{{ route('products.edit', $product->id) }}" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-500 transition duration-300">
                Bewerken
            </a>
            <button class="bg-yellow-500 text-white px-4 py-2 rounded shadow hover:bg-yellow-400 transition duration-300" onclick="openPriceUpdateModal()">
                Prijs bijwerken
            </button>
            <a href="#" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-500 transition duration-300">
                Voorraad aanpassen
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-800">@yield('page_title', 'Product informatie')</h1>
        <p class="text-gray-500">Overzicht van alle productgegevens</p>
    </header>
    
    <!-- Grid container voor de blokken -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
        <!-- Algemene Informatie -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-3xl font-semibold text-gray-800 mb-4">Algemene Informatie</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Naam:</dt>
                    <dd class="text-gray-900">{{ $product->name }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Artikelnummer:</dt>
                    <dd class="text-gray-900">{{ $product->sku }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Producttype:</dt>
                    <dd class="text-gray-900">{{ $product->product_type }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Merknaam:</dt>
                    <dd class="text-gray-900">{{ $product->brand->name ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Subgroep:</dt>
                    <dd class="text-gray-900">{{ $product->subgroup->name ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Categorie (primair):</dt>
                    <dd class="text-gray-900">{{ $product->category->name ?? '-' }}</dd>
                </div>
@php
    $productCategories = is_string($product->categories) ? json_decode($product->categories, true) : $product->categories;

@endphp

<div class="flex">
    <dt class="w-40 text-gray-500">Categorieën:</dt>
    <dd class="text-gray-900">
        @if(!empty($productCategories))
            @foreach($productCategories as $catId)
                {{ $categories[$catId] ?? '-' }}@if(!$loop->last), @endif
            @endforeach
        @else
            -
        @endif
    </dd>
</div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Tags:</dt>
                    <dd class="text-gray-900">
                        @if($product->tags->isNotEmpty())
                            @foreach($product->tags as $tag)
                                {{ $tag->name }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Status:</dt>
                    <dd class="text-gray-900">{{ $articleStatus ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">WooID:</dt>
                    <dd class="text-gray-900">
                        {{ $product->woo_id }} 
                        @if($product->woo_id)
                            - <a href="https://papierenversier.nl?p={{ $product->woo_id }}" target="_blank" class="text-blue-500 underline">website</a>
                        @endif
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Actief op web:</dt>
                    <dd class="text-gray-900">
                        @if($product->available_for_web && isset($product->woo_id))
                            Ja
                        @else
                            Nee
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
        
        <!-- Afbeeldingen -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Afbeeldingen</h2>
            @if(isset($images) && $images->isNotEmpty())
                <div class="Imgcontainer">
                    <div class="top">
                        <ul>
                            @foreach ($images as $index => $image)
                                <li>
                                    <a href="#img_{{ $image->id }}">
                                        <img src="{{ $image->location }}" alt="Afbeelding {{ $index + 1 }}">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    {{-- Lightbox voor elke afbeelding --}}
                    @foreach ($images as $index => $image)
                        <div id="img_{{ $image->id }}" class="lightbox">
                            <a href="#" class="close-btn">X</a>
                            <img src="{{ $image->location }}" alt="Afbeelding {{ $index + 1 }}">
                            <a href="{{ route('image.show', $image->id) }}" target="_blank" class="open-btn">Afbeelding openen</a>
                        </div>
                    @endforeach
                </div>
            @else
                <p>Geen afbeelding beschikbaar.</p>
            @endif
        </section>
        
        <!-- Voorraad & Beschikbaarheid -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Voorraad & Beschikbaarheid</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Huidige voorraad:</dt>
                    <dd class="text-gray-900">{{ $product->stock->current_quantity ?? '0' }} stuks</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Gereserveerde voorraad:</dt>
                    <dd class="text-gray-900">{{ $product->stock->reserved_quantity ?? '0' }} stuks</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Voorraad onderweg:</dt>
                    <dd class="text-gray-900">{{ $product->stock->on_the_way_quantity ?? '0' }} stuks</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Opslag locatie:</dt>
                    <dd class="text-gray-900">{{ $product->locationName ?? 'N/A' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Voorraad in weken:</dt>
                    <dd class="text-gray-900">{{ $product->stockInWeeks ?? 'N/A' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Eerst beschikbaar:</dt>
                    <dd class="text-gray-900">
                        @if($product->purchaseOrderItems->isNotEmpty())
                            {{ $product->purchaseOrderItems->first()->date }}
                        @else
                            N/A
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
        
        <!-- Inkoop & Leverancier -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Inkoop & Leverancier</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Leverancier:</dt>
                    <dd class="text-gray-900">{{ $product->supplier->name ?? 'N/A' }}</dd>
                </div>
                @php
                    $purchaseFactor = floor($product->purchase_quantity / $product->sale_quantity);
                @endphp
                <div class="flex">
                    <dt class="w-40 text-gray-500">Inhoud bulk:</dt>
                    <dd class="text-gray-900">{{ $purchaseFactor ?? '-' }}</dd>
                </div>

                  </dl>
                     
    <!-- Unit prijzen -->
    <h3 class="text-xl font-semibold mb-2">Per stuk</h3>
    <dl class="space-y-2">
        <div class="flex">
            <dt class="w-60 text-gray-500">Inkoopprijs per stuk (excl. BTW):</dt>
            <dd class="text-gray-900">
                @if(isset($product->purchase_price_excl))
                    € {{ number_format($product->purchase_price_excl, 2, ',', '.') }}
                @else
                    -
                @endif
            </dd>
        </div>
        <div class="flex">
            <dt class="w-60 text-gray-500">Inkoopprijs per stuk (incl. BTW):</dt>
            <dd class="text-gray-900">
                @if(isset($product->purchase_price_excl))
                    @php
                        // Gebruik het BTW-percentage van het product, of standaard 21%
                        $vat = $product->tax_percentage ?? 21;
                        $unitPriceIncl = $product->purchase_price_excl * (1 + $vat/100);
                    @endphp
                    € {{ number_format($unitPriceIncl, 2, ',', '.') }}
                @else
                    -
                @endif
            </dd>
        </div>
    </dl>
    
    <!-- Bulk prijzen: alleen tonen als inkoopeenheid of verkoopeenheid niet gelijk is aan 1 -->
    @if($product->purchase_quantity != 1 || $product->sale_quantity != 1)
        <h3 class="text-xl font-semibold mt-6 mb-2">Bulk prijzen</h3>
        <dl class="space-y-2">
            <div class="flex">
                <dt class="w-60 text-gray-500">Inkoopprijs bulk (excl. BTW):</dt>
                <dd class="text-gray-900">
                    @if(isset($product->purchase_bulk_price_excl))
                        € {{ number_format($product->purchase_bulk_price_excl, 2, ',', '.') }}
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div class="flex">
                <dt class="w-60 text-gray-500">Inkoopprijs bulk (incl. BTW):</dt>
                <dd class="text-gray-900">
                    @if(isset($product->purchase_bulk_price_excl))
                        @php
                            $vat = $product->tax_percentage ?? 21;
                            $bulkPriceIncl = $product->purchase_bulk_price_excl * (1 + $vat/100);
                        @endphp
                        € {{ number_format($bulkPriceIncl, 2, ',', '.') }}
                    @else
                        -
                    @endif
                </dd>
            </div>
        </dl>
    @endif
        </section>
        
        <!-- Verkoop & Omzet (overige gegevens) -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Verkoop & Omzet</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Totale omzet:</dt>
                    <dd class="text-gray-900">€ {{ number_format($product->totalRevenue, 2, ',', '.') }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Totale afzet:</dt>
                    <dd class="text-gray-900">{{ $product->totalSales }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Gemiddelde afzet:</dt>
                    <dd class="text-gray-900">{{ round($product->average_sales_per_week,1) }} stuks</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Gemiddelde verkoop/bestelling:</dt>
                    <dd class="text-gray-900">{{ round($product->average_sales_per_order,1) }} stuks</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Laatste verkoop op:</dt>
                    <dd class="text-gray-900">{{ \Carbon\Carbon::parse($product->last_sale_date)->format('Y-m-d') ?? 'N/A' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Verkoop kanalen:</dt>
                    <dd class="text-gray-900">
                        @if($sales_chanels->isNotEmpty())
                            @foreach($sales_chanels as $chanel)
                                {{ $chanel->name }}@if(!$loop->last), @endif
                            @endforeach
                        @else
                            Geen verkoopkanalen beschikbaar.
                        @endif
                    </dd>
                </div>
            </dl>
        </section>
        
        <!-- Verkoopprijzen & BTW -->
        <section class="bg-white shadow rounded-lg p-6 mt-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Verkoopprijzen & BTW</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Verkoopprijs regulier:</dt>
                    <dd class="text-gray-900">
                        € {{ number_format($product->regularPrice, 2, ',', '.') }}
                        ({{ $product->regularPriceMargin ?? '30%' }})
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Verkoopprijs vinted:</dt>
                    <dd class="text-gray-900">
                        € {{ number_format($product->vintedPrice, 2, ',', '.') }}
                        ({{ $product->vintedPriceMargin ?? '28%' }})
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">BTW percentage:</dt>
                    <dd class="text-gray-900">{{ $product->tax_percentage ?? '21%' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Laatste prijs mutatie:</dt>
                    <dd class="text-gray-900">
                        {{ \Carbon\Carbon::parse($product->last_price_update)->format('Y-m-d') ?? 'N/A' }}
                    </dd>
                </div>
            </dl>
        </section>
        
        <!-- Afmetingen & Verzendgegevens -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Afmetingen & Verzendgegevens</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Lengte:</dt>
                    <dd class="text-gray-900">{{ $product->depth ? $product->depth . ' CM' : '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Breedte:</dt>
                    <dd class="text-gray-900">{{ $product->width ? $product->width . ' CM' : '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Hoogte:</dt>
                    <dd class="text-gray-900">{{ $product->height ? $product->height . ' CM' : '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Gewicht:</dt>
                    <dd class="text-gray-900">{{ $product->weight ? $product->weight . ' Gram' : '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Verzendklasse:</dt>
                    <dd class="text-gray-900">{{ $product->shipping_class ?? 'onbekend' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Picklocatie:</dt>
                    <dd class="text-gray-900">{{ $product->locationName }} ({{ $product->location ?? 'geen' }})</dd>
                </div>
            </dl>
        </section>
        
        <!-- Extra Product Details -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Extra Product Details</h2>
            <dl class="space-y-2">
                 <div class="flex">
                    <dt class="w-40 text-gray-500">Attributes:</dt>
                    <dd class="text-gray-900">
                        @if(!empty($product->attributes))
                            {{ is_array($product->attributes) ? json_encode($product->attributes) : $product->attributes }}
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Bundled Items:</dt>
                    <dd class="text-gray-900">
                        @if(!empty($product->bundled_items))
                            {{ is_array($product->bundled_items) ? json_encode($product->bundled_items) : $product->bundled_items }}
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">To website:</dt>
                    <dd class="text-gray-900">{{ $product->to_website ? 'Ja' : 'Nee' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Back in stock:</dt>
                    <dd class="text-gray-900">{{ $product->back_in_stock ? 'Ja' : 'Nee' }}</dd>
                </div>
            </dl>
        </section>
        
        <!-- Informatie en bewerk knoppen -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Informatie en bewerk knoppen</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt>
                        <a href="{{ route('orders.index', ['product_id' => $product->id ]) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                            Toon bestellingen inclusief dit product.
                        </a>
                    </dt>
                </div>
            </dl>
        </section>
        
        <!-- Vinted beschrijvingen -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Vinted beschrijving</h2>
            <div class="space-y-4">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Titel:</dt>
                    <dd class="text-gray-900">{{ $product->vinted_title ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Beschrijving:</dt>
                    <dd class="text-gray-900">{{ $product->vinted_description ?? '-' }}</dd>
                </div>
            </div>
        </section>
        
        <!-- Beschrijving -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Beschrijving</h2>
            <div class="space-y-4">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Korte beschrijving:</dt>
                    <dd class="text-gray-900">{{ $product->short_description ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Lange beschrijving:</dt>
                    <dd class="text-gray-900">{{ $product->long_description ?? '-' }}</dd>
                </div>
            </div>
        </section>
        
        <!-- SEO & Web -->
        <section class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">SEO & Web</h2>
            <dl class="space-y-2">
                <div class="flex">
                    <dt class="w-40 text-gray-500">SEO focuswoord:</dt>
                    <dd class="text-gray-900">{{ $product->seo_focus_keyword ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">SEO titel:</dt>
                    <dd class="text-gray-900">{{ $product->seo_title ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">SEO omschrijving:</dt>
                    <dd class="text-gray-900">{{ $product->seo_description ?? '-' }}</dd>
                </div>
            </dl>
        </section>
        
        {{-- Prijs bijwerken modal --}}
        <div id="priceUpdateModal" class="hidden fixed inset-0 z-50 overflow-auto bg-gray-800 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-lg p-6 w-1/3">
                <span class="close cursor-pointer" onclick="closePriceUpdateModal()">&times;</span>
                <h2 class="text-xl font-bold mb-4 text-gray-800">Prijs bijwerken</h2>
                <form id="price-update-form" action="{{ route('prices.updateSingle') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="mb-4">
                        <label for="regular_price" class="block text-sm font-medium text-gray-700">Reguliere prijs</label>
                        <input type="number" step="0.01" name="prices[regular]" id="regular_price"
                            value="{{ old('prices.regular') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            required>
                        <p class="text-sm text-gray-500 mt-1">Huidige prijs: € {{ number_format($product->regularPrice, 2, ',', '.') }}</p>
                    </div>
                    <div class="mb-4">
                        <label for="vinted_price" class="block text-sm font-medium text-gray-700">Vinted prijs</label>
                        <input type="number" step="0.01" name="prices[vinted]" id="vinted_price"
                            value="{{ old('prices.vinted') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="text-sm text-gray-500 mt-1">Huidige prijs: € {{ number_format($product->vintedPrice, 2, ',', '.') }}</p>
                    </div>
                    <button type="submit"
                        class="bg-green-500 text-white px-4 py-2 rounded shadow hover:bg-green-400 transition duration-300">
                        Bijwerken
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const weeklyData = @json($weeklySalesData);
            const monthlyData = @json($monthlySalesData);
            const ctx = document.getElementById('salesChart').getContext('2d');
            let chart;
        
            function renderChart(range = 12, view = 'week') {
                const dataSource = view === 'week' ? weeklyData : monthlyData;
                const sliced = dataSource.slice(-range);
                const labels = sliced.map(item => item.period);
                const afzet = sliced.map(item => item.afzet);
                const omzet = sliced.map(item => item.omzet);
        
                const maxAfzet = Math.max(...afzet);
                const maxOmzet = Math.max(...omzet);
        
                const config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Afzet',
                                data: afzet,
                                yAxisID: 'y',
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderRadius: 4,
                            },
                            {
                                label: 'Omzet',
                                data: omzet,
                                type: 'line',
                                yAxisID: 'y1',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: Math.ceil(maxAfzet * 1.15),
                                title: { display: true, text: 'Afzet' }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                suggestedMax: Math.ceil(maxOmzet * 1.15),
                                title: { display: true, text: 'Omzet (€)' },
                                grid: { drawOnChartArea: false },
                                ticks: {
                                    callback: function(value) {
                                        return '€ ' + value.toFixed(2);
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if (context.dataset.label === 'Omzet') {
                                            return context.dataset.label + ': € ' + context.parsed.y.toFixed(2);
                                        }
                                        return context.dataset.label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        }
                    }
                };
        
                if (chart) chart.destroy();
                chart = new Chart(ctx, config);
            }
        
            const rangeSelect = document.getElementById('rangeSelect');
            const viewToggle = document.getElementById('viewToggle');
        
            rangeSelect.addEventListener('change', () => {
                renderChart(parseInt(rangeSelect.value), viewToggle.value);
            });
        
            viewToggle.addEventListener('change', () => {
                renderChart(parseInt(rangeSelect.value), viewToggle.value);
            });
        
            renderChart(12, 'week');
        });
    </script>
    <script>
        function openPriceUpdateModal() {
            document.getElementById('priceUpdateModal').classList.remove('hidden');
        }
        
        function closePriceUpdateModal() {
            document.getElementById('priceUpdateModal').classList.add('hidden');
        }
    </script>
@endsection

@section('style')
    <style>
        .Imgcontainer {
            width: 100%;
            height: 100%;
        }
        .top {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
            margin: 0 auto;
            justify-content: center;
        }
        .top ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            width: 100%;
            box-sizing: border-box;
        }
        .top ul li {
            width: 50%;
            padding: 5px;
        }
        .top ul li img {
            width: 100%;
            height: auto;
            cursor: pointer;
        }
        .lightbox {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .lightbox:target {
            display: flex; 
            pointer-events: auto;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 80%;
            position: relative;
            cursor: default;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            color: white;
            text-decoration: none;
            background-color: rgba(0, 0, 0, 0.6);
            padding: 10px;
            border-radius: 50%;
        }
        .close-btn:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
        .open-btn {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }
        .open-btn:hover {
            background-color: #45a049;
        }
    </style>
@endsection

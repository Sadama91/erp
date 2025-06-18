@props([
    'orders',
    'config',
    'statusOptions',
    'statusClasses',
    'salesChannelOptions',
    'shippingMethodOptions',
])

<div class="widget latest-orders-widget bg-white shadow rounded-lg p-4 mb-4 max-w-md">
    {{-- Header --}}
    <header class="mb-3 flex items-baseline justify-between">
        <h2 class="text-xl font-bold">
            Bestellingen
            <span class="text-gray-500 text-sm font-normal ml-2">
                {{ $config['order_chanel']
                    ? ($salesChannelOptions[$config['order_chanel']] ?? ucfirst($config['order_chanel']))
                    : 'Alle' }}
            </span>
        </h2>
        <span class="text-xs text-gray-600">Limiet: {{ $config['limit'] }}</span>
    </header>

    {{-- Filters --}}
    <div class="filter-bar text-xs text-gray-600 mb-3 space-y-1">
        <div>
            <strong>Kanaal:</strong>
            {{ $config['order_chanel']
                ? ($salesChannelOptions[$config['order_chanel']] ?? ucfirst($config['order_chanel']))
                : 'Alle' }}
        </div>
        <div>
            <strong>Status:</strong>
            @if(!empty($config['status']))
                @foreach($config['status'] as $s)
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $statusClasses[$s] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $statusOptions[$s] ?? 'Onbekend' }}
                    </span>
                @endforeach
            @else
                <span>Alle</span>
            @endif
        </div>
        <div>
            <strong>Methode:</strong>
            {{ $config['shipping_method']
                ? ($shippingMethodOptions[$config['shipping_method']] ?? ucfirst($config['shipping_method']))
                : 'Alle' }}
        </div>
    </div>

    {{-- Orders List --}}
    @if($orders->isEmpty())
        <p class="text-gray-500 text-sm">Geen bestellingen.</p>
    @else
        <ul class="divide-y divide-gray-200">
            @foreach($orders as $order)
                <li class="py-2 flex items-center justify-between">
                    {{-- Order Info --}}
                    <div class="order-info text-sm">
                        <span class="font-medium">#{{ $order->id }}</span>
                        <span class="text-gray-400">({{ $order->created_at->format('d-m-Y') }})</span>
                        <span class="ml-2">{{ $order->customer_name }}</span>
                    </div>

                    {{-- Status & Amount --}}
                    <div class="order-meta flex items-center space-x-2 text-sm">
                        <span class="px-2 py-0.5 rounded-full font-medium {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusOptions[$order->status] ?? 'Onbekend' }}
                        </span>
                        <span>€{{ number_format($order->total, 2, ',', '.') }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

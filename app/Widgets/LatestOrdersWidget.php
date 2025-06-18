<?php

namespace App\Widgets;

use App\Models\Order;
use App\Models\Parameter;

class LatestOrdersWidget extends BaseWidget
{
    public function render(): string
    {
        // Haal dynamische parameter-opties
        $statusOptions          = Parameter::where('key', 'order_status')
                                           ->pluck('name', 'value')
                                           ->toArray();
        $salesChannelOptions    = Parameter::where('key', 'sales_channel')
                                           ->pluck('name', 'value')
                                           ->toArray();
        $shippingMethodOptions  = Parameter::where('key', 'shipping_method')
                                           ->pluck('name', 'value')
                                           ->toArray();

        // Badge classes per status (0-4)
        $statusClasses = [
            0 => 'bg-gray-100 text-gray-800',    // Concept
            1 => 'bg-yellow-100 text-yellow-800', // Verzonden
            2 => 'bg-green-100 text-green-800',   // Afgerond
            3 => 'bg-blue-100 text-blue-800',     // Vestigd
            4 => 'bg-purple-100 text-purple-800', // Definitief afgerond
        ];

        $c = $this->config;

                // Bouw query met filters en aggregaties
                $query = Order::latest()
                ->when(!empty($c['order_chanel']), fn($q) =>
                    $q->where('order_source', $c['order_chanel'])
                )
                ->when(!empty($c['status']), fn($q) =>
                    $q->whereIn('status', (array) $c['status'])
                )
                ->when(!empty($c['shipping_method']), fn($q) =>
                    $q->where('shipping_method', $c['shipping_method'])
                )
                // Tel aantal orderItems
                ->withCount('orderItems')
                // Sommeer kolom calculated_sales_price als total_value
                ->withSum('orderItems as total', 'calculated_sales_price');
    
            // Haal de geaggregeerde resultaten
            $orders = $query->limit($c['limit'] ?? 10)->get();
        
            // Render view met alle data
        return view('widgets.latest_orders', [
            'orders'                => $orders,
            'config'                => $c,
            'statusOptions'         => $statusOptions,
            'statusClasses'         => $statusClasses,
            'salesChannelOptions'   => $salesChannelOptions,
            'shippingMethodOptions' => $shippingMethodOptions,
        ])->render();
    }
}

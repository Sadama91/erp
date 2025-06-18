<?php

namespace App\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueMetricsWidget extends BaseWidget
{
    public function render(): string
    {
        // Bepaal filterperiode
        $filter = request()->get('filter', 'all');
        $now    = Carbon::now();

        switch ($filter) {
            case 'current_month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;

            case 'last_3_months':
                $start = $now->copy()->subMonths(2)->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                break;

            case 'last_year':
                $start = $now->copy()->subYear()->startOfYear();
                $end   = $now->copy()->subYear()->endOfYear();
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfDay();
                break;

            default: // 'all'
                $firstDate = DB::table('orders')->min('date');
                $start     = $firstDate
                    ? Carbon::createFromFormat('Y-m-d', $firstDate)->startOfMonth()
                    : $now->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
        }

        // Aantal maanden voor gemiddelde
        $months = max(1, $start->diffInMonths($end) + 1);

        // Basisquery voor order_items binnen de periode
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.date', [$start, $end]);

        // KPI-berekeningen
        $revenue       = $query->sum('order_items.calculated_sales_price');
        $ordersCount   = DB::table('orders')->whereBetween('date', [$start, $end])->count();
        $itemsCount    = $query->sum('order_items.quantity');
        $costTotal     = $query->sum(DB::raw('order_items.quantity * order_items.purchase_price'));

        $avgPerMonth       = $revenue / $months;
        $avgOrderValue     = $ordersCount ? $revenue / $ordersCount : 0;
        $avgItemValue      = $itemsCount  ? $revenue / $itemsCount   : 0;
        $marginValue       = $revenue - $costTotal;
        $marginPercent     = $revenue ? ($marginValue / $revenue) * 100 : 0;

        return view('widgets.revenue_metrics', compact(
            'filter',
            'revenue',
            'avgPerMonth',
            'ordersCount',
            'avgOrderValue',
            'itemsCount',
            'avgItemValue',
            'marginValue',
            'marginPercent'
        ))->render();
    }
}

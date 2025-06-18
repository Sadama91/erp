<?php

namespace App\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends BaseWidget
{
    public function render(): string
    {
        $now = Carbon::now();

        // Labels en data arrays initialiseren
        $labels           = [];
        $currentRevenue   = [];
        $currentMargin    = [];
        $lastRevenue      = [];
        $lastMargin       = [];

        // Loop 12 maanden terug, nieuw→oud
        for ($i = 11; $i >= 0; $i--) {
            $m            = $now->copy()->subMonths($i);
            $labels[]     = $m->format('M Y');

            // Huidige jaar: begin en eind van die maand
            $start        = $m->copy()->startOfMonth();
            $end          = $m->copy()->endOfMonth();
            $revCurrent   = DB::table('order_items')
                ->join('orders','order_items.order_id','=','orders.id')
                ->whereBetween('orders.date', [$start, $end])
                ->sum('order_items.calculated_sales_price');
            $costCurrent  = DB::table('order_items')
                ->join('orders','order_items.order_id','=','orders.id')
                ->whereBetween('orders.date', [$start, $end])
                ->sum(DB::raw('order_items.quantity * order_items.purchase_price'));
            $marginCurrent = $revCurrent > 0
                ? round((($revCurrent - $costCurrent) / $revCurrent) * 100, 2)
                : 0;

            $currentRevenue[] = (float) $revCurrent;
            $currentMargin[]  = $marginCurrent;

            // Vorig jaar: zelfde maand vorig jaar
            $prev         = $m->copy()->subYear();
            $startPrev    = $prev->copy()->startOfMonth();
            $endPrev      = $prev->copy()->endOfMonth();
            $revLast      = DB::table('order_items')
                ->join('orders','order_items.order_id','=','orders.id')
                ->whereBetween('orders.date', [$startPrev, $endPrev])
                ->sum('order_items.calculated_sales_price');
            $costLast     = DB::table('order_items')
                ->join('orders','order_items.order_id','=','orders.id')
                ->whereBetween('orders.date', [$startPrev, $endPrev])
                ->sum(DB::raw('order_items.quantity * order_items.purchase_price'));
            $marginLast   = $revLast > 0
                ? round((($revLast - $costLast) / $revLast) * 100, 2)
                : 0;

            $lastRevenue[] = (float) $revLast;
            $lastMargin[]  = $marginLast;
        }

        return view('widgets.revenue_chart', compact(
            'labels',
            'currentRevenue',
            'currentMargin',
            'lastRevenue',
            'lastMargin'
        ))->render();
    }
}

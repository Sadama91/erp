<?php

namespace App\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueSourceTableWidget extends BaseWidget
{
    public function render(): string
    {
        $now = Carbon::now();

        // Labels voor de laatste 3 maanden (nieuw → oud)
        $periods = [];
        for ($i = 0; $i < 3; $i++) {
            $periods[] = $now->copy()->subMonths($i)->format('M Y');
        }

        // Periode
        $start = $now->copy()->subMonths(2)->startOfMonth();
        $end   = $now->copy()->endOfMonth();

        // Één query: join orders → parameters om source-naam op te halen
        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            // join op parameters om de naam van de sales channel te krijgen
            ->join('parameters as p', function($join) {
                $join->on('p.value', '=', 'orders.order_source')
                     ->where('p.key', '=', 'sales_chanel');
            })
            ->whereBetween('orders.date', [$start, $end])
            ->selectRaw(
                'p.name                          AS source, '.
                'YEAR(orders.date)               AS year, '.
                'MONTH(orders.date)              AS month, '.
                'DATE_FORMAT(orders.date, "%b %Y") AS period, '.
                'SUM(order_items.calculated_sales_price) AS total'
            )
            ->groupByRaw('p.name, YEAR(orders.date), MONTH(orders.date), DATE_FORMAT(orders.date, "%b %Y")')
            ->get();

        // Zet om naar [source][period] => total
        $table = [];
        foreach ($rows as $row) {
            $table[$row->source][$row->period] = (float) $row->total;
        }

        return view('widgets.revenue_sources', compact('table', 'periods'))->render();
    }
}

<?php

namespace App\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueListWidget extends BaseWidget
{
    public function render(): string
    {
        $now   = Carbon::now();
        $start = $now->copy()->subMonths(11)->startOfMonth();
        $end   = $now->copy()->endOfMonth();

        // 1 query: per jaar+maand omzet & kosten
        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.date', [$start, $end])
            ->selectRaw("
                YEAR(orders.date)  AS year,
                MONTH(orders.date) AS month,
                SUM(order_items.calculated_sales_price)                AS revenue,
                SUM(order_items.quantity * order_items.purchase_price) AS cost
            ")
            ->groupByRaw("YEAR(orders.date), MONTH(orders.date)")
            ->get()
            ->keyBy(fn($r) => $r->year.'-'.$r->month);

        $data = [];

        // Bouw lijst nieuw→oud en sla lege maanden over
        for ($i = 0; $i < 12; $i++) {
            $m   = $now->copy()->subMonths($i);
            $key = $m->year.'-'.$m->month;

            // geen data of 0 omzet → skip
            if (!isset($rows[$key]) || (float)$rows[$key]->revenue === 0.0) {
                continue;
            }

            $revenue = (float)$rows[$key]->revenue;
            $cost    = (float)$rows[$key]->cost;
            $margin  = $revenue > 0
                ? round((($revenue - $cost) / $revenue) * 100, 2)
                : 0;

            $data[] = [
                'label'   => $m->format('M Y'),
                'revenue' => $revenue,
                'margin'  => $margin,
            ];
        }

        return view('widgets.revenue_list', compact('data'))->render();
    }
}

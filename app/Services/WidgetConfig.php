<?php

namespace App\Services;

class WidgetConfig
{
    public static function getDefaultWidgets(): array
    {
        // Orders
        return [
            'LatestOrders-All' => [
                'widget_name' => 'LatestOrdersWidget',
                'widget_id'   => 'LatestOrders-All',
                'active'      => true,
                'config'      => [
                    'order_chanel'     => null,
                    'status'           => [],
                    'shipping_method' => null,
                    'limit'            => 10,
                ],
                'position'    => 10,
            ],

            'LatestOrders-Vinted' => [
                'widget_name' => 'LatestOrdersWidget',
                'widget_id'   => 'LatestOrders-Vinted',
                'active'      => true,
                'config'      => [
                    'order_chanel'    => 'vinted',
                    'shipping_method' => null,
                    'status'           => [],
                    'limit'            => 10,
                ],
                'position'    => 11,
            ],

            'LatestOrders-Website' => [
                'widget_name' => 'LatestOrdersWidget',
                'widget_id'   => 'LatestOrders-Website',
                'active'      => true,
                'config'      => [
                    'order_chanel'    => 'website',
                    'shipping_method' => null,
                    'status'           => [],
                    'limit'            => 10,
                ],
                'position'    => 12,
            ],


            // Omzet

            // RevenueChartWidget: grafiek omzet huidige vs vorig jaar
            'RevenueChart-1' => [
                'widget_name' => 'RevenueChartWidget',
                'widget_id'   => 'RevenueChart-1',
                'active'      => true,
                'config'      => [
                    // geen extra filters nodig
                ],
                'position'    => 1,
            ],

            // RevenueListWidget: lijst 12 maanden omzet & marge
            'RevenueList-1' => [
                'widget_name' => 'RevenueListWidget',
                'widget_id'   => 'RevenueList-1',
                'active'      => true,
                'config'      => [
                    // geen extra filters nodig
                ],
                'position'    => 2,
            ],

            // RevenueSourceTableWidget: tabel omzet per bron voor 3 maanden
            'RevenueSourceTable-1' => [
                'widget_name' => 'RevenueSourceTableWidget',
                'widget_id'   => 'RevenueSourceTable-1',
                'active'      => true,
                'config'      => [
                    // geen extra filters nodig
                ],
                'position'    => 8,
            ],
            // RevenueListWidget: lijst 12 maanden omzet & marge
'RevenueList-1' => [
    'widget_name' => 'RevenueListWidget',
    'widget_id'   => 'RevenueList-1',
    'active'      => true,
    'config'      => [
        // geen extra filters nodig
    ],
    'position'    => 4,
],

// RevenueMetricsWidget: KPI’s omzet, bestellingen, items & marge
'RevenueMetrics-1' => [
    'widget_name' => 'RevenueMetricsWidget',
    'widget_id'   => 'RevenueMetrics-1',
    'active'      => true,
    'config'      => [
        // filter: 'all' of 'year' (default: 'all')
        'filter' => 'all',
    ],
    'position'    => 6,
],

// InventoryBalanceWidget: voorraad- & balansstatistieken
'InventoryBalance-1' => [
    'widget_name' => 'InventoryBalanceWidget',
    'widget_id'   => 'InventoryBalance-1',
    'active'      => true,
    'config'      => [
        // geen extra config nodig
    ],
    'position'    => 5,
],

        ];
    }
}

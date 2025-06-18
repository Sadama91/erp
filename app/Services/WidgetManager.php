<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WidgetManager
{
    /**
     * Mapping van widget-namen naar hun classes.
     * Voeg hier nieuwe widgets toe.
     */
    protected $widgetMap = [
        'WelcomeWidget'      => \App\Widgets\WelcomeWidget::class,
        'LatestOrdersWidget' => \App\Widgets\LatestOrdersWidget::class,
        'RevenueChartWidget' => \App\Widgets\RevenueChartWidget::class,
        'RevenueListWidget' => \App\Widgets\RevenueListWidget::class,
        'RevenueSourceTableWidget' => \App\Widgets\RevenueSourceTableWidget::class,
        'RevenueMetricsWidget' => \App\Widgets\RevenueMetricsWidget::class,
        'InventoryBalanceWidget' => \App\Widgets\InventoryBalanceWidget::class,
        // Voeg meer widgets toe...
    ];

    /**
     * Rendert een widget op basis van naam en optionele config.
     */
    public function renderWidget(string $widgetName, array $config = []): string
    {
        if (!isset($this->widgetMap[$widgetName])) {
            Log::warning("WidgetManager: onbekende widget '$widgetName'");
            return "<div class='bg-red-100 text-red-800 p-2 rounded'>Onbekende widget: <strong>$widgetName</strong></div>";
        }

        $widgetClass = $this->widgetMap[$widgetName];
        $widget = new $widgetClass($config);

        if (!method_exists($widget, 'render')) {
            Log::error("Widget '$widgetName' mist een render()-methode.");
            return "<div class='bg-red-100 text-red-800 p-2 rounded'>Fout: widget '$widgetName' mist render()-methode.</div>";
        }

        return $widget->render();
    }

    /**
     * Geeft alle beschikbare widgets (namen) terug.
     * Handig voor een configuratie-UI.
     */
    public function availableWidgets(): array
    {
        return array_keys($this->widgetMap);
    }
}

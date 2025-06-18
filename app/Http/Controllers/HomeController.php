<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use App\Services\WidgetManager;
use App\Services\WidgetConfig;

class HomeController extends Controller
{
    public function index(WidgetManager $widgetManager)
{
    $userId = auth()->id();
    $userSetting = UserSetting::where('user_id', $userId)
                              ->where('page', 'homepage')
                              ->first();

    // Haal standaard widgets op
    $defaultWidgets = WidgetConfig::getDefaultWidgets();

    if (!$userSetting) {
        // Geen instellingen → sla standaard widgets op
        $userSetting = new UserSetting();
        $userSetting->user_id = $userId;
        $userSetting->page = 'homepage';
        $userSetting->settings = json_encode(['widgets' => $defaultWidgets]);
        $userSetting->save();

        $widgets = $defaultWidgets;
    } else {
        // Haal de opgeslagen widgets op
        $storedSettings = json_decode($userSetting->settings, true);
        $userWidgets = $storedSettings['widgets'] ?? [];

        $widgets = [];

        foreach ($defaultWidgets as $widgetId => $defaultConfig) {
            // Zoek de opgeslagen widget-configuratie en voeg ontbrekende sleutels toe
            $widgets[$widgetId] = array_merge($defaultConfig, $userWidgets[$widgetId] ?? []);
            
            // ✅ Zorg ervoor dat widget_name altijd bestaat
            if (!isset($widgets[$widgetId]['widget_name'])) {
                $widgets[$widgetId]['widget_name'] = explode('-', $widgetId)[0]; // Gebruik de prefix van widget_id
            }
        }

        // Voeg widgets toe die door de gebruiker zijn toegevoegd, maar niet standaard zijn
        foreach ($userWidgets as $widgetId => $userConfig) {
            if (!isset($widgets[$widgetId])) {
                $widgets[$widgetId] = array_merge([
                    'widget_name' => explode('-', $widgetId)[0], // Fallback widgetnaam
                    'widget_id' => $widgetId,
                    'active' => false,
                    'config' => [],
                    'position' => PHP_INT_MAX
                ], $userConfig);
            }
        }
    }

    // Sorteer widgets op positie zonder keys te verliezen
    uasort($widgets, fn($a, $b) => ($a['position'] ?? PHP_INT_MAX) <=> ($b['position'] ?? PHP_INT_MAX));

    // Render widgets
    $widgetsHtml = '';
    foreach ($widgets as $widgetConfig) {
        if (!empty($widgetConfig['active'])) {
            if (!isset($widgetConfig['widget_name'])) {
                dd("FOUT: widget_name ontbreekt", $widgetConfig);
            }

            $widgetsHtml .= $widgetManager->renderWidget(
                $widgetConfig['widget_name'], // Gebruik de originele widgetnaam
                $widgetConfig['config'] ?? []
            );
        }
    }
    
    return view('home', compact('widgetsHtml'));
}

    
}    

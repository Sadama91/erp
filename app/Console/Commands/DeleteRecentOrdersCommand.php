<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\OrderController;

class DeleteRecentOrdersCommand extends Command
{
    // De command naam waarmee je het command uitvoert
    protected $signature = 'orders:delete-recent';
    
    protected $description = 'Verwijder alle bestellingen van het afgelopen uur en herstel de voorraad';

    public function handle()
    {
        // Maak een instance van de OrderController
        $orderController = new OrderController();

        // Roep de destroyRecentOrders functie aan
        $response = $orderController->destroyRecentOrders();

        // Omdat destroyRecentOrders() een redirect/JSON response teruggeeft,
        // kun je hier de output loggen of weergeven
        $this->info('Bestellingen van het afgelopen uur zijn verwijderd.');
    }
}

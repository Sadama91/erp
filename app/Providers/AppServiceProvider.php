<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PurchaseOrderItem;
use App\Observers\PurchaseOrderItemObserver;

class AppServiceProvider extends ServiceProvider
{
    // Regisreer de koppelingen tussen events en listeners
    protected $listen = [
        \App\Events\PurchaseOrderPlaced::class => [
            \App\Listeners\HandlePurchaseOrderPlaced::class,
        ],
        \App\Events\PurchaseOrderProcessed::class => [
            HandlePurchaseOrderInventoryUpdate::class,
            HandlePurchaseOrderFinancialTransaction::class,
        ], 
        \App\Events\InvoiceCreated::class => [
            \App\Listeners\CreateJournalEntryForInvoice::class,
            \App\Listeners\UpdateVATRecordsForInvoice::class,
        ],
        \App\Events\PaymentReceived::class => [
            \App\Listeners\PaymentJournalEntryForPayment::class,
        ],
    ];
    
    public function boot()
    {
        PurchaseOrderItem::observe(PurchaseOrderItemObserver::class);
    }
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

}

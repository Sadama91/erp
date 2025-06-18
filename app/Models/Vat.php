<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Vat extends Model
{
    // use LogsActivity;
    protected $table = 'vats';

    protected $fillable = [
        'order_id',
        'invoice_id',
        'finance_transaction_id', 
        'purchase_amount_id', // naamgeving aangepast: "_id" zonder quote
        'amount_incl',
        'amount_excl',
        'vat_amount',
        'vat_rate',
        'vat_transaction_type',
        'financial_details',
    ];

    protected $casts = ['financial_details'];

    /**
     * Boot method om automatisch logging af te handelen bij create/update.
     */
    protected static function boot()
    {
        parent::boot();
    
        // Logging bij het aanmaken van een BTW-record via Spatie Activity Log
        static::created(function ($vat) {
            activity()
                ->performedOn($vat)
                ->withProperties($vat->toArray())
                ->log('BTW record aangemaakt');
        });
    
        // Logging bij het updaten van een BTW-record via Spatie Activity Log
        static::updated(function ($vat) {
            activity()
                ->performedOn($vat)
                ->withProperties($vat->toArray())
                ->log('BTW record bijgewerkt');
        });
    }
    

    /**
     * Optionele relatie naar het Product.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(purchaseOrder::class);
    }
    /**
     * Optionele relatie naar het Product.
     */
    public function financeTransaction()
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    /**
     * Relatie naar de Order (via order_order_id).
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_order_id');
    }

    /**
     * Relatie naar de InvoiceLine.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Configureer Spatie Activitylog opties.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // of specificeer de attributen die je wilt loggen
            ->useLogName('vat')
            ->dontSubmitEmptyLogs();
    }

    /**
     * Geef een beschrijving voor het event.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "VAT record has been {$eventName}";
    }
}

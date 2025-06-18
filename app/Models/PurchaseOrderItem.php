<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    //LOgging:
    protected static $logAttributes = ['purchase_order_id', 'product_id', 'sku',
    'description','price_excl_unit','price_excl_bulk','price_incl_unit',
    'price_incl_bulk','quantity','total',];

    protected static $logName = 'purchase_order_item';
    
    // Eventueel: alleen loggen als er wijzigingen zijn
    protected static $logOnlyDirty = true;
    
    // Geef aan welke tabel deze model gebruikt
    protected $table = 'purchase_order_items';

    // Vulbare velden
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'sku',
        'description',
        'price_excl_unit',
        'price_excl_bulk',
        'price_incl_unit',
        'price_incl_bulk',
        'quantity',
        'total',
        'created_at',
        'updated_at',
    ];

    // Relatie met PurchaseOrder
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Definieer de relatie met Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Definieer de relatie met Price
    public function price()
    {
        return $this->hasOne(Price::class, 'product_id', 'product_id'); // Dit kan ook anders zijn afhankelijk van je prijsstructuur
    }
    // Toegangsmethode om de totale prijs te berekenen
    public function getTotalAttribute()
    {
        return $this->price_excl_unit * $this->quantity; // Pas aan afhankelijk van hoe je de total berekent
    }
}

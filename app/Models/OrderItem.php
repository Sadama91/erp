<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'purchase_price',
        'original_sales_price',
        'calculated_sales_price',
        'vat_amount',
        'vat_rate_id', // Nieuw toegevoegd
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    /**
     * Bereken de BTW op basis van het BTW-tarief en update het item.
     */
    public function calculateVat()
    {
        $vatRate = VatRate::find($this->vat_rate_id); // Zorg dat je een `VatRate` model hebt

        if ($vatRate) {
            $this->vat_amount = ($this->calculated_sales_price * $this->quantity) * ($vatRate->rate / 100);
            $this->save();

            // Ook de order totalen bijwerken
            $this->order->updateTotals();
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceLines extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_lines';

    // Velden die via mass-assignment ingevuld mogen worden
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'amount_excl_vat_total',
        'amount_incl_vat_total',
        'total_vat',
        'vat_rate',
        'remarks',
    ];
    // In app/Models/InvoiceLine.php
        protected $casts = [
            'quantity'              => 'float',
            'amount_excl_vat_total' => 'float',
            'amount_incl_vat_total' => 'float',
            'total_vat'             => 'float',
            'vat_rate'              => 'float',
        ];


    /**
     * Relatie naar de Invoice waartoe deze regel behoort.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Relatie naar het Product (kan null zijn).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price',
        'type',
        'valid_from',
        'valid_till',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

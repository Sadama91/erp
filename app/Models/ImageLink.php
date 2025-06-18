<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImageLink extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'image_links'; // Definieer hier de juiste tabelnaam

    protected $fillable = [
        'image_id',
        'product_id',
        'role',
        'publication',
        'order',
    ];

    // Relaties
    public function image()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class); // Zorg ervoor dat je een Product model hebt
    }
}

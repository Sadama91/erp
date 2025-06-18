<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location',
        'thumbnail_location', // thumbnail afbeelding
        'original_filename',
        'uploaded_by',
        'uploaded_at',
        'description',
        'mime_type',
        'size',
        'status'
    ];

    // Relaties
    public function imageLinks() // Zorg ervoor dat de naam hier consistent is (kleine letters)
    {
        return $this->hasMany(ImageLink::class);
    }
}
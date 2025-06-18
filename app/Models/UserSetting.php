<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'page', 'settings'];

    protected $casts = [
        'settings' => 'array', // JSON wordt automatisch omgezet naar array in PHP
    ];
}

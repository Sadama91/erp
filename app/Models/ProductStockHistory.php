<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStockHistory extends Model
{
    use HasFactory;

    protected $table = 'product_stock_history'; // Zorg ervoor dat de tabelnaam correct is.

    public $timestamps = false; // Disable timestamps
    protected $fillable = [
        'product_id',
        'quantity',
        'stock_action',
        'reason',
        'user_id',
        'changed_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

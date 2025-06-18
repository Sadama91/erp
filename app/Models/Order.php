<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use HasFactory, SoftDeletes,LogsActivity;

    //LOgging:
    protected static $logAttributes = ['*'];
    protected static $logName = 'orders';
    
    // Eventueel: alleen loggen als er wijzigingen zijn
    protected static $logOnlyDirty = true;

    // Geef aan welke tabel deze model gebruikt
    protected $table = 'orders';

    // Vulbare velden
    
    protected $fillable = [
        'customer_id',
        'date',
        'status',
        'shipping_method',
        'shipping_cost',
        'order_source',
        'notes',
        'user_id',
        'customer_name',
        'username',
        'customer_address',
        'postal_code',
        'city',
        'country',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($order) {
            // Soft delete alle gerelateerde order-items
            foreach ($order->orderItems as $orderItem) {
                $orderItem->delete();
            }
        });
    }
    // Relaties
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    /**
     * Bereken de totalen en update de order.
     */
    public function updateTotals()
    {
        $this->total_purchase_price = $this->orderItems->sum(function ($item) {
            return $item->purchase_price * $item->quantity;
        });

        $this->total_sales_price = $this->orderItems->sum(function ($item) {
            return $item->calculated_sales_price * $item->quantity;
        });

        $this->total_vat_amount = $this->orderItems->sum('vat_amount');

        $this->save();
    }

    /**
     * Implementeer de vereiste methode voor Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log alle attributen
            ->dontSubmitEmptyLogs();
    }

    public function getWooId(): ?int
{
    return $this->meta['woo_id'] ?? null;
}

public function setWooMeta(array $newData): void
{
    $currentMeta = $this->meta ?? [];
    $mergedMeta = array_merge($currentMeta, $newData);

    $this->meta = $mergedMeta;
    $this->save();
}


}

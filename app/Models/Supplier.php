<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use HasFactory, SoftDeletes,LogsActivity;

    protected $fillable = [
        'name',
        'contact_info',
        'purchase_via',
        'telephone',
        'website',
        'terms',
        'remarks',
        'status',
        'payment_days',
    ];

    // Log alleen als er veranderingen zijn (optioneel)
    protected static $logOnlyDirty = true;
    protected static $logName = 'supplier';
    protected static $submitEmptyLogs = false;

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
// In Supplier model
public function activities()
{
    return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
}


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log alle attributen
            ->dontSubmitEmptyLogs();
    }
}

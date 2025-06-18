<?php

namespace App\Models;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory,LogsActivity;

    //LOgging:
    protected static $logAttributes = ['supplier_id', 'date', 'status','notes'];
    protected static $logName = 'purchase_order';
    
    // Eventueel: alleen loggen als er wijzigingen zijn
    protected static $logOnlyDirty = true;

    // Geef aan welke tabel deze model gebruikt
    protected $table = 'purchase_orders';

    // Vulbare velden
    protected $fillable = [
        'supplier_id', 
        'date', 
        'status', 
        'notes', 
        'created_at',
        'updated_at',
    ];

    // Relatie met PurchaseOrderItem
public function purchaseOrderItems()
{
    return $this->hasMany(PurchaseOrderItem::class);
}

       // Definieer de relatie met Supplier
       public function supplier()
       {
           return $this->belongsTo(Supplier::class, 'supplier_id'); // 'supplier_id' is de foreign key
       }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

        /**
     * Implementeer de vereiste methode voor Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log alle attributen
            ->useLogName('purchase_order')
            ->dontSubmitEmptyLogs();
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'name',
        'type',
        'status',
        'date',
        'invoice_due_date',
        'invoice_reference',
        'invoice_number',
        'description',
        'notes',
        'linking_documents',
    ];
    protected $casts = [
        'date' => 'date',
        'linking_documents' => 'array',
    ];

    // Log alle attributen
    protected static $logAttributes = ['*'];
    protected static $logName = 'invoice';

    protected static function boot()
    {
        parent::boot();

        // Bij een delete, ook de invoice_lines meenemen.
        static::deleting(function ($invoice) {
            // Soft delete alle gerelateerde order-items
            foreach ($invoice->invoiceLines as $invoiceItem) {
                $invoiceItem->delete();
            }
        });
    }
    /**
     * Relatie met Supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relatie met InvoiceLines (1:N).
     */
    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLines::class);
    }

    /**
     * Accessor voor een dynamisch factuurnummer.
     */
    public function getFormattedInvoiceNumberAttribute()
    {
        $year = $this->created_at ? $this->created_at->format('Y') : date('Y');
        return sprintf('%s-%05d', $year, $this->id);
    }

    /**
     * Implementeer de vereiste methode voor Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log alle attributen
            ->useLogName('invoice')
            ->dontSubmitEmptyLogs();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FinanceAccount extends Model
{
    use LogsActivity;

    protected $table = 'finance_accounts';

    // Velden die via mass-assignment ingevuld mogen worden
    protected $fillable = [
        'parent_id',
        'account_code',
        'account_name',
        'category',
        'balance',
        'balance_old',
        'is_active',
    ];

    // Logs
    protected static $logName = 'finance_account';
    protected static $logAttributes = ['parant_id', 'account_code', 'account_name', 'balance','is_active'];
    protected static $logOnlyDirty = true; // Alleen de gewijzigde velden loggen
 
     // Relatie naar de activiteit loggen
     public static function boot()
     {
         parent::boot();
     
         static::updated(function ($setting) {
             activity()
                 ->performedOn($setting)
                 ->causedBy(auth()->user())
                 ->withProperties([
                     'old' => $setting->getOriginal(),
                     'new' => $setting->getAttributes(),
                 ])
                 ->log('Bankrekening bijgewerkt');
     
             // Gebruik hier de parameter $setting in plaats van $this
             $setting->balance_old = $setting->balance;
             
             $setting->saveQuietly();
         });
     
         static::created(function ($setting) {
             activity()
                 ->performedOn($setting)
                 ->causedBy(auth()->user())
                 ->withProperties([
                     'new' => $setting->getAttributes(),
                 ])
                 ->log('Bankrekening toegevoegd');
     
             $setting->balance_old = $setting->balance;
             $setting->saveQuietly(); // Voorkomt een loop en extra logging
         });
     }
     

    /**
     * Relatie naar eventuele subrekeningen.
     */
    public function children()
    {
        return $this->hasMany(FinanceAccount::class, 'parent_id');
    }

    /**
     * Relatie naar de bovenliggende rekening.
     */
    public function parent()
    {
        return $this->belongsTo(FinanceAccount::class, 'parent_id');
    }

    /**
     * Relatie naar de finance transacties.
     * Iedere account kan meerdere transacties hebben.
     */
    public function financeTransactions()
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
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
}

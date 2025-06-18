<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Setting extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['key', 'value', 'category', 'active'];

    // Zorg ervoor dat de 'value' als JSON wordt behandeld
    protected $casts = [
        'value' => 'array',
    ];

    // Voeg de logfunctie toe voor updates
    protected static $logAttributes = ['key', 'value', 'category', 'active'];

    protected static $logOnlyDirty = true; // Alleen de gewijzigde velden loggen

    protected static $logName = 'setting';

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
                ->log('Instelling bijgewerkt');
        });

        static::created(function ($setting) {
            activity()
                ->performedOn($setting)
                ->causedBy(auth()->user())
                ->withProperties([
                    'new' => $setting->getAttributes(),
                ])
                ->log('Instelling toegevoegd');
        });
    }

    /**
     * Implementeer de vereiste methode voor Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log alle attributen
            ->useLogName('settings')
            ->dontSubmitEmptyLogs();
    }

    public static function get($key, $default = null, $category = null)
{
    $query = self::query()
        ->where('key', '=', $key)
        ->whereNull('deleted_at')
        ->where('active', 1);
    
    if ($category) {
        $query->where('category', $category);
    }
    
    $setting = $query->first();
    if ($setting) {
        $decoded = json_decode($setting->value, true);
        return $decoded !== null ? $decoded : $default;
    }
    
    return $default;
}


}

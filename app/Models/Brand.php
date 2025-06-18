<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Brand;
use App\Models\Product;

class Brand extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'woo_description', 'main_brand', 'woo_id'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('brand')
            ->logOnly(['name', 'slug', 'woo_id', 'main_brand'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::created(function ($brand) {
            $brand->syncWithWooCommerce();
        });

        static::updated(function ($brand) {
            $brand->syncWithWooCommerce();
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function syncWithWooCommerce()
    {
        activity()->performedOn($this)->log('Start sync merk met WooCommerce.');

        if ($this->woo_id) {
            Http::withBasicAuth(
                config('services.woocommerce.consumer_key'),
                config('services.woocommerce.consumer_secret')
            )->put(config('services.woocommerce.url') . '/wp-json/wc/v3/products/brands/' . $this->woo_id, [
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->woo_description,
            ]);

            activity()->performedOn($this)->log('WooCommerce merk bijgewerkt.');
            return;
        }

        $wooResponse = Http::withBasicAuth(
            config('services.woocommerce.consumer_key'),
            config('services.woocommerce.consumer_secret')
        )->get(config('services.woocommerce.url') . '/wp-json/wc/v3/products/brands', [
            'slug' => $this->slug
        ]);

        if ($wooResponse->successful()) {
            $existingBrands = $wooResponse->json();

            if (!empty($existingBrands)) {
                $wooBrand = $existingBrands[0];

                $this->woo_id = $wooBrand['id'];
                $this->saveQuietly();

                activity()->performedOn($this)->log('WooCommerce merk gekoppeld (gevonden via slug).');
                return;
            }
        }

        $wooCreateResponse = Http::withBasicAuth(
            config('services.woocommerce.consumer_key'),
            config('services.woocommerce.consumer_secret')
        )->post(config('services.woocommerce.url') . '/wp-json/wc/v3/products/brands', [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->woo_description,
        ]);

        if ($wooCreateResponse->successful() && isset($wooCreateResponse['id'])) {
            $wooBrand = $wooCreateResponse->json();

            $this->woo_id = $wooBrand['id'];
            $this->saveQuietly();

            activity()->performedOn($this)->log('Nieuw WooCommerce merk aangemaakt.');
        }
    }
}

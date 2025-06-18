<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('category')
            ->logOnly(['name', 'slug', 'woo_id', 'parent_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'woo_id'
    ];

    protected static function booted()
    {
        static::created(function ($category) {
            $category->syncWithWooCommerce();
        });

        static::updated(function ($category) {
            $category->syncWithWooCommerce();
        });
    }

    public function syncWithWooCommerce()
    {
        activity()->performedOn($this)->log('Start sync met WooCommerce.');

        if ($this->woo_id) {
            Http::withBasicAuth(
                config('services.woocommerce.consumer_key'),
                config('services.woocommerce.consumer_secret')
            )->put(config('services.woocommerce.url') . '/wp-json/wc/v3/products/categories/' . $this->woo_id, [
                'name' => $this->name,
                'slug' => $this->slug,
            ]);

            activity()->performedOn($this)->log('WooCommerce categorie bijgewerkt.');
            return;
        }

        $wooResponse = Http::withBasicAuth(
            config('services.woocommerce.consumer_key'),
            config('services.woocommerce.consumer_secret')
        )->get(config('services.woocommerce.url') . '/wp-json/wc/v3/products/categories', [
            'slug' => $this->slug
        ]);

        if ($wooResponse->successful()) {
            $existingCategories = $wooResponse->json();

            if (!empty($existingCategories)) {
                $wooCategory = $existingCategories[0];

                $this->woo_id = $wooCategory['id'];

                if (!empty($wooCategory['parent'])) {
                    $this->attachParentCategory($wooCategory['parent']);
                }

                $this->saveQuietly();
                activity()->performedOn($this)->log('WooCommerce categorie gekoppeld (gevonden via slug).');
                return;
            }
        }

        $wooCreateResponse = Http::withBasicAuth(
            config('services.woocommerce.consumer_key'),
            config('services.woocommerce.consumer_secret')
        )->post(config('services.woocommerce.url') . '/wp-json/wc/v3/products/categories', [
            'name' => $this->name,
            'slug' => $this->slug,
        ]);

        if ($wooCreateResponse->successful() && isset($wooCreateResponse['id'])) {
            $wooCategory = $wooCreateResponse->json();

            $this->woo_id = $wooCategory['id'];

            if (!empty($wooCategory['parent'])) {
                $this->attachParentCategory($wooCategory['parent']);
            }

            $this->saveQuietly();
            activity()->performedOn($this)->log('Nieuwe WooCommerce categorie aangemaakt.');
        }
    }

    protected function attachParentCategory($parentWooId)
    {
        $parentCategory = static::where('woo_id', $parentWooId)->first();

        if (!$parentCategory) {
            $parentResponse = Http::withBasicAuth(
                config('services.woocommerce.consumer_key'),
                config('services.woocommerce.consumer_secret')
            )->get(config('services.woocommerce.url') . '/wp-json/wc/v3/products/categories/' . $parentWooId);

            if ($parentResponse->successful()) {
                $wooParent = $parentResponse->json();

                $parentCategory = static::create([
                    'name' => $wooParent['name'],
                    'slug' => $wooParent['slug'],
                    'woo_id' => $wooParent['id'],
                ]);

                if (!empty($wooParent['parent'])) {
                    $parentCategory->attachParentCategory($wooParent['parent']);
                }

                $parentCategory->saveQuietly();
                activity()->performedOn($parentCategory)->log('Parent categorie aangemaakt via sync.');
            }
        }

        if ($parentCategory) {
            $this->parent_id = $parentCategory->id;
            activity()->performedOn($this)->log('Parent gekoppeld aan categorie.');
        }
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions; // Zorg dat deze regel aanwezig is

class Product extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'sku',
        'brand_id',
        'subgroup_id',
        'shipping_class',
        'purchase_quantity',
        'sale_quantity',
        'height',
        'width',
        'depth',
        'weight',
        'to_website',
        'supplier_id',
        'short_description',
        'long_description',
        'status',
        'published',
        'woo_id',
        'product_type',
        'attributes',
        'categories',
        'seo_title',
        'seo_description',
        'focus_keyword',
        'vat_rate_id',
        'available_for_web',
        'sales_chanel',
        'location',
        'primary_category_id',
        'bundled_items',
        'vinted_title',
        'vinted_description',
        'back_in_stock'
    ];

    protected $casts = [
        'height'             => 'decimal:2',
        'width'              => 'decimal:2',
        'depth'              => 'decimal:2',
        'weight'             => 'decimal:2',
        'to_website'         => 'boolean',
        'published'          => 'boolean',
        'back_in_stock'      => 'boolean',
        'purchase_quantity'  => 'integer',
        'sale_quantity'      => 'integer',
        'supplier_id'        => 'integer',
        'category_id'        => 'integer',
        'subgroup_id'        => 'integer',
        'woo_id'             => 'integer',
        'vat_rate_id'        => 'integer',
        'primary_category_id'=> 'integer',
        'status'             => 'integer',
        'attributes'         => 'array',
        'categories'         => 'array',
        'sales_chanel'       => 'array',
        'bundled_items'      => 'array',
    ];

    protected static ?array $statusLabels = null;


    /**
     * Implementeer de vereiste methode voor de Spatie Activitylog.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Pas dit aan naar gelang welke attributen je wilt loggen
            ->dontSubmitEmptyLogs();
    }

    /**
     * Geef een beschrijving voor de gebeurtenis die wordt gelogd.
     *
     * @param  string  $eventName
     * @return string
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Product '{$this->name}' is {$eventName}";
    }
    public function getProductTypeIdAttribute()
{
    return $this->product_type;
}
public function categories()
{
    // Deze functie is afhankelijk van hoe je non-primaire categorieën wilt structureren.
    // Hier veronderstellen we dat je deze in een array of string opslaat in de database.
    return $this->hasMany(Category::class, 'id', 'categories'); // Pas aan afhankelijk van je implementatie
}

    public function nonPrimaryCategories()
    {
        // Deze functie is afhankelijk van hoe je non-primaire categorieën wilt structureren.
        // Hier veronderstellen we dat je deze in een array of string opslaat in de database.
        return $this->hasMany(Category::class, 'id', 'categories'); // Pas aan afhankelijk van je implementatie
    }

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subgroup()
    {
        return $this->belongsTo(Subgroup::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function stockHistory()
    {
        return $this->hasMany(ProductStockHistory::class);
    }

    public function purchases()
    {
        return $this->hasMany(purchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    

    public function purchaseOrderItems()
    {
        return $this->hasMany(purchaseOrderItem::class);
    }

    public function latestPurchaseItem()
    {
        return $this->hasOne(PurchaseOrderItem::class)->latestOfMany();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

// Functie om de som van de verkopen op te halen
public function totalSales()
{
    return $this->orderItems->sum('quantity');
}

// Functie om de totale omzet te berekenen
public function totalRevenue()
{
    return $this->orderItems->sum('calculated_sales_price');
}


    // Functie om de voorraad op te halen
    public function stock()
    {
        return $this->hasOne(ProductStock::class); // Zorg ervoor dat dit overeenkomt met jouw models
    }

    public function syncTags($tagString)
    {
        $tags = explode(',', $tagString);
        $tagIds = [];

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!$tag) continue;

            $tagModel = Tag::firstOrCreate(['name' => $tag, 'slug' => Str::slug($tag)]);
            $tagIds[] = $tagModel->id;
        }

        $this->tags()->sync($tagIds);
    }

    // Update de voorraad van het product en registreert de wijziging
    public function updateStock($quantity, $reason)
    {
        // Update de voorraad van het product
        $this->sale_quantity += $quantity; // Voorbeeld, pas aan op basis van je logica
        $this->save();

        // Voeg een record toe aan de voorraadgeschiedenis
        $this->stockHistory()->create([
            'quantity_change' => $quantity,
            'change_reason' => $reason,
        ]);
    }

    public function imageLinks()
    {
        return $this->hasMany(ImageLink::class);
    }

    public function images()
    {
        return $this->hasManyThrough(Image::class, ImageLink::class, 'product_id', 'id', 'id', 'image_id');
    }

    public function locationParameter()
    {
        return $this->belongsTo(Parameter::class, 'location', 'value');
    }

    // Laad status labels eenmalig
    public static function getStatusLabels(): array
    {
        if (self::$statusLabels === null) {
            self::$statusLabels = Parameter::where('key', 'article_status')
                ->pluck('value', 'name')
                ->toArray();
        }

        return self::$statusLabels;
    }

    /**
     * Haalt een specifieke statuslabel op.
     *
     * @param int|string $status
     * @return string
     */
    public static function getStatusLabel($status): string
    {
        $labels = self::getStatusLabels();

        return $labels[$status] ?? 'Onbekend';
    }

    // Wijzig de status van het product als de overgang is toegestaan
    public function updateStatus(string $newStatus): bool
    {
        $currentStatus = $this->status;

            if (!isset(self::$allowedTransitions[$currentStatus]) ||
            !in_array($newStatus, self::$allowedTransitions[$currentStatus])) {
               return false; // Statuswijziging niet toegestaan
        }

        $this->status = $newStatus;
        $this->save();

         return true; // Status succesvol gewijzigd
    }

    // Bulk update van de status voor meerdere producten op basis van SKU's
    public static function bulkUpdateStatusBySkus(array $productSkus, string $newStatus): array
    {
        $updated = [];
        $notAllowed = [];

        foreach ($productSkus as $sku) {
            $product = self::where('sku', $sku)->first();

            if (!$product) {
                $notAllowed[] = [
                    'sku' => $sku,
                    'name' => 'Onbekend',
                    'id' => 'Onbekend',
                    'current_status' => 'Onbekend',
                    'desired_status' => $newStatus,
                    'reason' => 'Product niet gevonden',
                ];
                continue;
            }

            if ($product->updateStatus($newStatus)) {
                $updated[] = $product;
            } else {
                $notAllowed[] = [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'id' => $product->id,
                    'current_status' => $product->status,
                    'desired_status' => $newStatus,
                    'reason' => sprintf(
                        "Overgang van %d %s naar %d %s niet toegestaan",
                        $product->status,
                        self::getStatusLabel($product->status),
                        $newStatus,
                        self::getStatusLabel($newStatus)
                    ),
                ];
                     }
        }

        return ['updated' => $updated, 'notAllowed' => $notAllowed];
    }

    // Automatische statusupdates voor het product
    public function updateStatusAutomatically()
    {
        $this->updateStatusFromPurchaseOrders();
        $this->updateStatusFromSales();
        $this->checkForExpiration();
        // Update de laatste statusovergang
        $this->last_status_change = now();
        $this->save();
      }

    // Update de status op basis van inkooporders
    protected function updateStatusFromPurchaseOrders()
    {
        // Verkrijg voorraadinformatie via de relatie
        $onTheWayQuantity = $this->stock->on_the_way_quantity ?? 0;
        $currentQuantity = $this->stock->current_quantity ?? 0;
    
          if ($this->status === 0 && $onTheWayQuantity > 0) {
            $this->status = 10; // Onderweg
         } elseif ($this->status === 10 && $currentQuantity > 0) {
            $this->status = 20; // Actief
         }
    }


    // Update de status op basis van verkopen
    protected function updateStatusFromSales()
    {
        $currentQuantity = $this->stock->current_quantity ?? 0;
        // Logica om status te updaten bij verkoop
        if ($this->status === 30 && $this->current_quantity <= 0) {
            $this->status = 40; // Gesaneerd
         }
    }

    // Controleer of de status vervallen moet worden
    protected function checkForExpiration()
    {
        // Controleer of de status vervallen moet worden
        if ($this->status === 40 && $this->last_status_change) {
            if ($this->fourMonthsHavePassed($this->last_status_change)) {
                $this->status = 90; // Vervallen
            }
        }
    }

    // Controleert of er vier maanden zijn verstreken sinds de laatste statuswijziging
    protected function fourMonthsHavePassed($lastStatusChange): bool
    {
        return now()->diffInMonths($lastStatusChange) >= 4;
    }
}

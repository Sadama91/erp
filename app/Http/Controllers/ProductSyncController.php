<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;

class ProductSyncController extends Controller
{
    /**
     * Push een batch lokale producten naar WooCommerce (droog-run).
     * Maakt een JSON-bestand met de payloads zonder daadwerkelijke API-calls.
     */
    public function pushBatch(Request $request)
    {
        // Valideer inkomende data
        $data = $request->validate([
            '*.id'        => 'required|integer|exists:products,id',
            '*.name'      => 'required|string',
            '*.price'     => 'required|numeric',
            '*.stock'     => 'required|integer|min:0',
            '*.image_url' => 'nullable|url',
        ]);

        $dryRun = [];

        foreach ($data as $item) {
            $product = Product::find($item['id']);

            // Bouw de Woo payload
            $payload = [
                'name'          => $item['name'],
                'regular_price' => (string) $item['price'],
            ];

            if ($item['stock'] > 0) {
                $payload['stock_quantity'] = $item['stock'];
                $payload['manage_stock']   = true;
            }

            if (!empty($item['image_url'])) {
                $payload['images'] = [['src' => $item['image_url']]];
            }

            // Bepaal of het een 'create' of 'update' zou zijn
            $action = $product->woo_id ? 'update' : 'create';

            // Voeg toe aan dry-run verzameling
            $dryRun[] = [
                'local_id' => $product->id,
                'woo_id'   => $product->woo_id,
                'action'   => $action,
                'payload'  => $payload,
            ];
        }

        // Schrijf dry-run JSON
        $now  = now()->format('YmdHis');
        $path = storage_path("logs/woocommerce/dryrun-push-{$now}.json");
        File::ensureDirectoryExists(dirname($path), 0755);
        File::put($path, json_encode(['dry_run' => $dryRun], JSON_PRETTY_PRINT));

        // Retourneer het bestand en de data
        return response()->json([
            'dry_run_file' => "logs/woocommerce/dryrun-push-{$now}.json",
            'dry_run'      => $dryRun,
        ], 200);
    }

    /**
     * Push één lokaal product-update naar WooCommerce (droog-run).
     */
    public function pushSingle($id, Request $request)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'      => 'sometimes|required|string',
            'price'     => 'sometimes|required|numeric',
            'stock'     => 'sometimes|required|integer|min:0',
            'image_url' => 'nullable|url',
        ]);

        // Bouw de Woo payload op basis van de aangeleverde velden
        $payload = [];
        if (isset($data['name']))  $payload['name'] = $data['name'];
        if (isset($data['price'])) $payload['regular_price'] = (string) $data['price'];
        if (isset($data['stock']) && $data['stock'] > 0) {
            $payload['stock_quantity'] = $data['stock'];
            $payload['manage_stock']   = true;
        }
        if (isset($data['image_url'])) {
            $payload['images'] = [['src' => $data['image_url']]];
        }

        // Bepaal actie
        $action = $product->woo_id ? 'update' : 'create';

        // Dry-run array
        $dryRun = [
            'local_id' => $product->id,
            'woo_id'   => $product->woo_id,
            'action'   => $action,
            'payload'  => $payload,
        ];

        // Schrijf dry-run JSON per item
        $now  = now()->format('YmdHis');
        $path = storage_path("logs/woocommerce/dryrun-push-{$product->id}-{$now}.json");
        File::ensureDirectoryExists(dirname($path), 0755);
        File::put($path, json_encode(['dry_run' => $dryRun], JSON_PRETTY_PRINT));

        return response()->json([
            'message'      => 'Dry-run succesvol',
            'dry_run_file' => "logs/woocommerce/dryrun-push-{$product->id}-{$now}.json",
            'dry_run'      => $dryRun,
        ], 200);
    }
}

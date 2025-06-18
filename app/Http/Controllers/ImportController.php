<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use League\Csv\Reader;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Product;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Setting;
use App\Models\Subgroup;
use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\InvoiceLines;
use App\Models\ProductStock;
use App\Models\ProductStockHistory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\Image;
use App\Models\ImageLink;

class ImportController extends Controller{


    public function index()
    {
        $imports = [
            'categories' => 'Import categorieën',
            'subgroups' => 'Import subgroepen',
            'products' => 'Import producten',
            'brands' => 'Import merken',
            'suppliers' => 'Import leveranciers',
            'orders' => 'Import bestellingen',
            'purchase_orders' => 'Import inkooporders',
            'import_stock' => 'Import huidige voorraad',
            'import_costs' => 'Import kosten uit excel',
        ];
            
        return view('import.index', compact('imports'));
    }
    
    public function import(Request $request, $type)
    {
        $file = $request->file('csv_file');
        if (!$file) {
            return redirect()->back()->withErrors(['csv_file' => 'CSV file is required.']);
        }

        // CSV inlezen
        try {
            $csv = Reader::createFromPath($file->getRealPath(), 'r');
            $csv->setHeaderOffset(0);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['csv_file' => 'Fout bij het inlezen van het CSV-bestand.']);
        }

        $records = iterator_to_array($csv->getRecords());
        $records = array_filter($records, function ($record) {
            $trimmed = array_map('trim', $record);
            return count(array_filter($trimmed, fn($v) => $v !== '')) > 0;
        });

        // Voor return-values van import-helpers
        $result = null;
        $table  = null;
        $feedback = [];

        switch ($type) {
            case 'brands':
                $result = $this->importBrands($records);
                $table  = view('import.summary', ['importedItems' => $result])->render();
                break;

            case 'subgroups':
                $result = $this->importSubgroups($records);
                $table  = view('import.summary', ['importedItems' => $result])->render();
                break;

            case 'products':
                $result = $this->importProducts($records);
                break;

            case 'orders':
                $result = $this->importOrders($records);
                break;

            case 'purchase_orders':
                $result = $this->importPurchaseOrders($records);
                break;

            case 'import_costs':
                $result = $this->importCosts($records);
                break;
                
            case 'import_stock':
                $stockResult = $this->importStock($records);
                $result      = $stockResult['importedStocks'];
                $feedback    = [
                    'skippedSKUs'  => $stockResult['skippedSKUs'],
                    'updatedCount' => $stockResult['updatedCount'],
                ];
                break;

            case 'suppliers':
                $result = $this->importSuppliers($records);
                break;

            default:
                return redirect()->back()->withErrors(['type' => 'Invalid import type.']);
        }

        // Als helper een JsonResponse teruggeeft, daarin fouten checken
        if ($result instanceof JsonResponse) {
            $data = $result->getData(true);
            if (!empty($data['errors'])) {
                return redirect()
                    ->route('import.index')
                    ->withErrors($data['errors']);
            }
            // geen errors → success
            return redirect()
                ->route('import.index')
                ->with('success', ucfirst(str_replace('_', ' ', $type)) . ' succesvol geïmporteerd.');
        }

        // Anders: normale import zonder JSON-errors
        $redirect = redirect()
            ->route('import.index')
            ->with('success', ucfirst(str_replace('_', ' ', $type)) . ' import completed.');

        if ($table) {
            $redirect->with('table', $table);
        }
        if (!empty($feedback)) {
            $redirect->with('extraFeedback', $feedback);
        }

        return $redirect;
    }

    protected function importProducts(array $records)
    {
        foreach ($records as $rowData) {
            if (!isset($rowData['Artikelnummer'], $rowData['Productomschrijving'])) {
                logger()->warning('Mandatory fields are missing in row:', ['row' => $rowData]);
                continue;
            }
            $sku = trim($rowData['Artikelnummer']);
            $existingProduct = Product::where('SKU', $sku)->first();
            if ($existingProduct) {
                logger()->info('SKU already exists. Skipping row:', ['sku' => $sku]);
                continue;
            }

            $description = isset($rowData['Productomschrijving']) ? trim($rowData['Productomschrijving']) : 'Geen beschrijving';

            // Merk
            $brand = null;
            if (isset($rowData['Merk'])) {
                $brandName = trim($rowData['Merk']);
                if (!empty($brandName)) {
                    $brand = Brand::firstOrCreate(
                        ['name' => $brandName],
                        ['slug' => Str::slug($brandName)]
                    );
                }
            }

            // Categorieën
            $rawCategoryString = trim($rowData['Categorieën'] ?? '');
            $categoryPaths = array_filter(array_map('trim', explode(',', $rawCategoryString)));
            $categoryIds = [];
            foreach ($categoryPaths as $path) {
                $parts = array_map('trim', explode('>', $path));
                $parentId = null;
                foreach ($parts as $part) {
                    $category = Category::firstOrCreate(
                        ['name' => $part, 'parent_id' => $parentId],
                        ['slug' => Str::slug($part)]
                    );
                    $parentId = $category->id;
                }
                $categoryIds[] = $parentId;
            }
            $primaryCategoryId = $categoryIds[0] ?? null;

            // Subgroep
            $subgroupName = trim($rowData['Subgroep'] ?? '') ?: 'Onbekend';
            $subgroup = Subgroup::firstOrCreate(
                ['name' => $subgroupName],
                ['slug' => Str::slug($subgroupName)]
            );

            // Leverancier
            $supplierName = trim($rowData['Inkoop via'] ?? 'Onbekend');
            $supplier = Supplier::whereRaw('LOWER(name) = ?', [strtolower($supplierName)])->first();
            if (!$supplier) {
                $supplier = Supplier::create([
                    'name' => $supplierName, 
                    'status' => 'Actief'
                ]);
            }

            // Verzendklasse ophalen als Parameter
            $shippingInput = trim($rowData['Verzendklasse'] ?? '');
            $shippingParameter = null;
            if ($shippingInput) {
                $shippingParameter = Parameter::firstOrCreate(
                    ['key' => 'shipping_class', 'name' => $shippingInput],
                    ['value' => Str::slug($shippingInput), 'description' => 'Automatisch via import']
                );
            }

            $purchaseQuantityValue = is_numeric(trim($rowData['Inhoud inkoop'] ?? '')) ? trim($rowData['Inhoud inkoop']) : null;
            $saleQuantityValue = is_numeric(trim($rowData['Inhoud verkoop'] ?? '')) ? trim($rowData['Inhoud verkoop']) : 1;
            $salesChanelJson = json_encode(['vinted', 'beurs', 'facebook', 'website']);

            $product = Product::create([
                'sku' => $sku,
                'name' => trim($rowData['Productomschrijving']),
                'published' => (bool)trim($rowData['Actief']),
                'supplier_id' => $supplier->id,
                'category_id' => $primaryCategoryId,
                'categories' => json_encode($categoryIds),

                'brand_id' => $brand ? $brand->id : 1,
                'subgroup_id' => $subgroup ? $subgroup->id : 1,
                'purchase_quantity' => $purchaseQuantityValue,
                'sale_quantity' => $saleQuantityValue,
                'sales_chanel' => $salesChanelJson,
                'product_type' => 'Verkoop',
                'height' => trim(str_replace(',', '.', $rowData['Hoogte'])) ?: null,
                'width' => trim(str_replace(',', '.', $rowData['Breedte'])) ?: null,
                'depth' => trim(str_replace(',', '.', $rowData['Lengte'])) ?: null,
                'weight' => trim(str_replace(',', '.', $rowData['Gewicht'])) ?: null,
                'shipping_class' => $shippingParameter ? $shippingParameter->value : null,
                'description' => $description,
                'short_description' => isset($rowData['Korte beschrijving']) ? trim($rowData['Korte beschrijving']) : trim($rowData['Productomschrijving']),
                'long_description' => isset($rowData['Beschrijving']) ? trim($rowData['Beschrijving']) : trim($rowData['Productomschrijving']),
                'woo_id' => is_numeric(trim($rowData['WooID'] ?? '')) ? trim($rowData['WooID']) : null,
                'seo_title' => isset($rowData['Meta: _yoast_wpseo_focuskw']) ? trim($rowData['Meta: _yoast_wpseo_focuskw']) : null,
                'seo_description' => isset($rowData['Meta: _yoast_wpseo_metadesc']) ? trim($rowData['Meta: _yoast_wpseo_metadesc']) : null,
                'focus_keyword' => isset($rowData['Tags']) ? trim($rowData['Tags']) : null,
                'vat_rate_id' => 7,
                'available_for_web' => isset($rowData['Online tonen']) ? (bool)trim($rowData['Online tonen']) : 1,
            ]);
           

            // Afbeelding koppelen via Images + ImageLink
            if (!empty($rowData['Afbeelding'])) {
                $imageUrl = trim($rowData['Afbeelding']);
                $image = Image::firstOrCreate(
                    ['location' => $imageUrl],
                    [
                        'thumbnail_location' => $imageUrl, // tijdelijke fallback als er geen aparte thumbnail is
                        'description' => 'Imported via product import',
                        'original_filename' => basename($imageUrl),
                        'mime_type' => 'image/jpeg',
                        'status' => 'active',
                        'uploaded_by' => auth()->id()
                    ]
                );
                

                ImageLink::firstOrCreate([
                    'image_id' => $image->id,
                    'product_id' => $product->id,
                    'role' => 'Primary',
                    'publication' => true,
                    'order' => 1,
                ]);
            }

            $regularPrice = $this->cleanPrice(trim($rowData['Verkoopprijs'])) ?: 0;
            $vintedPrice = $this->cleanPrice(trim($rowData['Verkoopprijs Vinted']));

            Price::create([
                'product_id' => $product->id,
                'price' => $regularPrice,
                'type' => 'regular',
                'valid_from' => now(),
                'valid_till' => null,
            ]);

            if ($vintedPrice && $vintedPrice != $regularPrice) {
                Price::create([
                    'product_id' => $product->id,
                    'price' => $vintedPrice,
                    'type' => 'vinted',
                    'valid_from' => now(),
                    'valid_till' => null,
                ]);
            }

            if (isset($rowData['Tags'])) {
                $this->syncTags($product, $rowData['Tags']);
            }
        }
    }

protected function importBrands(array $records)
{
    $importedItems = [];
    foreach ($records as $rowData) {
        Log::info('Brand record: ' . json_encode($rowData));
        if (!isset($rowData['Name'])) {
            Log::warning('Naam ontbreekt in brand record: ' . json_encode($rowData));
            continue;
        }
        $slug = isset($rowData['slug']) ? trim($rowData['slug']) : Str::slug(trim($rowData['Name']));
        $brand = Brand::updateOrCreate(
            ['slug' => $slug],
            ['name' => trim($rowData['Name'])]
        );
        $importedItems[] = $brand;
    }
    return $importedItems;
}
protected function importSubgroups(array $records)
{
    $importedItems = [];
    foreach ($records as $rowData) {
        Log::info('Subgroup record: ' . json_encode($rowData));
        if (!isset($rowData['Name'])) {
            Log::warning('Naam ontbreekt in subgroup record: ' . json_encode($rowData));
            continue;
        }
        $slug = isset($rowData['slug']) ? trim($rowData['slug']) : Str::slug(trim($rowData['Name']));
        $subgroup = Subgroup::updateOrCreate(
            ['slug' => $slug],
            ['name' => trim($rowData['Name'])]
        );
        $importedItems[] = $subgroup;
    }
    return $importedItems;
}

/**
 * Import "kosten" records en update alleen de linked_key op de bestaande
 * FinanceTransaction.  Er worden GEEN nieuwe (reversal of schuld) transacties
 * aangemaakt; het openstaande bedrag kan je later handmatig overboeken.
 */


/**
 * Import "kosten" records en update alleen de linked_key op de bestaande
 * FinanceTransaction.  Er worden GEEN nieuwe (reversal of schuld) transacties
 * aangemaakt; het openstaande bedrag kan je later handmatig overboeken.
 */


protected function importCosts(array $records)
{
    $feedbackRows = [];
    Log::info('Start importCosts()', ['rows' => count($records)]);

    // ── statische mapping -------------------------------------------------
    $expenseMap = [
        'marketing'   => 'advertising_expense_account',
        'verzending'  => 'shipping_expense_account',
        // 'kosten' valt in de default hieronder
    ];
    $invoiceTypeMap = [
        'kosten'     => 'kosten',      // ID 134
        'marketing'  => 'marketing',   // ID 140
        'verzending' => 'shipping',    // ID 142
    ];

    DB::beginTransaction();
    try {
        foreach ($records as $index => $row) {
            $line        = $index + 2; // Excel‑regel (1‑based + header)
            $typeRaw     = strtolower(trim($row['Type'] ?? ''));
            $reference   = trim($row['Referentie'] ?? '');
            $responsible = strtolower(trim($row['Door'] ?? ''));
            $dateRaw     = trim($row['Datum'] ?? '');
            $description = trim($row['Omschrijving'] ?? '');

            Log::debug('Verwerk regel', compact('line', 'typeRaw', 'reference', 'responsible'));

            // 1) Lees bedragen
            $paidAmount = $this->cleanPrice($row['uit (geld)'] ?? '0');
            $openAmount = $this->cleanPrice($row['Open'] ?? '0');

            // ───────────────────────────── Inkoop  ──────────────────────────
            if ($typeRaw === 'inkoop') {
                // Skip zonder referentie of open bedrag
                if ($reference === '' || $openAmount <= 0) {
                    $reason = $reference === '' ? 'Inkoop zonder referentie' : 'Geen open bedrag';
                    Log::debug('Regel overgeslagen', compact('line', 'reason'));
                    $feedbackRows[] = compact('line') + ['status' => 'skipped', 'reason' => $reason];
                    continue;
                }

                // 2) Vind Invoice + originele transactie
                $invoice = Invoice::where('invoice_reference', $reference)->first();
                if (! $invoice) {
                    $reason = "Geen Invoice met referentie '{$reference}'";
                    Log::warning($reason, compact('line'));
                    $feedbackRows[] = compact('line') + ['status' => 'skipped', 'reason' => $reason];
                    continue;
                }
                $originalTxn = FinanceTransaction::where('invoice_id', $invoice->id)->first();
                if (! $originalTxn) {
                    $reason = "Geen FinanceTransaction voor Invoice #{$invoice->id}";
                    Log::warning($reason, compact('line'));
                    $feedbackRows[] = compact('line') + ['status' => 'skipped', 'reason' => $reason];
                    continue;
                }

                // 3) Linked_key aanpassen (géén echte boekingen)
                $linked      = json_decode($originalTxn->linked_key, true) ?? [];
                $bookedSoFar = (float) ($linked['amount_booked'] ?? 0.00);
                $newOpen     = round($openAmount, 2);
                $newBooked   = $bookedSoFar;            // niet verhogen
                $newTotal    = round($newBooked + $newOpen, 2);

                $debtKey = match($responsible) {
                    'sanne'  => 'debt_account_sanne',
                    'sander' => 'debt_account_sander',
                    default  => 'debt_account_main',
                };
                $debtAccount = (int) Setting::get($debtKey, null, 'financeaccount');

                $linkedKeyData = [
                    'total'            => $newTotal,
                    'amount_booked'    => $newBooked,
                    'amount_open'      => $newOpen,
                    'original_account' => $originalTxn->account_id,
                    'debt_account'     => ($debtAccount != $originalTxn->account_id) ? $debtAccount : null,
                    'booking_account'  => $responsible,
                ];
            // Automatisch op de schuldrekening bijboeken
            $this->updateAccountBalance($debtAccount, 'bij', $newOpen);// Automatisch op de schuldrekening bijboeken
            $this->updateAccountBalance($originalTxn->account_id, 'af', $newOpen);

                $originalTxn->update(['linked_key' => json_encode($linkedKeyData)]);

                Log::info('Linked_key bijgewerkt', ['line' => $line, 'invoice_id' => $invoice->id]);
                $feedbackRows[] = compact('line') + ['status' => 'updated', 'reason' => null];
                continue;
            }
            // ───────────────────────────── Einde Inkoop  ───────────────────

            
            /* ─────────────── KOSTEN ─────────────── */
            if (in_array($typeRaw, ['kosten', 'marketing', 'verzending'])) {
                if ($paidAmount <= 0) {
                    $feedbackRows[] = compact('line') + ['status' => 'skipped', 'reason' => 'Bedrag = 0'];
                    continue;
                }
                try {
                    $date = Carbon::createFromFormat('d-m-Y', $dateRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    throw new \Exception("Ongeldige datum op regel {$line}: '{$dateRaw}'");
                }

                // 1) Invoice + line (vereiste kolommen: name, type, date)
                $invoice = Invoice::create([
                    'invoice_reference' => 'COST-' . Str::uuid(),
                    'name'              => substr($description, 0, 191),
                    'type'              => $invoiceTypeMap[$typeRaw] ?? 'kosten',
                    'date'              => $date,          // kolom `date`
                    'status'            => 'betaald',         // of jouw default
                    'total_amount'      => $paidAmount,
                    'notes'             => $typeRaw,
                ]);
                $invoice->invoiceLines()->create([
                    'description'           => $description,
                    'amount_incl_vat_total' => $paidAmount,
                    'type'                  => $typeRaw,
                    'date'                  => $date,
                ]);

                // 2) Accounts
                $settingKey     = $expenseMap[$typeRaw] ?? 'operating_expense_account';
                $expenseAccount = (int) Setting::get($settingKey, null, 'financeaccount');
                $bankAccount    = (int) Setting::get('bank_account', null, 'financeaccount');

                // 3) Boekingen
                FinanceTransaction::create([
                    'account_id'       => $expenseAccount,
                    'debit_credit'     => 'bij',
                    'amount'           => $paidAmount,
                    'description'      => $description,
                    'invoice_id'       => $invoice->id,
                    'transaction_date' => $date,
                ]);
                FinanceTransaction::create([
                    'account_id'       => $bankAccount,
                    'debit_credit'     => 'af',
                    'amount'           => -$paidAmount,
                    'description'      => 'Betaling kosten: ' . $description,
                    'invoice_id'       => $invoice->id,
                    'transaction_date' => $date,
                ]);

                $feedbackRows[] = compact('line') + ['status' => 'booked-cost', 'invoice_id' => $invoice->id];
                continue;
            }
            /* ─────────── EINDE KOSTEN ──────────── */

            $feedbackRows[] = compact('line') + ['status' => 'skipped', 'reason' => 'Onbekend type'];
        }




        DB::commit();
        Log::info('importCosts() succesvol afgerond');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('importCosts() rollback', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
        return response()->json(['errors' => [$e->getMessage()], 'feedback' => $feedbackRows], 400);
    }

    return response()->json(['message' => 'Kosten & inkoop succesvol geïmporteerd.', 'feedback' => $feedbackRows]);
}


/**
 * Helper om een FinanceAccount op bij te werken
 */
protected function updateAccountBalance(int $accountId, string $debitCredit, float $amount): void
{
    $account = FinanceAccount::findOrFail($accountId);

    if ($debitCredit === 'bij') {
        $account->balance += $amount;
    } else {
        $account->balance -= $amount;
    }

    $account->save();
    Log::info("Account {$account->account_code} bijgewerkt door de import:", [
        'debit_credit' => $debitCredit,
        'amount'       => $amount,
        'new_balance'  => $account->balance,
    ]);
}

protected function importPurchaseOrders(array $records)
{
    $errors = [];
    $ordersGrouped = [];

    // 1) Groepeer per Inkooporder
    foreach ($records as $row) {
        $key = trim($row['Inkooporder'] ?? '');
        if (!$key) {
            $errors[] = 'Inkooporder ontbreekt in record: ' . json_encode($row);
            continue;
        }
        $ordersGrouped[$key][] = $row;
    }
    if (!empty($errors)) {
        return response()->json(['errors' => $errors], 400);
    }

    foreach ($ordersGrouped as $orderNr => $rows) {
        DB::beginTransaction();
        try {
            $first = $rows[0];
            $date  = Carbon::createFromFormat('d-m-Y', trim($first['Ingekocht op']))->format('Y-m-d');

            // 2) Leverancier
            $supplier = Supplier::firstOrCreate(
                ['name' => trim($first['Inkoopvia'])],
                ['status' => 'Actief', 'description' => $first['Beschrijving'] ?? null]
            );

            // 3) PurchaseOrder
            $po = PurchaseOrder::firstOrCreate(
                ['supplier_id' => $supplier->id, 'date' => $date],
                [
                    'notes'  => sprintf(
                        'Inkooporder %s via %s op %s (import uit Excel)',
                        $first['Inkooporder'],
                        $supplier->name,
                        $date
                    ),
                    'status' => (strtolower(trim($first['Afgerond'] ?? '')) === 'ja') ? 3 : 1,
                ]
            );

            // 4) Items & totalen
            $totals = ['net' => 0, 'vat' => 0, 'gross' => 0];
            foreach ($rows as $rowData) {
                $product   = Product::where('sku', trim($rowData['ArtikelNr']))->firstOrFail();
                $qty       = (float) $rowData['Inkoophoeveelheid'];
                $priceIncl = $this->cleanPrice($rowData['Inkoopwaarde Incl']);
                $vatRate   = 0.21;
                $netBulk   = $priceIncl / (1 + $vatRate);
                $factor    = $product->purchase_quantity / max($product->sale_quantity, 1);
                $netUnit   = $netBulk / $factor;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id'        => $product->id,
                    'sku'               => $product->sku,
                    'price_excl_bulk'   => round($netBulk, 2),
                    'price_excl_unit'   => round($netUnit, 2),
                    'price_incl_bulk'   => round($priceIncl, 2),
                    'price_incl_unit'   => round($priceIncl / $factor, 2),
                    'quantity'          => $qty,
                    'total'             => round($priceIncl * $qty, 2),
                ]);

                // voorraadmutatie
                $stock = ProductStock::firstOrNew(['product_id' => $product->id]);
                if ($po->status >= 1) {
                    $inc = $qty * $factor;
                    ProductStockHistory::create([
                        'product_id'   => $product->id,
                        'quantity'     => $inc,
                        'stock_action' => 'IN',
                        'reason'       => 'Inkooporder ' . $po->id . ' verwerkt',
                        'user_id'      => auth()->id(),
                        'changed_at'   => now(),
                    ]);
                    $stock->current_quantity += $inc;
                } else {
                    $stock->on_the_way_quantity += $qty;
                }
                $stock->save();

                $totals['net']   += round($netBulk * $qty, 2);
                $totals['vat']   += round(($priceIncl - $netBulk) * $qty, 2);
                $totals['gross'] += round($priceIncl * $qty, 2);
            }
            Log::info('Regel nummer ' . $orderNr . ' verwerkt', [
                'supplier' => $supplier->name,
                'po_id'    => $po->id,
                'totals'   => $totals,
            ]);
            // 5) Factuur & boeking alleen bij status 3
            if ($po->status === 3) {
                // Bereken due date 14 dagen na inkoopdatum
            
                // Maak factuur aan met dezelfde notes als de PO en excel-inkoopnummer als referentie
                $invoice = Invoice::create([
                    'purchase_order_id' => $po->id,
                    'supplier_id'       => $supplier->id,
                    'name'              => $supplier->name,
                    'type'              => 'inkoop',
                    'status'            => 'betaald',
                    'date'              => $date,
                    'description'       => 'Auto-factuur bij verwerking PO #' . $po->id,
                    'notes'              => $po->notes,
                    'invoice_reference' => $first['Inkooporder'],
                ]);
                // 5b) Factuurregels
                
                foreach ($po->purchaseOrderItems as $poi) {
                    $lineQty      = $poi->quantity;
                    $lineExcl     = round($poi->price_excl_bulk * $lineQty, 2);
                    $lineIncl     = round($poi->price_incl_bulk * $lineQty, 2);
                    $lineVatTotal = round($lineIncl - $lineExcl, 2);

                    InvoiceLines::create([
                        'invoice_id'            => $invoice->id,
                        'product_id'            => $poi->product_id,
                        // gebruik hier de productnaam als omschrijving
                        'description'           => $poi->product->name,
                        'quantity'              => $lineQty,
                        'amount_excl_vat_total' => $lineExcl,
                        'total_vat'             => $lineVatTotal,
                        'amount_incl_vat_total' => $lineIncl,
                        'vat_rate'              => $vatRate * 100,
                    ]);
                }

                // Totale factuurbedrag
                $invoiceTotal = $invoice->invoiceLines()->sum('amount_incl_vat_total');

                // Expense-account ophalen
                $expenseAccount = (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount');
                if ($expenseAccount <= 0) {
                    throw new \Exception('Inkooprekening is niet ingesteld (purchase_invoice_expense_account).');
                }

                // Boek direct op inkooprekening, inclusief invoice_id en purchase_order_id
                FinanceTransaction::create([
                    'account_id'        => $expenseAccount,
                    'debit_credit'      => 'bij',
                    'amount'            => $invoiceTotal,
                    'description'       => 'Inkoopfactuur #' . $invoice->invoice_number,
                    'invoice_id'        => $invoice->id,        // koppel naar de factuur
                    'purchase_order_id' => $po->id,             // én naar de PO
                    'transaction_date'  => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = "Fout bij inkooporder {$orderNr}: " . $e->getMessage();
            Log::error($e->getMessage(), ['purchase_order' => $orderNr]);
        }
    }

    if (!empty($errors)) {
        return response()->json(['errors' => $errors], 400);
    }

    return response()->json(['message' => 'Inkooporders succesvol geïmporteerd.']);
}


protected function importSuppliers(array $records)
{
    $importedItems = [];
    foreach ($records as $rowData) {
        Log::info('Supplier record: ' . json_encode($rowData));
        if (!isset($rowData['Name'])) {
            Log::warning('Naam ontbreekt in supplier record: ' . json_encode($rowData));
            continue;
        }
        // Gebruik case-insensitive matching
        $supplierName = trim($rowData['Name']);
        $supplier = Supplier::whereRaw('LOWER(name) = ?', [strtolower($supplierName)])->first();
        if (!$supplier) {
            $supplier = Supplier::create([
                'name' => $supplierName,
                'description' => isset($rowData['Description']) ? trim($rowData['Description']) : null,
            ]);
        }
        $importedItems[] = $supplier;
    }
    return $importedItems;
}


protected function importOrders(array $records)
{
    $errors        = [];
    $ordersGrouped = [];

    // ≡ 1) Bereid lijst met reeds geïmporteerde order‑nummers voor (CSV‑cache) ===
    $cacheFile = storage_path('imported_orders.csv');
    if (!file_exists($cacheFile)) {
        touch($cacheFile);
    }
    $imported = array_filter(array_map('str_getcsv', file($cacheFile)));
    $importedNumbers = array_column($imported, 0);

    // ≡ 2) Groepeer CSV‑regels per Bestelling‑nummer ============================
    foreach ($records as $row) {
        if (empty($row['Bestelling'])) {
            $errors[] = 'Bestelling ontbreekt in record: ' . json_encode($row);
            continue;
        }
        $ordersGrouped[trim($row['Bestelling'])][] = $row;
    }

    // ≡ 3) Verwerk elke order ================================================
    foreach ($ordersGrouped as $orderNr => $orderRows) {
        if (in_array($orderNr, $importedNumbers, true)) {
            continue; // al geïmporteerd
        }

        DB::beginTransaction();
        try {
            $first = $orderRows[0];

            // ---- Datum ----
            try {
                $date = Carbon::createFromFormat('d-m-Y', trim($first['Orderdatum']))->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \RuntimeException("Ongeldig datumformaat voor order {$orderNr}: {$first['Orderdatum']}");
            }

            // ---- Shipping method & Order source (Parameter records) ----
            $shippingMethod = $this->firstOrCreateParameter('shipping_method', $first['Verzendwijze']);
            $orderSource    = $this->firstOrCreateParameter('order_source',   $first['Kanaal']);

            // ---- Klantgegevens ----
            $address     = trim(($first['Naam'] ?? '') . ', ' . ($first['Adres'] ?? '') . ', ' . ($first['Postcode'] ?? '') . ', ' . ($first['Plaats'] ?? ''));
            $customer    = $this->parseCustomerAddress($address);
            $customer['name']        = $customer['name']        ?: trim($first['Naam'] ?? '');
            $customer['address']     = $customer['address']     ?: trim($first['Adres'] ?? '');
            $customer['postal_code'] = $customer['postal_code'] ?: trim($first['Postcode'] ?? '');
            $customer['city']        = $customer['city']        ?: trim($first['Plaats'] ?? '');
            $customer['country']     = $customer['country']     ?: trim($first['Country'] ?? 'Onbekend');

            // ---- Status ----
            $isPacked    = in_array(strtolower(trim($first['Ingepakt'] ?? '')), ['ja', 'true', '1'], true);
            $isCompleted = in_array(strtolower(trim($first['Afgerond check'] ?? '')), ['ja', 'true', '1'], true);
            $status      = $isPacked ? 3 : ($isCompleted ? 1 : 1); // 1 = bevestiging, 3 = verzonden

            // ---- Basis order ----
            $order = Order::create([
                'date'             => $date,
                'shipping_method'  => $shippingMethod->value,
                'order_source'     => $orderSource->value,
                'shipping_cost'    => $this->cleanPrice($first['Verzendkosten'] ?? '0'),
                'customer_name'    => $customer['name'],
                'customer_address' => $customer['address'],
                'postal_code'      => $customer['postal_code'],
                'city'             => $customer['city'],
                'country'          => $customer['country'],
                'notes'            => trim($first['Omschrijving'] ?? ''),
                'status'           => $status,
                'user_id'          => auth()->id(),
                'total_purchase_price' => 0,
                'total_sales_price'    => 0,
                'total_vat_amount'     => 0,
            ]);

            // ---- Order‑items + voorraad ----
            $totals = ['purchase' => 0, 'sales' => 0, 'vat' => 0];
            foreach ($orderRows as $item) {
                // Validatie
                if (empty($item['Artnummer']) || empty($item['Aantal'])) {
                    $errors[] = "Verplichte velden ontbreken in order {$orderNr}: " . json_encode($item);
                    continue;
                }

                $product = $this->resolveProduct($item);
                if (!$product) {
                    $errors[] = "Product niet gevonden (SKU/naam) in order {$orderNr}: " . json_encode($item);
                    continue;
                }

                $qty = (float) $item['Aantal'] ?: 1;
                $purchasePriceTotal = $this->cleanPrice($item['Inkoopwaarde'] ?? '0');
                $salesPriceTotal    = $this->cleanPrice($item['Verkoopwaarde'] ?? '0');
                $unitPurchase       = $purchasePriceTotal / $qty;
                $unitSales          = $salesPriceTotal / $qty;
                $vatRate            = (float) ($item['VatRate'] ?? '0.21');
                $vatAmountPerUnit   = $unitSales * $vatRate;

                OrderItem::create([
                    'order_id'               => $order->id,
                    'product_id'             => $product->id,
                    'quantity'               => $qty,
                    'purchase_price'         => round($unitPurchase, 2),
                    'original_sales_price'   => round($unitSales, 2),
                    'calculated_sales_price' => round($salesPriceTotal, 2),
                    'vat_amount'             => round($vatAmountPerUnit * $qty, 2),
                    'vat_rate_id'            => $product->vat_rate_id,
                ]);

                // Voorraadmutatie (OUT)
                $stock = ProductStock::firstOrCreate(['product_id' => $product->id]);
                $decrement = min($qty, $stock->current_quantity);
                $stock->decrement('current_quantity', $decrement);
                ProductStockHistory::create([
                    'product_id'   => $product->id,
                    'quantity'     => $decrement,
                    'stock_action' => 'OUT',
                    'reason'       => 'Order ' . $order->id . ' import',
                    'user_id'      => auth()->id(),
                    'changed_at'   => now(),
                ]);

                $totals['purchase'] += $purchasePriceTotal;
                $totals['sales']    += $salesPriceTotal;
                $totals['vat']      += $vatAmountPerUnit * $qty;
            }

            // ---- Update order‑totalen ----
            $order->update([
                'total_purchase_price' => round($totals['purchase'], 2),
                'total_sales_price'    => round($totals['sales'], 2),
                'total_vat_amount'     => round($totals['vat'], 2),
            ]);

            // ---- Financiële transactie ----
            FinanceTransaction::create([
                'account_id' => 1, // TODO: correct account kiezen
                'amount'     => $order->shipping_cost + $totals['sales'],
                'type'       => 'sale',
                'status'     => 'confirmed',
                'description'=> 'Order #' . $order->id . ' import',
                'direction'  => 'in',
                'debit_credit' => 'bij',
                'reference'  => ['table' => 'orders', 'key' => $order->id],
            ]);

            // ---- Log & cache ----
            logger()->info("Order {$orderNr} geïmporteerd (#{$order->id})");
            file_put_contents($cacheFile, $orderNr . PHP_EOL, FILE_APPEND);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = "Fout bij order {$orderNr}: " . $e->getMessage();
            logger()->error($e->getMessage(), ['order' => $orderNr]);
        }
    }

    if ($errors) {
        return response()->json(['errors' => $errors], 400);
    }

    return response()->json(['message' => 'Bestellingen succesvol geïmporteerd.']);
}



/**
 * Helperfunctie om een parameter op te zoeken op basis van key en inputwaarde (case-insensitief).
 *
 * @param string $key
 * @param string|null $input
 * @param array $defaultCandidates
 * @return string|null
 */
protected function getParameterValue(string $key, ?string $input, array $defaultCandidates = [])
{
    if (!empty($input)) {
        $param = Parameter::where('key', $key)
            ->whereRaw('LOWER(name) = ?', [strtolower($input)])
            ->first();
        if ($param) {
            return $param->value;
        }
    }
    foreach ($defaultCandidates as $candidate) {
        $param = Parameter::where('key', $key)
            ->whereRaw('LOWER(name) = ?', [strtolower($candidate)])
            ->first();
        if ($param) {
            return $param->value;
        }
    }
    return null;
}

/**
 * Helperfunctie om een gecombineerde klantadresstring te parsen.
 *
 * Verwacht een string in de vorm:
 * "Naam, Adres, Postcode, Plaats"
 *
 * Retourneert een array met:
 * - 'name'
 * - 'address'
 * - 'postal_code'
 * - 'city'
 *
 * @param string $combined
 * @return array
 */
protected function parseCustomerAddress(string $combined)
{
    // Regex die het adres goed splitst, met een extra check voor het land
    if (preg_match('/^(.*?),\s*(.*?),\s*([\d]{4}\s?[A-Z]{2}),\s*(.*?)(?:,\s*(.*))?$/i', $combined, $matches)) {
        return [
            'name' => trim($matches[1]), // Naam
            'address' => trim($matches[2]), // Adres
            'postal_code' => strtoupper(str_replace(' ', '', $matches[3])), // Postcode
            'city' => ucwords(strtolower(trim($matches[4]))), // Stad
            'country' => isset($matches[5]) ? ucwords(strtolower(trim($matches[5]))) : '', // Land
        ];
    }

    // Als de regex niet matcht, geef een standaard waarde terug
    return [
        'name' => '', // Als er geen naam kan worden geparsed
        'address' => '',
        'postal_code' => '',
        'city' => '',
        'country' => '',  // Voeg land toe als leeg
    ];
}


/**
 * Helperfunctie om een samenhangend adres te standaardiseren.
 *
 * Verwacht drie losse velden:
 * - $street: Straat + huisnummer
 * - $postcode: Postcode
 * - $city: Plaats
 *
 * Retourneert een nette, samengevoegde adresstring.
 *
 * @param string $street
 * @param string $postcode
 * @param string $city
 * @return string
 */
protected function standardizeAddress(string $street, string $postcode, string $city)
{
    $combined = trim($street . ', ' . $postcode . ', ' . $city);
    $parsed = $this->parseCustomerAddress($combined);
    $parts = array_filter([$parsed['address'], $parsed['postal_code'], $parsed['city']]);
    return implode(', ', $parts);
}


private function cleanPrice($price)
{
    // Log de originele prijs en de stappen
       // Verwerk de prijs: verwijder € en spaties, zet komma om naar punt, en converteer naar float
    return floatval(str_replace(',', '.', str_replace(['€', ' '], '', $price)));
}

    
    private function cleanRowData(array $row)
    {
        return array_map(function ($value) {
            $value = preg_replace('/[\r\n\t]+/', ' ', $value);
            $value = preg_replace('/[^\p{L}\p{N}\s]/u', '', $value);
            return trim($value);
        }, $row);
    }
    protected function importStock(array $records)
{
    $importedStocks = [];
    $skippedSKUs = [];
    $updatedCount = 0;
    
    foreach ($records as $rowData) {
        Log::info('Stock record: ' . json_encode($rowData));
        if (!isset($rowData['SKU']) || !isset($rowData['Stock'])) {
            Log::warning('Verplichte velden ontbreken in stock record: ' . json_encode($rowData));
            continue;
        }
    
        $sku = trim($rowData['SKU']);
        $newStock = (int)$rowData['Stock'];
    
        $product = Product::where('sku', $sku)->first();
        if (!$product) {
            $skippedSKUs[] = $sku;
            continue;
        }
    
        $productStock = ProductStock::firstOrNew(['product_id' => $product->id]);
        $difference = $newStock - ($productStock->current_quantity ?? 0);
    
        $productStock->current_quantity = $newStock;
        $productStock->product_id = $product->id;
        $productStock->save();
    
        ProductStockHistory::create([
            'product_id'   => $product->id,
            'sku'          => $sku,
            'quantity'     => $difference,
            'stock_action' => $difference > 0 ? 'IN' : 'OUT',
            'reason'       => 'Import uit beheer Excel',
            'user_id'      => auth()->id(),
            'changed_at'   => now(),
        ]);
    
        $importedStocks[] = $productStock;
        $updatedCount++;
    }
    
    Log::info('Aantal overgeslagen SKU\'s: ' . count($skippedSKUs));
    Log::info('Overgeslagen SKU\'s: ' . implode(', ', $skippedSKUs));
    Log::info('Aantal succesvol bijgewerkte artikelen: ' . $updatedCount);
    
    return [
        'importedStocks' => $importedStocks,
        'skippedSKUs'    => $skippedSKUs,
        'updatedCount'   => $updatedCount,
    ];
}

    private function syncTags(Product $product, ?string $tags)
    {
        if (!$tags) {
            $product->tags()->detach(); // Als geen tags, verwijder koppeling
            return;
        }

        $tagNames = explode(',', $tags); // Omzetten naar array
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            $tag = Tag::firstOrCreate(['name' => $tagName]); // Zoek of maak tag
            $tagIds[] = $tag->id;
        }

        $product->tags()->sync($tagIds); // Koppelen aan product
    }
    // ------------------------------------------------------------
//  Hulp‑methoden (intern – zelfde controller)
// ------------------------------------------------------------
private function firstOrCreateParameter(string $key, ?string $value)
{
    $value = trim($value ?? 'Onbekend');
    $param = Parameter::where('key', $key)->whereRaw('LOWER(name) = ?', [strtolower($value)])->first();
    if ($param) {
        return $param;
    }
    return Parameter::create([
        'key'   => $key,
        'name'  => ucfirst(strtolower($value)),
        'value' => strtolower($value),
    ]);
}

private function resolveProduct(array $item): ?Product
{
    $sku         = trim($item['Artnummer']);
    $articleName = trim($item['Artikel']);
    $known       = ['Korting', 'Transactiekosten overig', 'IDEAL kosten'];
    // Zoek op naam als het om een "speciale" regel gaat
    if (preg_match('/(' . implode('|', $known) . ')/i', $articleName)) {
        return Product::where('name', 'like', "%{$articleName}%")->first();
    }
    return Product::where('sku', $sku)->first();
}

}

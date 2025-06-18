<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    HomeController, CategoryController, BrandController, TagController, SubgroupController, ProductController, 
    ProductStockController, ParameterController, ImageController, ImageLinkController, SupplierController, 
    PriceController, PurchaseOrderController, CashbookController, PurchaseOrderItemController, OrderController, 
    ImportController, LocationController, InventoryController, BatchImageUploadController, UserSettingController, 
    InvoiceController, TaxController, FinancialTransactionController, WooCommerceController,FinanceAccountController,
    FinanceTransactionController,VatController,BalanceController,InvoiceLinesController,SettingController,WidgetController,
    OrderImportController,ReportController
    };

// ✅ Openbare routes (alleen voor gasten)
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
    Route::get('/', fn() => redirect('/login'))->name('home');
   
});


// ✅ Middleware voor ingelogde gebruikers
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    
    // User settings
    Route::prefix('settings')->group(function () {
        Route::get('/settings/widget', [ImportController::class, 'index'])->name('settings.widget');
        Route::get('{page}', [UserSettingController::class, 'getSettings']);
        Route::post('/', [UserSettingController::class, 'saveSettings']);
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('countBatch/{location?}', [InventoryController::class, 'countBatch'])->name('inventory.countBatch');
        Route::post('updateStock', [InventoryController::class, 'updateStock'])->name('inventory.updateStock');
        Route::get('backInStock', [InventoryController::class, 'backInStock'])->name('inventory.backInStock');
        Route::post('/back_in_stock', [InventoryController::class, 'procesInStock'])->name('inventory.back_in_stock');
    });

    // tags
    Route::get('/tags/search', [TagController::class, 'search']);
    // categories -> verwekren
    Route::post('/categories/{category}/sync-woo', [CategoryController::class, 'syncWithWoo'])->name('categories.syncWoo');
    // BRands -> erwerken
    Route::post('/brands/{brand}/sync-woo', [BrandController::class, 'syncWithWoo'])->name('brands.syncWoo');

    // Producten
    Route::prefix('products')->group(function () {
        Route::post('/update-vinted-field', [ProductController::class, 'updateVintedField'])->name('products.updateVintedField');
        Route::get('bulkEdit', [ProductController::class, 'bulkEdit'])->name('products.bulkEdit');
        Route::put('bulkUpdate', [ProductController::class, 'bulkUpdate'])->name('products.bulkUpdate');
        Route::get('{id}/gallery', [ProductController::class, 'gallery'])->name('products.gallery');
        Route::post('updateStatus', [ProductController::class, 'updateStatus'])->name('products.updateStatus');
        Route::post('bulk-assign-location', [ProductController::class, 'bulkAssignLocation'])->name('products.bulkAssignLocation');
        Route::get('{id}/copy', [ProductController::class, 'copy'])->name('products.copy');
        Route::post('prices/updateSingle', [PriceController::class, 'updatePrice'])->name('prices.updateSingle');
        Route::get('search', [ProductController::class, 'search'])->name('products.search');
        Route::get('bulk-edit', [ProductController::class, 'bulkEdit'])->name('products.bulkEdit');

    });
    
    // Purchases
    Route::prefix('purchaseOrders/{purchaseOrderId}')->group(function () {
        Route::post('/control', [PurchaseOrderController::class, 'control'])->name('purchase_orders.control');
        Route::post('/process', [PurchaseOrderController::class, 'process'])->name('purchase_orders.process');
        Route::get('/control', [PurchaseOrderController::class, 'control'])->name('purchase_orders.control');
        Route::get('/items', [PurchaseOrderController::class, 'items'])->name('purchase_orders.items');
    });

    // Images
    Route::prefix('image')->group(function () {
        Route::delete('{id}', [ImageController::class, 'destroy'])->name('image.destroy');
        Route::post('upload-csv', [ImageController::class, 'uploadCSV'])->name('image.uploadCSV');

    });
     // Invoices en Invoice Lines
     Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    
        // Routes voor invoice lines gekoppeld aan een specifieke factuur
        Route::get('{invoice}/lines', [InvoiceLinesController::class, 'index'])->name('invoices.lines.index');
        Route::post('{invoice}/lines', [InvoiceLinesController::class, 'store'])->name('invoices.lines.store');
    });
    
    // Orders & Picklist
    Route::prefix('orders')->group(function () {
        Route::post('/submit', [OrderController::class, 'store'])->name('orders.store');
        Route::post('{id}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('confirm_delete/{id}', [OrderController::class, 'confirmDelete'])->name('orders.confirm_delete');
        Route::post('picklist', [OrderController::class, 'showPickList'])->name('orders.picklist');
        Route::get('pack/{selectedOrders}', [OrderController::class, 'packOrders'])->name('orders.pack');
        Route::post('send/{id}', [OrderController::class, 'sendOrder'])->name('orders.send');
        Route::post('destroy/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::get('/soldSince', [OrderController::class, 'showSoldSinceAndItems'])->name('orders.soldSinceAndItems');
        Route::post('/soldSince', [OrderController::class, 'showSoldSinceAndItems'])->name('orders.showSoldSinceAndItems');
        Route::post('/save-selected-items', [OrderController::class, 'saveSelectedItems']); // voor bijhouden in de verkoop sinds
        Route::get('/import/manual', [WooCommerceController::class, 'manualImportForm'])->name('orders.manualForm');
        Route::post('/import/manual', [WooCommerceController::class, 'manualImportSubmit'])->name('orders.manualSubmit');
        
    });

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/store', [SettingController::class, 'store'])->name('settings.store');
        Route::post('/update/{id}', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/deactivate/{id}', [SettingController::class, 'deactivate'])->name('settings.deactivate');
        Route::post('/activate/{id}', [SettingController::class, 'activate'])->name('settings.activate');
        Route::get('/{id}/logs', [SettingController::class, 'getActivityLogs'])->name('settings.logs');
    });
    
    // productStock
    Route::get('log-updated-products', [ProductStockController::class, 'logUpdatedProducts']);

    // Imports
    Route::prefix('import')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('import.index');
        Route::post('{type}', [ImportController::class, 'import'])->name('import.run');
    });
    // REports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])
            ->name('reports.index');
        Route::get('{report}/edit-data',[ReportController::class,'editData'])
            ->name('reports.editData');
            Route::get('{report}/filters',[ReportController::class,'filters'])
                ->name('reports.filters');
        });

    // generate via GET (en optioneel POST)
    Route::match(['get','post'], 'reports/generate', [ReportController::class, 'generate'])
        ->name('reports.generate');
            // Financiële resources
        Route::prefix('financial')->name('financial.')->group(function () {
            Route::resources([
                'invoices'          => InvoiceController::class,
                'accounts'          => FinanceAccountController::class,
                'transactions'      => FinanceTransactionController::class,
                'vat'               => VatController::class,
                'balance_sheet'     => BalanceController::class,
            ]);

            // financiele subroutes
            Route::post('accounts/{id}/toggle-active', [FinanceAccountController::class, 'toggleActive'])->name('accounts.toggle-active');
            Route::get('accounts/{id}/logs', [FinanceAccountController::class, 'logs'])->name('accounts.logs');
            Route::put('accounts/{id}/update-balance', [FinanceAccountController::class, 'updateBalance'])->name('accounts.update-balance');
            
            // schulden
            route::get('debt-overview',[FinanceAccountController::class,'debtOverview'])->name('debt-overview');
            route::put('debt-change/{account}',[FinanceAccountController::class,'debtReversalUpdate'])->name('debt-change');
            route::get('debt-transactions/{account}',[FinanceTransactionController::class,'debtTransactions'])->name('debt_transactions');
            Route::put('debt-settlement', [FinanceTransactionController::class, 'settleDebt'])->name('debt_settlement');
            // transaction            
            Route::get('transactions/{id}/logs', [FinanceTransactionController::class, 'logs'])->name('transactions.logs');
            
            //Vat
            Route::get('vat/{id}/json', [VatController::class, 'json'])->name('vat.json');
        });

    // ✅ Resources
    Route::resources([
        'reports' => ReportController::class,
        'categories' => CategoryController::class,
        'brands' => BrandController::class,
        'tags' => TagController::class,
        'subgroups' => SubgroupController::class,
        'products' => ProductController::class,
        'parameters' => ParameterController::class,
        'image' => ImageController::class,
        'image_links' => ImageLinkController::class,
        'suppliers' => SupplierController::class,
        'prices' => PriceController::class,
        'purchases' => PurchaseOrderController::class,
        'orders' => OrderController::class,
        'locations' => LocationController::class,
        'inventory' => InventoryController::class,
        'woo' => WooCommerceController::class,
        'settings' => SettingController::class,
        'widgets' => WidgetController::class,
    ]);
});

// ✅ Auth-routes inladen
require __DIR__.'/auth.php';

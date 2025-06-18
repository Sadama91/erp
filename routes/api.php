<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderImportController;
use App\Http\Controllers\ProductSyncController;
use App\Http\Controllers\PurchaseOrder;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('woo-import/orders', [OrderImportController::class, 'store']);

Route::post('products/push', [ProductSyncController::class, 'pushBatch']);
Route::put('products/push/{id}', [ProductSyncController::class, 'pushSingle']);
Route::get('wc-test', [ProductSyncController::class, 'testConnection']);


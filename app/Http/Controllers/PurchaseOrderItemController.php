<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderItemController extends Controller
{
    // Toon alle items voor een inkooporder
    public function index($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchaseOrderId);
        $items = $purchaseOrder->items;
        return view('purchase_order_items.index', compact('items', 'purchaseOrder'));
    }

    // Toon de details van een specifiek inkooporderitem
    public function show($purchaseOrderId, $id)
    {
        $item = PurchaseOrderItem::findOrFail($id);
        return view('purchase_order_items.show', compact('item'));
    }

    // Toon het formulier voor het aanmaken van een nieuw item
    public function create($purchaseOrderId)
    {
        return view('purchase_order_items.create', compact('purchaseOrderId'));
    }

    // Sla een nieuw item op
    public function store(Request $request, $purchaseOrderId)
    {
        $request->validate([
            'product_id' => 'required',
            'sku' => 'required|string',
            'description' => 'required|string',
            'price_excl_unit' => 'required|numeric',
            'price_excl_bulk' => 'required|numeric',
            'price_incl_unit' => 'required|numeric',
            'price_incl_bulk' => 'required|numeric',
            'quantity' => 'required|integer',
        ]);

        PurchaseOrderItem::create(array_merge($request->all(), ['purchase_order_id' => $purchaseOrderId]));

        return redirect()->route('purchase_order_items.index', $purchaseOrderId)->with('success', 'Item succesvol aangemaakt.');
    }

    // Toon het formulier voor het bewerken van een item
    public function edit($purchaseOrderId, $id)
    {
        $item = PurchaseOrderItem::findOrFail($id);
        return view('purchase_order_items.edit', compact('item', 'purchaseOrderId'));
    }

    // Werk een item bij
    public function update(Request $request, $purchaseOrderId, $id)
    {
        $request->validate([
            'product_id' => 'required',
            'sku' => 'required|string',
            'description' => 'required|string',
            'price_excl_unit' => 'required|numeric',
            'price_excl_bulk' => 'required|numeric',
            'price_incl_unit' => 'required|numeric',
            'price_incl_bulk' => 'required|numeric',
            'quantity' => 'required|integer',
        ]);

        $item = PurchaseOrderItem::findOrFail($id);
        $item->update($request->all());

        return redirect()->route('purchase_order_items.index', $purchaseOrderId)->with('success', 'Item succesvol bijgewerkt.');
    }

    // Verwijder een item
    public function destroy($purchaseOrderId, $id)
    {
        $item = PurchaseOrderItem::findOrFail($id);
        $item->delete();

        return redirect()->route('purchase_order_items.index', $purchaseOrderId)->with('success', 'Item succesvol verwijderd.');
    }
}

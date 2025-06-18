<?php

namespace App\Http\Controllers;

use App\Models\InvoiceLines;
use Illuminate\Http\Request;

class InvoiceLinesController extends Controller
{
    /**
     * Haal een lijst van factuurregels op.
     */
    public function index()
    {
        $invoiceLines = InvoiceLines::with('invoice', 'product')->get();
        return response()->json($invoiceLines);
    }

    /**
     * Sla een nieuwe factuurregel op.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_id'             => 'required|exists:invoices,id',
            'product_id'             => 'nullable|exists:products,id',
            'quantity'               => 'required|integer|min:1',
            'amount_excl_vat_total'  => 'required|numeric',
            'amount_incl_vat_total'  => 'required|numeric',
            'total_vat'              => 'required|numeric',
            'vat_rate'               => 'required|numeric',
            'remarks'                => 'nullable|string',
        ]);

        $invoiceLine = InvoiceLines::create($validatedData);

        // Herlaad om relaties mee te nemen
        $invoiceLine->load('invoice', 'product');

        return response()->json($invoiceLine, 201);
    }

    /**
     * Toon een specifieke factuurregel.
     */
    public function show($id)
    {
        $invoiceLine = InvoiceLines::with('invoice', 'product')->findOrFail($id);
        return response()->json($invoiceLine);
    }

    /**
     * Update een bestaande factuurregel.
     */
    public function update(Request $request, $id)
    {
        $invoiceLine = InvoiceLines::findOrFail($id);

        $validatedData = $request->validate([
            'invoice_id'             => 'sometimes|required|exists:invoices,id',
            'product_id'             => 'nullable|exists:products,id',
            'quantity'               => 'sometimes|required|integer|min:1',
            'amount_excl_vat_total'  => 'sometimes|required|numeric',
            'amount_incl_vat_total'  => 'sometimes|required|numeric',
            'total_vat'              => 'sometimes|required|numeric',
            'vat_rate'               => 'sometimes|required|numeric',
            'remarks'                => 'nullable|string',
        ]);

        $invoiceLine->update($validatedData);

        return response()->json($invoiceLine);
    }

    /**
     * Verwijder een factuurregel.
     */
    public function destroy($id)
    {
        $invoiceLine = InvoiceLines::findOrFail($id);
        $invoiceLine->delete();
        return response()->json(null, 204);
    }
}

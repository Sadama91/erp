<?php

namespace App\Http\Controllers;

use App\Models\Vat;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Log;

class VatController extends Controller
{
    /**
     * Geeft een overzicht van BTW-transacties met filtermogelijkheden.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Ophalen van filterwaarden uit de request
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $source    = $request->input('source');

        // Begin met de query op het Vat-model
        $query = Vat::query();

        // Filteren op periode als beide data aanwezig zijn
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Filteren op bron (bijvoorbeeld 'order', 'kosten', 'inkoop', 'verkoop', etc.)
        if ($source) {
            $query->where('vat_transaction_type', $source);
        }

        // Ophalen en pagineren van de resultaten
        $vats = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('financial.vat', compact('vats', 'startDate', 'endDate', 'source'));
    }

    /**
     * Maak een nieuwe BTW‑regel aan.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id'           => 'nullable|exists:products,id',
            'order_order_id'       => 'nullable|exists:orders,id',
            'invoice_line_id'      => 'nullable|exists:invoice_lines,id',
            'amount_incl'          => 'required|numeric',
            'amount_excl'          => 'required|numeric',
            'vat_amount'           => 'required|numeric',
            'vat_rate'             => 'required|numeric',
            'vat_transaction_type' => 'required|in:betalen,ontvangen',
            'financial_details'    => 'nullable|json',
        ]);

        $vat = Vat::create($validatedData);
        $vat->load(['product', 'order', 'invoiceLine']);

        return response()->json($vat, 201);
    }

    /**
     * Toon een specifieke BTW‑regel.
     */
    public function show($id)
    {
        $vat = Vat::with(['product', 'order', 'invoiceLine'])->findOrFail($id);
        return response()->json($vat);
    }

    /**
     * Update een bestaande BTW‑regel.
     */
    public function update(Request $request, $id)
    {
        $vat = Vat::findOrFail($id);

        $validatedData = $request->validate([
            'product_id'           => 'nullable|exists:products,id',
            'order_order_id'       => 'nullable|exists:orders,id',
            'invoice_line_id'      => 'nullable|exists:invoice_lines,id',
            'amount_incl'          => 'sometimes|required|numeric',
            'amount_excl'          => 'sometimes|required|numeric',
            'vat_amount'           => 'sometimes|required|numeric',
            'vat_rate'             => 'sometimes|required|numeric',
            'vat_transaction_type' => 'sometimes|required|in:betalen,ontvangen',
            'financial_details'    => 'nullable|json',
        ]);

        $vat->update($validatedData);
        return response()->json($vat);
    }

    /**
     * Verwijder een BTW‑regel.
     */
    public function destroy($id)
    {
        $vat = Vat::findOrFail($id);
        $vat->delete();
        return response()->json(null, 204);
    }
    
    /**
     * Retourneert de financiële transactie en de bijbehorende logs als JSON.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */

     public function json($id)
     {
         $vat = Vat::with('financeTransaction')
            ->where('finance_transaction_id', $id)
            ->first();
         if (!$vat) {
             return response()->json([
                 'error' => 'VAT-record niet gevonden',
                 'Id' => $id,
             ], 404);
         }
         $logs = Activity::where(function($query) use ($vat) {
            // Logs voor het VAT-record
            $query->where('subject_id', $vat->id)
                  ->where('subject_type', Vat::class);
        })
        ->orWhere(function($query) use ($vat) {
            // Logs voor de gekoppelde FinancialTransaction
            $query->where('subject_id', $vat->finance_transaction_id)
                  ->where('subject_type', FinanceTransaction::class);
        })
        ->orderBy('created_at', 'desc')
        ->get();
        
        
         
         return response()->json([
             'vat'                   => $vat,
             'finance_transaction'   => $vat->financeTransaction,
             'logs'                  => $logs,
         ]);
     }
     
}

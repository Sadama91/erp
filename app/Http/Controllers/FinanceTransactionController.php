<?php

namespace App\Http\Controllers;

use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class FinanceTransactionController extends Controller
{
    /**
     * Haal een lijst van alle financiële transacties op en toon deze in een view.
     */
    public function index()
    {
        $transactions = FinanceTransaction::with([
            'financeAccount',
            'invoice',
            'order',
            'invoiceLine',
            'orderItem'
                ])
        ->orderByDesc('id')
        ->get();

        return view('financial.transactions', compact('transactions'));
    }

    /**
     * Maak een nieuwe financiële transactie aan.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'account_id'       => 'required|exists:finance_accounts,id',
            'debit_credit'     => 'required|in:debit,credit',
            'amount'           => 'required|numeric',
            'description'      => 'nullable|string',
            'linked_key'       => 'nullable|json',
            'invoice_id'       => 'nullable|exists:invoices,id',
            'order_id'         => 'nullable|exists:orders,id',
            'invoice_line_id'  => 'nullable|exists:invoice_lines,id',
            'order_item_id'    => 'nullable|exists:order_items,id',
            'transaction_date' => 'nullable|date',
        ]);

        $transaction = FinanceTransaction::create($validatedData);
        $transaction->load(['financeAccount', 'invoice', 'order', 'invoiceLine', 'orderItem']);

        activity('finance_transaction')
            ->performedOn($transaction)
            ->withProperties(['attributes' => $validatedData])
            ->log('created');

        return response()->json($transaction, 201);
    }

    /**
     * Toon een specifieke financiële transactie.
     */
    public function show($id)
    {
        $transaction = FinanceTransaction::with([
            'financeAccount',
            'invoice',
            'order',
            'invoiceLine',
            'orderItem'
        ])->findOrFail($id);

        return response()->json($transaction);
    }


/**
 * JSON‑endpoint: openstaande schuldtransacties voor een rekening.
 */
public function debtTransactions(int $accountId)
{
    $transactions = FinanceTransaction::whereNotNull('linked_key')
        ->whereRaw(
            "CAST(JSON_UNQUOTE(JSON_EXTRACT(linked_key,'$.debt_account')) AS UNSIGNED) = ?",
            [$accountId]
        )
        ->orderBy('transaction_date', 'asc')
        ->get();

    // Verrijk voor frontend
    $transactions->each(function ($tx) {
        $tx->linked_key_data = json_decode($tx->linked_key, true);
        $tx->amount_open = $tx->linked_key_data['total'] - $tx->linked_key_data['amount_booked'];
    });
       return response()->json(['transactions' => $transactions->values()]);
}

/**
 * Verwerk (gedeeltelijke) schuldafboeking.
 *
 * - Als `transaction_ids[]` aanwezig is, worden die transacties in dezelfde volgorde verwerkt.
 * - Restbedrag wordt automatisch toegepast op overige open transacties (oudste eerst).
 * - Eventueel overblijvend bedrag wordt geboekt als vooruitbetaalde schuld.
 */
public function settleDebt(Request $request)
{
    $validated = $request->validate([
        'account_id'       => 'required|integer',
        'amount'           => 'required|numeric|min:0.01',
        'description'      => 'required|string|max:255',
        'transaction_ids'  => 'array',
        'transaction_ids.*'=> 'integer',
    ]);

    $accountId       = (int) $validated['account_id'];
    $remainingAmount = (float) $validated['amount'];
    $description     = $validated['description'];
    $explicitIds     = $validated['transaction_ids'] ?? [];

    // Basis‑selectie: alle open transacties voor dit account
    $baseQuery = FinanceTransaction::whereNotNull('linked_key')
        ->whereRaw(
            "CAST(JSON_UNQUOTE(JSON_EXTRACT(linked_key,'$.debt_account')) AS UNSIGNED) = ?",
            [$accountId]
        )
        ->orderBy('transaction_date', 'asc');

    // 1. Transacties die door gebruiker gekozen zijn
    $explicit = collect();
    if ($explicitIds) {
        $idOrder  = implode(',', $explicitIds);
        $explicit = (clone $baseQuery)
            ->whereIn('id', $explicitIds)
            ->orderByRaw("FIELD(id, {$idOrder})")
            ->get();
    }

    // 2. Overige open transacties (oudste eerst)
    $fallback = (clone $baseQuery)
        ->when($explicitIds, fn ($q) => $q->whereNotIn('id', $explicitIds))
        ->get();

    $transactions = $explicit->concat($fallback)
        ->filter(function ($tx) {
            $d = json_decode($tx->linked_key, true);
            return isset($d['amount_open']) && (float) $d['amount_open'] > 0;
        });

    if ($transactions->isEmpty()) {
        return back()->withErrors('Geen open schuldtransacties gevonden voor dit account.');
    }

    DB::beginTransaction();
    try {
        foreach ($transactions as $tx) {
            if ($remainingAmount <= 0) {
                break;
            }

            $data       = json_decode($tx->linked_key, true);
            $openAmount = (float) $data['amount_open'];

            if ($openAmount <= 0) {
                continue;
            }

            $apply = min($openAmount, $remainingAmount);

            // Boek af op de transactie (methode past JSON aan en boekt grootboekregels)
            $tx->applyDebtPayment($apply, $description);
            $remainingAmount -= $apply;
        }


        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Schuldafboeking mislukt: ' . $e->getMessage());
        return back()->withErrors('Schuldafboeking mislukt.');
    }

    return back()->with('success', 'Schuldafboeking succesvol verwerkt.');
}

 
    /**
     * Update een bestaande financiële transactie.
     */
    public function update(Request $request, $id)
    {
        $transaction = FinanceTransaction::findOrFail($id);

        $validatedData = $request->validate([
            'account_id'       => 'sometimes|required|exists:finance_accounts,id',
            'debit_credit'     => 'sometimes|required|in:debit,credit',
            'amount'           => 'sometimes|required|numeric',
            'description'      => 'nullable|string',
            'linked_key'       => 'nullable|json',
            'invoice_id'       => 'nullable|exists:invoices,id',
            'order_id'         => 'nullable|exists:orders,id',
            'invoice_line_id'  => 'nullable|exists:invoice_lines,id',
            'order_item_id'    => 'nullable|exists:order_items,id',
            'transaction_date' => 'nullable|date',
        ]);

        $transaction->update($validatedData);

        activity('finance_transaction')
            ->performedOn($transaction)
            ->withProperties(['attributes' => $validatedData])
            ->log('updated');

        return response()->json($transaction);
    }

    /**
     * Verwijder een financiële transactie.
     */
    public function destroy($id)
    {
        $transaction = FinanceTransaction::findOrFail($id);

        activity('finance_transaction')
            ->performedOn($transaction)
            ->withProperties(['attributes' => $transaction->toArray()])
            ->log('deleted');

        $transaction->delete();

        return response()->json(null, 204);
    }

    public function logs($id)
{
    $transaction = FinanceTransaction::findOrFail($id);
    // Haal de activiteiten op die betrekking hebben op deze transactie
    $logs = $transaction->activities()->orderBy('created_at', 'desc')->get();

    return response()->json(['logs' => $logs]);
}


}

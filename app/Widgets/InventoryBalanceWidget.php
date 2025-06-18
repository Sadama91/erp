<?php

namespace App\Widgets;

use Illuminate\Support\Facades\DB;
use App\Models\FinanceAccount;

class InventoryBalanceWidget extends BaseWidget
{
    public function render(): string
    {
        //
        // ─── VOORRAAD & BALANS ───────────────────────────────────────────────────────
        //

        // 1) Aantal ingekochte artikelen (IN‑mutaties)
        $totalPurchasedQty = DB::table('product_stock_history')
            ->where('stock_action', 'IN')
            ->sum('quantity');

        // 2) Aantal verkochte artikelen
        $soldQty = DB::table('order_items')
            ->sum('quantity');

        // 3) Huidige voorraad
        $presentQty = DB::table('product_stock')
            ->sum('current_quantity');

        // 4) Inkoopwaarde totaal
        $purchaseValueTotal = DB::table('order_items')
            ->sum(DB::raw('quantity * purchase_price'));

        // 5) Verkoopwaarde totaal
        $salesValueTotal = DB::table('order_items')
            ->sum('calculated_sales_price');

        // 6) Operationeel saldo (verkoop − inkoop)
        $balanceTotal = $salesValueTotal - $purchaseValueTotal;

        // 7) Waarde van de aanwezige voorraad
        $avgPurchasePrice = $totalPurchasedQty
            ? $purchaseValueTotal / $totalPurchasedQty
            : 0;
        $presentValue = $presentQty * $avgPurchasePrice;

        // 8) ‘On hold’ bedrag (operationeel − voorraadwaarde)
        $onHoldValue = $balanceTotal - $presentValue;

        // 9) Winst per bestelling & % t.o.v. inkoop
        $ordersCount        = DB::table('orders')->count();
        $profitPerOrder     = $ordersCount
            ? $balanceTotal / $ordersCount
            : 0;
        $profitTotalPercent = $purchaseValueTotal
            ? ($balanceTotal / $purchaseValueTotal) * 100
            : 0;

        //
        // ─── FINANCIËLE SALDI ────────────────────────────────────────────────────────
        //

        // 10) Schuld Sanne (code = 'schuld_sanne', id = 16)
        $schuldSanne = FinanceAccount::where('account_code', 'schuld_sanne')
            ->value('balance') ?? 0;

        // 11) Schuld Sander (code = 'schuld_sander', id = 15)
        $schuldSander = FinanceAccount::where('account_code', 'schuld_sander')
            ->value('balance') ?? 0;

        // 12) Schuld totaal (kinderen van parent_id 14)
        $schuldTotaal = DB::table('finance_accounts')
            ->where('parent_id', 14)
            ->sum('balance');

        // 13) Gelden onderweg/openstaand (category = 'onderweg')
        $onderweg = DB::table('finance_accounts')
            ->where('category', 'onderweg')
            ->sum('balance');

        // 14) Theoretisch saldo = bankrekening + onderweg − schuldTotaal
        $bankrekening = FinanceAccount::where('account_code', 'Bankrekening')
            ->value('balance') ?? 0;
        $theoretischSaldo = $bankrekening + $onderweg - $schuldTotaal;

        // 15) Actueel saldo (account id = 18)
        $actueelSaldo = FinanceAccount::find(18)->balance ?? 0;

        // 16) Verschil (theoretisch − actueel)
        $verschil = $theoretischSaldo - $actueelSaldo;

        return view('widgets.inventory_balance', compact(
            // voorraad & balans
            'totalPurchasedQty',
            'soldQty',
            'presentQty',
            'purchaseValueTotal',
            'salesValueTotal',
            'balanceTotal',
            'presentValue',
            'onHoldValue',
            'ordersCount',
            'profitPerOrder',
            'profitTotalPercent',
            // financiële saldi
            'schuldSanne',
            'schuldSander',
            'schuldTotaal',
            'onderweg',
            'theoretischSaldo',
            'actueelSaldo',
            'verschil'
        ))->render();
    }
}

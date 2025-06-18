<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\Setting;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index()
    {
        // Haal de bankrekening op via de setting 'bank_account'
        $bankAccountId = (int) Setting::get('bank_account', null, 'financeaccount');
        $bankAccount = FinanceAccount::find($bankAccountId);

        // BTW-rekeningen: BTW betaald en BTW ontvangen
        $btwPaidId = (int) Setting::get('btw_paid_account', null, 'financeaccount');
        $btwReceivedId = (int) Setting::get('btw_received_account', null, 'financeaccount');
        $btwPaid = FinanceAccount::find($btwPaidId);
        $btwReceived = FinanceAccount::find($btwReceivedId);
        // Neem de saldi zoals ze zijn (BTW betaald staat meestal als negatief, dus optellen geeft het netto resultaat)
        $btwPaidBalance = $btwPaid ? $btwPaid->balance : 0;
        $btwReceivedBalance = $btwReceived ? $btwReceived->balance : 0;
        $netBtwResult = $btwReceivedBalance + $btwPaidBalance;

        // Gelden onderweg
        $onTheWayVinted = FinanceAccount::find((int) Setting::get('on_the_way_account_vinted', null, 'financeaccount'))->balance;
        $onTheWayWebshop = FinanceAccount::find((int) Setting::get('on_the_way_account_webshop', null, 'financeaccount'))->balance;
        $onTheWayOverig = FinanceAccount::find((int) Setting::get('on_the_way_account_overig', null, 'financeaccount'))->balance;

        // Expense-rekeningen (bijvoorbeeld: operating, purchase, other, shipping, advertising)
        $expenseAccountIds = [
            (int) Setting::get('operating_expense_account', null, 'financeaccount'),
            (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount'),
            (int) Setting::get('other_expense_account', null, 'financeaccount'),
            (int) Setting::get('shipping_expense_account', null, 'financeaccount'),
            (int) Setting::get('advertising_expense_account', null, 'financeaccount'),
        ];
        $expenseAccounts = FinanceAccount::whereIn('id', $expenseAccountIds)->get();
        $totalExpenses = $expenseAccounts->sum('balance');

        // Debt-rekeningen (bijvoorbeeld: debt_account_main, debt_account_sanne, debt_account_sander, debt_account_extern)
        $debtAccountIds = [
            (int) Setting::get('debt_account_main', null, 'financeaccount'),
            (int) Setting::get('debt_account_sanne', null, 'financeaccount'),
            (int) Setting::get('debt_account_sander', null, 'financeaccount'),
            (int) Setting::get('debt_account_extern', null, 'financeaccount'),
        ];
        $debtAccounts = FinanceAccount::whereIn('id', $debtAccountIds)->get();
        $totalDebts = $debtAccounts->sum(function($acc) {
            return abs($acc->balance);
        });

        $on_the_way_accountIds = [
            (int) Setting::get('on_the_way_account', null, 'financeaccount'),
            (int) Setting::get('on_the_way_account_vinted', null, 'financeaccount'),
            (int) Setting::get('on_the_way_account_webshop', null, 'financeaccount'),
            (int) Setting::get('on_the_way_account_overig', null, 'financeaccount'),
        ];
        $onTheWayAccounts = FinanceAccount::whereIn('id', $on_the_way_accountIds)->get();
        $totalOnTheWay = $onTheWayAccounts->sum(function($acc) {
            return abs($acc->balance);
        });


        // Stel het netto bedrijfssaldo vast.
        // Hier gaan we ervan uit dat het bedrijfssaldo = banksaldo - totale kosten + totale schulden.
        $companyNet = ($bankAccount->balance + $totalOnTheWay) - $totalExpenses + (-1 * $totalDebts);

        // Theoretisch bank saldo: het banksaldo (zonder aanpassing)
        $theoreticalBank = $bankAccount->balance - $totalExpenses;

        // Bouw een overzichtsarray met de gewenste blokken
        $balanceSheet = [
            'netto_btw' => [
                'title'   => 'Netto BTW Resultaat',
                'total'   => $netBtwResult,
                'details' => [
                    ['name' => 'BTW ontvangen', 'amount' => $btwReceivedBalance],
                    ['name' => 'BTW betaald', 'amount' => $btwPaidBalance],
                ],
            ],
            'bedrijfssaldo' => [
                'title'   => 'Verwacht Bedrijfssaldo',
                'total'   => $companyNet,
                'details' => [
                    ['name' => 'Bankrekening', 'amount' => $bankAccount ? $bankAccount->balance : 0],
                    ['name' => 'Gelden onderweg', 'amount' => $totalOnTheWay],
                    ['name' => 'Totale kosten', 'amount' => $totalExpenses],
                    ['name' => 'Totale schulden', 'amount' => $totalDebts],
                ],
            ],
            'theoretisch_bank' => [
                'title'   => 'Huidig theoretisch Saldo Bankrekening',
                'total'   => $theoreticalBank,
                'details' => [
                    ['name' => 'Bankrekening', 'amount' => $bankAccount ? $bankAccount->balance : 0],
                    ['name' => 'Totale kosten', 'amount' => $totalExpenses],
                ],
            ],
            'theoretisch_bank' => [
                'title'   => 'Gelden onderweg',
                'total'   => $totalOnTheWay,
                'details' => [
                    ['name' => 'Totaal onderweg Vinted', 'amount' => $onTheWayVinted],
                    ['name' => 'Totaal onderweg Webshop', 'amount' => $onTheWayWebshop],
                    ['name' => 'Totaal onderweg Overig', 'amount' => $onTheWayOverig],
                ],
            ],
            'schulden' => [
                'title'   => 'Overzicht Schulden',
                'total'   => $totalDebts,
                'details' => $debtAccounts->map(function($acc) {
                    return [
                        'name'   => $acc->account_name,
                        'amount' => abs($acc->balance),
                    ];
                })->toArray(),
            ],
        ];
        

        return view('financial.balancesheet', compact('balanceSheet'));
    }
}

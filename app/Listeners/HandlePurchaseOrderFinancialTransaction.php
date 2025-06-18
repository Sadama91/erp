<?php

namespace App\Listeners;

use App\Events\PurchaseOrderProcessed;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Log;

class HandlePurchaseOrderFinancialTransaction
{
    public function handle(PurchaseOrderProcessed $event)
    {
        $order = $event->purchaseOrder;

        // Bereken de bedragen van de orderitems
        $totalIncl = $order->purchaseOrderItems->sum(fn($item) => $item->price_incl_unit * $item->quantity);
        $totalExcl = $order->purchaseOrderItems->sum(fn($item) => $item->price_excl_unit * $item->quantity);
        $taxAmount = $totalIncl - $totalExcl;

        // Haal de grootboekrekeningen op via de configuratie voor inkoopfacturen
        $purchaseAccountCode = config('invoices.default_purchase_account_code', '1300');
        $vatAccountCode      = config('invoices.purchase_vat_account_code', '1700');
        $counterAccountCode  = config('invoices.default_counter_account_code', '1600');

        $purchaseAccount = ChartOfAccount::where('code', $purchaseAccountCode)->first();
        $vatAccount      = ChartOfAccount::where('code', $vatAccountCode)->first();
        $counterAccount  = ChartOfAccount::where('code', $counterAccountCode)->first();

        if (!$purchaseAccount || !$vatAccount || !$counterAccount) {
            throw new \Exception('Benodigde grootboekrekeningen voor financiële verwerking zijn niet geconfigureerd.');
        }

        // Maak een JournalEntry voor de financiële boeking van de inkooporder
        $journalEntry = JournalEntry::create([
            'date'        => $order->date,
            'description' => "Financiële verwerking inkooporder #{$order->id}",
            'reference'   => 'purchase_order:' . $order->id,
            'type'        => 'purchase',
        ]);

        // Boek de inkoopkosten (netto bedrag)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id'       => $purchaseAccount->id,
            'side'             => 'debit',
            'amount'           => $totalExcl,
            'description'      => 'Inkoopkosten (netto)',
        ]);

        // Boek de BTW-regel
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id'       => $vatAccount->id,
            'side'             => 'debit',
            'amount'           => $taxAmount,
            'description'      => 'BTW te vorderen',
        ]);

        // Boek de tegenboeking: Crediteuren (totaal incl. btw)
        JournalEntryLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id'       => $counterAccount->id,
            'side'             => 'credit',
            'amount'           => $totalIncl,
            'description'      => 'Schuld aan leverancier',
        ]);

        activity()
            ->performedOn($journalEntry)
            ->withProperties([
                'total_excl' => $totalExcl,
                'tax_amount' => $taxAmount,
                'total_incl' => $totalIncl,
            ])
            ->log("Financiële journal entry aangemaakt voor inkooporder #{$order->id}");

        Log::info("Financiële verwerking uitgevoerd voor inkooporder #{$order->id}");
    }
}

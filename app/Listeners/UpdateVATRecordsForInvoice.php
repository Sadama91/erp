<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\VATRecord;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class UpdateVATRecordsForInvoice
{
    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event)
    {
        $invoice = $event->invoice;

        // Bepaal de btw-categorie op basis van het factuurtype.
        // Pas dit aan op jouw logica. Bijvoorbeeld: 
        $category = match ($invoice->type) {
            'verkoop' => 'sales',
            'inkoop'  => 'purchase',
            'kosten'  => 'expense',
            default   => 'unknown',
        };

        // Groepeer de factuurregels op basis van het btw-percentage
        $groups = $invoice->lines->groupBy('tax_rate');

        foreach ($groups as $taxRate => $lines) {
            // Tel de btw-bedragen van alle regels met hetzelfde btw-percentage op.
            $totalTax = $lines->sum(function($line) {
                return $line->tax_amount;
            });

            // Bepaal de relevante btw-account (dit kan ook per factuurtype verschillen)
            $vatAccountId = null;
            if ($invoice->type === 'verkoop') {
                $vatAccountId = \App\Models\ChartOfAccount::where('code', config('invoices.sales_vat_account_code'))->value('id');
            } elseif (in_array($invoice->type, ['inkoop', 'kosten'])) {
                $vatAccountId = \App\Models\ChartOfAccount::where('code', config('invoices.purchase_vat_account_code'))->value('id');
            }
            // Indien er geen btw-account is gevonden, log een waarschuwing
            if (empty($vatAccountId)) {
                Log::warning("Geen BTW-account gevonden voor factuur {$invoice->id} met btw-percentage {$taxRate}.");
            }

            // Update of maak een VATRecord aan.
            // Hier koppelen we de VATRecord aan de factuur via polymorfe velden (recordable_type en recordable_id).
            $vatRecord = VATRecord::updateOrCreate(
                [
                    'recordable_type' => Invoice::class,
                    'recordable_id'   => $invoice->id,
                    'tax_rate'        => $taxRate,
                    'category'        => $category,
                ],
                [
                    'tax_amount' => $totalTax,
                    'account_id' => $vatAccountId,
                ]
            );
            Log::info("VATRecord bijgewerkt/aangemaakt", ['vatRecord' => $vatRecord]);
        }
    }
}

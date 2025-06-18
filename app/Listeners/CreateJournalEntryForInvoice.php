<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;

class CreateJournalEntryForInvoice
{
    public function handle(InvoiceCreated $event)
    {
        $invoice = $event->invoice;

        // Maak een journal entry voor deze factuur aan
        $entry = \App\Models\JournalEntry::create([
            'invoice_id'  => $invoice->id,
            'date'        => $invoice->date,
            'description' => 'Boeking factuur #' . $invoice->invoice_number,
        ]);

        // Loop door alle factuurregels
        foreach ($invoice->lines as $line) {
            // Haal het product op (indien niet via relatie geladen)
            $product = $line->product ?? \App\Models\Product::find($line->product_id);

            // Splits de verwerking op basis van het factuurtype:
            // - 'inkoop': goederen (boek op inkoopkosten)
            // - 'kosten': kosten (boek op expense-account)
            // - 'verkoop': omzetfactuur (boek op sales-account)
            // - 'overige': overige kosten, gebruik expense-account als standaard
            if ($invoice->type === 'inkoop') {
                $baseAccountCode = $product->purchase_account_id ?? config('invoices.default_purchase_account_code');
                $vatAccountCode  = config('invoices.purchase_vat_account_code');
            } elseif ($invoice->type === 'verkoop') {
                $baseAccountCode = $product->sales_account_id ?? config('invoices.default_sales_account_code');
                $vatAccountCode  = config('invoices.sales_vat_account_code');
            } elseif ($invoice->type === 'kosten') {
                $baseAccountCode = config('invoices.default_expense_account_code');
                $vatAccountCode  = config('invoices.expense_vat_account_code');
            } elseif ($invoice->type === 'overige') {
                $baseAccountCode = config('invoices.default_expense_account_code');
                $vatAccountCode  = config('invoices.expense_vat_account_code');
            } else {
                $baseAccountCode = config('invoices.default_account_code');
                $vatAccountCode  = config('invoices.default_vat_account_code');
            }

            // Haal de account-ID's op basis van de code
            $baseAccountId = ChartOfAccount::where('code', $baseAccountCode)->value('id');
            $vatAccountId  = ChartOfAccount::where('code', $vatAccountCode)->value('id');

            if (empty($baseAccountId)) {
                throw new \Exception("Geen geldig basisaccount gevonden voor regel: " . $line->description . ". Gebruikte baseAccountCode: " . $baseAccountCode . ". Invoice type: ". $invoice->type);
            }

            // Bereken bedragen (unit_price is inclusief BTW)
            $quantity = $line->quantity;
            $unitPriceIncl = $line->unit_price;
            $taxRate = $line->tax_rate; // bv. 0.21 voor 21%
            $unitPriceExcl = $unitPriceIncl / (1 + $taxRate);
            $baseAmount = round($quantity * $unitPriceExcl, 2);
            $vatAmount = round($quantity * ($unitPriceIncl - $unitPriceExcl), 2);

            // Bepaal debet en credit afhankelijk van het factuurtype
            if (in_array($invoice->type, ['inkoop', 'kosten'])) {
                // Voor inkoop- en kostenfacturen worden de bedragen gedebiteerd (als kosten of inkoopkosten)
                $debitBase = $baseAmount;
                $creditBase = 0;
                $debitVat  = $vatAmount;
                $creditVat = 0;
            } elseif ($invoice->type === 'verkoop') {
                // Voor verkoopfacturen worden de bedragen gecrediteerd (omzet en te betalen BTW)
                $debitBase = 0;
                $creditBase = $baseAmount;
                $debitVat  = 0;
                $creditVat = $vatAmount;
            } else {
                $debitBase = $creditBase = $debitVat = $creditVat = 0;
            }

            // Boek de basisregel
            $dataBasis = [
                'account_id'  => $baseAccountId,
                'amount'      => $baseAmount,
                'debit'       => $debitBase,
                'credit'      => $creditBase,
                'description' => $line->description . ' (basis)',
            ];
            $entry->lines()->create($dataBasis);

            // Boek de BTW-regel, indien van toepassing
            if ($vatAmount > 0) {
                if (empty($vatAccountId)) {
                    throw new \Exception("Geen geldig btw-account gevonden voor regel: " . $line->description . ". Gebruikte vatAccountCode: " . $vatAccountCode);
                }
                $dataVat = [
                    'account_id'  => $vatAccountId,
                    'amount'      => $vatAmount,
                    'debit'       => $debitVat,
                    'credit'      => $creditVat,
                    'description' => $line->description . ' (btw)',
                ];
                Log::info('Journal Entry - BTW-regel', $dataVat);
                $entry->lines()->create($dataVat);
            }
        }

        // Voeg een tegenrekeningregel toe voor het totaalbedrag
        if ($invoice->type === 'verkoop') {
            $counterDebit = $invoice->total;
            $counterCredit = 0;
        } elseif (in_array($invoice->type, ['inkoop', 'kosten'])) {
            $counterDebit = 0;
            $counterCredit = $invoice->total;
        } else {
            $counterDebit = $counterCredit = 0;
        }
        $counterAccountCode = $invoice->type === 'verkoop'
            ? config('invoices.sales_counter_account_code')
            : config('invoices.default_counter_account_code');
        $counterAccountId = ChartOfAccount::where('code', $counterAccountCode)->value('id');
        if (empty($counterAccountId)) {
            throw new \Exception("Geen geldig tegenrekening gevonden voor factuur. Gebruikte counterAccountCode: " . $counterAccountCode);
        }
        $dataCounter = [
            'account_id'  => $counterAccountId,
            'amount'      => $invoice->total,
            'debit'       => $counterDebit,
            'credit'      => $counterCredit,
            'description' => 'Tegenrekening',
        ];
        
        $entry->lines()->create($dataCounter);
        activity()
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->withProperties(['journal_entry_id' => $entry->id])
            ->log("Journal entry aangemaakt voor factuur #" . $invoice->invoice_number);
    
        // Koppel de journal entry aan de factuur
    // Koppel de journal entry aan de factuur
    $invoice->update(['journal_entry_id' => $entry->id]);

    // Indien de factuur direct als betaald is gemarkeerd, genereer dan een automatische betaling en trigger het PaymentReceived‑event.
    if ($invoice->status === 'betaald') {
        // Maak een automatische Payment record aan
        $payment = \App\Models\Payment::create([
            'date'            => $invoice->date,
            'amount'          => $invoice->total,
            'type'            => 'betaling',
            'method'          => 'automatisch', // Of een andere standaardwaarde
            'reference'       => 'Auto: factuur #' . $invoice->invoice_number,
            'customer_id'     => $invoice->customer_id,
            'supplier_id'     => $invoice->supplier_id,
            'bank_account_id' => config('payments.default_bank_account_id'), // Voeg dit toe!
        ]);

        // Koppel de betaling aan de factuur via de pivot-tabel
        $payment->invoices()->attach($invoice->id, ['amount' => $invoice->total]);

        // Trigger het PaymentReceived event zodat de PaymentJournalEntryForPayment listener de betalingsboekingen kan afhandelen.
        event(new \App\Events\PaymentReceived($invoice, $payment));
        
        // Eventueel: log de automatische betaling
        \Illuminate\Support\Facades\Log::info('Automatische betaling gegenereerd voor factuur #' . $invoice->invoice_number);
    }

    activity()
        ->performedOn($invoice)
        ->causedBy(auth()->user())
        ->withProperties(['journal_entry_id' => $entry->id])
        ->log("Journal entry aangemaakt voor factuur #" . $invoice->invoice_number);
    }
}

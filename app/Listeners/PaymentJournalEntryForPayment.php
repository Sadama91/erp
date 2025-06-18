<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Log;

class PaymentJournalEntryForPayment
{
    public function handle(PaymentReceived $event)
    {
        $invoice = $event->invoice;
        $payment = $event->payment;

        // Maak een journal entry voor de betaling
        $entry = JournalEntry::create([
            'invoice_id'  => $invoice->id,
            'date'        => $payment->date,
            'description' => 'Betaling voor factuur #' . $invoice->invoice_number . ' - ' . $payment->reference,
        ]);
        // Haal de bankrekening op op basis van de Payment bank_account_id
        
        $bankAccountId = $payment->bank_account_id ?? config('payments.default_bank_account_id');

        if (empty($bankAccountId)) {
            throw new \Exception("Geen bankrekening geselecteerd voor de betaling.");
        }

        // Boek de betaling op de bankrekening (debet voor ontvangen betaling)
        $entry->lines()->create([
            'account_id'  => $bankAccountId,
            'amount'      => $payment->amount,
            'debit'       => $payment->amount,
            'credit'      => 0,
            'description' => 'Ontvangen betaling op bankrekening',
        ]);

        // Bepaal de tegenrekening: voor verkoop gebruik je debiteuren, voor inkoop/kosten crediteuren.
        $counterAccountCode = $invoice->type === 'verkoop'
            ? config('payments.debiteuren_account_code', '1400')
            : config('payments.crediteuren_account_code', '1600');
        $counterAccountId = ChartOfAccount::where('code', $counterAccountCode)->value('id');
        if (empty($counterAccountId)) {
            throw new \Exception("Geen geldig tegenrekening gevonden voor betaling. Gebruikte code: " . $counterAccountCode);
        }

        // Boek de tegenboeking (credit op debiteuren/crediteuren)
        $entry->lines()->create([
            'account_id'  => $counterAccountId,
            'amount'      => $payment->amount,
            'debit'       => 0,
            'credit'      => $payment->amount,
            'description' => 'Betaling tegenboeking (debiteuren/crediteuren)',
        ]);

        // Log de boeking als activity (optioneel)
        activity()
            ->performedOn($invoice)
            ->causedBy(auth()->user())
            ->log("Betaling geboekt voor factuur #" . $invoice->invoice_number . " - Bedrag: €" . number_format($payment->amount, 2, ',', '.'));
    }
}

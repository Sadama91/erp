<?php

namespace App\Helpers;

use App\Models\FinanceTransaction;

class VatCalculator
{
    /**
     * Bereken de BTW-gegevens op basis van een FinanceTransaction.
     *
     * @param  \App\Models\FinanceTransaction  $transaction
     * @return array
     */
    public static function calculate(FinanceTransaction $transaction)
    {
        // Stel een standaard BTW-tarief in
        $vatRate = 21;
        
        // Pas het BTW-tarief aan afhankelijk van het type transactie
        switch ($transaction->type) {
            case 'inkoop':
                $vatRate = 9;
                break;
            case 'kosten':
                $vatRate = 21;
                break;
            case 'verkoop':
                $vatRate = 21;
                break;
            // Voeg extra cases toe als dat nodig is
            default:
                $vatRate = 21;
                break;
        }

        // Basisbedragen
        $amountExcl = $transaction->amount;
        $vatAmount  = round($amountExcl * ($vatRate / 100), 2);
        $amountIncl = $amountExcl + $vatAmount;

        return [
            'order_id'             => $transaction->order_id ?? null,
            'invoice_id'           => $transaction->invoice_id ?? null,
            'purchase_amount_id'   => $transaction->purchase_amount_id ?? null,
            'amount_excl'          => $amountExcl,
            'vat_amount'           => $vatAmount,
            'amount_incl'          => $amountIncl,
            'vat_rate'             => $vatRate,
            'vat_transaction_type' => $transaction->type, // Hiermee kun je later onderscheid maken
            'financial_details'    => 'Automatisch berekend via FinanceTransaction ID: ' . $transaction->id,
        ];
    }
}

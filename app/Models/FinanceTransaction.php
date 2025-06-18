<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Vat;
use App\Models\InvoiceLines;
use App\Models\OrderItem;
use App\Models\Parameter;
use App\Models\Setting;
use App\Models\FinanceAccount;

class FinanceTransaction extends Model
{
    use LogsActivity;

    protected $table = 'finance_transactions';

    // Zorg dat je een kolom 'amount_old' in de finance_transactions-tabel hebt.
    // In dit model gebruiken we nu 'debit_credit' in plaats van 'debit_credit'.
    protected $fillable = [
        'account_id',
        'debit_credit',         // Verwacht "bij" of "af"
        'amount',
        'amount_old',     // Oude waarde, die we vóór een update opslaan
        'description',
        'linked_key',
        'invoice_id',
        'order_id',
        'purchase_order_id',
        'transaction_date',
        'vat_id',
        'is_booked',      // 0 = niet geboekt, 1 = geboekt
    ];

    protected static $logAttributes = ['*'];
    protected static $logName = 'finance_transaction';
    protected static $logOnlyDirty = true;

    /**
     * Boot method: verwerk automatische boekingen en zorg voor opslag van de oude transactie-waarde.
     */
    protected static function boot()
    {
        parent::boot();

        // Sla vóór een update de huidige 'amount' op in 'amount_old'.
       /* static::updating(function ($transaction) {
            $transaction->amount_old = $transaction->getOriginal('amount') ?? 0;
        });*/

        static::created(function ($transaction) {
            // Als er een gekoppelde account is, pas dan direct het volledige effect toe.
            if ($transaction->account_id) {
                $account = \App\Models\FinanceAccount::find($transaction->account_id);
                if ($account) {
                    // Als balance_old nog niet is gezet, gaan we ervan uit dat dit de eerste transactie is (of dat we 0 als uitgangspunt gebruiken).
                    if (is_null($account->balance_old)) {
                        $account->balance_old = 0;
                    }
                    // Bereken het effect: voor "bij" is dit +amount, voor "af" is dit -amount.
                    $effect = $transaction->amount;
                    // Update het account-saldo: nieuwe balans = oude basis (balance_old) + effect.
                    $newBalance = $account->balance_old + $effect;
                    $account->balance = $newBalance;
                    // Stel balance_old bij zodat dit de basis vormt voor volgende transacties.
                    $account->balance_old = $newBalance;
                    $account->save();
                    activity()
                        ->performedOn($account)
                        ->withProperties(['new_balance' => $account->balance, 'effect' => $effect])
                        ->log("Account balance updated via creation of transaction {$transaction->id}");
                }
            }
            // Nu, na het toepassen van het effect, slaan we de transactie-waarde op als oude waarde.
            $transaction->amount_old = $transaction->amount;
            // Verwerk overige boekingen indien nodig.
            if (($transaction->invoice_id || $transaction->order_id) && !$transaction->is_booked) {
                $transaction->processBooking();
            }
            // Sla de transactie opnieuw op zonder extra events.
            $transaction->saveQuietly();
            activity()
                ->performedOn($transaction)
                ->withProperties(['event' => 'created'])
                ->log("Created transaction ID {$transaction->id}");
        });

        static::updated(function ($transaction) {
            // Als relevante velden zijn gewijzigd, zorg dan dat de boeking wordt teruggedraaid en opnieuw verwerkt.
            if (
                $transaction->wasChanged('invoice_id') ||
                $transaction->wasChanged('order_id') ||
                $transaction->wasChanged('amount') ||
                $transaction->wasChanged('debit_credit')
            ) 
            $account = FinanceAccount::find($transaction->account_id);
             
             
            // Pas de accountbalans aan op basis van het verschil tussen het oude en nieuwe effect.
            if ($transaction->account_id) {
                $account = FinanceAccount::find($transaction->account_id);
                    if ($account) {
                    // Zorg dat balance_old gezet is (0 bij eerste transactie).
                    if (is_null($account->balance_old)) {
                        $account->balance_old = 0;
                    }
                    // Bereken het oude effect met de originele waarde van 'debit_credit' en de oude transactiebedrag.
                    $oldEffect = $transaction->amount_old;
                    $newEffect = $transaction->amount;
   
                    // Het verschil dat we op het account moeten toepassen.
                    $difference = $newEffect - $oldEffect;
        
                    // Trek eerst het oude effect af...
                    $account->balance -= $oldEffect;
                    // ... en tel daarna het nieuwe effect erbij op.
                    $account->balance += $newEffect;
                    
                    // Werk de oude balans bij zodat toekomstige updates op de juiste basis werken.
                    $account->balance_old = $account->balance;
                    $account->save();
                    
                    activity()
                    ->performedOn($account)
                    ->withProperties([
                        'new_balance' => $account->balance, 
                        'old_effect'  => $oldEffect, 
                        'new_effect'  => $newEffect,
                        'difference'  => abs($newEffect-$oldEffect),
                    ])
                    ->log("Account balance updated via update of transaction {$transaction->id}");
            }
            }
            
            // Werk de oude transactiebedrag bij naar de nieuwe waarde.
            $transaction->amount_old = $transaction->amount;
            $transaction->saveQuietly();
            
            activity()
                ->performedOn($transaction)
                ->withProperties(['event' => 'updated', 'changes' => $transaction->getChanges()])
                ->log("Updated transaction ID {$transaction->id}");
        });
        
        static::deleting(function ($transaction) {
            // Eerst: als er een VAT-record is gekoppeld, haal dit via onze helper removeVatRecord() op.
            if ($transaction->vat_id) {
                $transaction->removeVatRecord();
            }
            // Corrigeer daarna het saldo van het gekoppelde FinanceAccount.
            if ($transaction->account_id) {
                $account = FinanceAccount::find($transaction->account_id);
                if ($account) {
                    // Zorg voor een fallback: als debit_credit null is, gebruiken we "debit" als standaard.
                    $appliedEffect = $transaction->amount_old;
                    $account->balance -= $appliedEffect;
                    $account->save();
                    activity()
                        ->performedOn($account)
                        ->withProperties([
                            'deleted_transaction_id' => $transaction->id,
                            'new_balance' => $account->balance,
                        ])
                        ->log("Deleted transaction {$transaction->id}: Account balance updated for account ID {$account->id}");
                }
            }
        });
        
    }
    /**
     * Boekt schulden weg
     */
    public function applyDebtPayment($paymentAmount)
{
    $linked = json_decode($this->linked_key, true);
    if (!$linked || !isset($linked['total'])) {
        throw new \Exception("Geen schuldinformatie beschikbaar in deze transactie.");
    }

    $total = floatval($linked['total']);
    $amount_booked = isset($linked['amount_booked']) ? floatval($linked['amount_booked']) : 0;
    $amount_open = isset($linked['amount_open']) ? floatval($linked['amount_open']) : ($total - $amount_booked);

    if ($paymentAmount > $amount_open) {
        throw new \Exception("Het ingevoerde bedrag ($paymentAmount) is hoger dan het openstaande bedrag ($amount_open).");
    }

    // Pas de bedragen aan
    $amount_booked += $paymentAmount;
    $amount_open = $total - $amount_booked;

    if ($amount_open <= 0) {
        $this->linked_key = null;
    } else {
        $this->linked_key = json_encode([
            'total'             => $total,
            'amount_booked'     => $amount_booked,
            'amount_open'       => $amount_open,
            'original_account'  => $linked['original_account'] ?? null,
            'debt_account'      => $linked['debt_account'] ?? null,
            'booking_account'   => $linked['booking_account'] ?? null,
        ]);
    }
    $this->saveQuietly();

    // Werk de schuldrekening bij
    $debtAccountId = $linked['debt_account'] ?? null;
    if ($debtAccountId) {
        $debtAccount = FinanceAccount::find($debtAccountId);
        if ($debtAccount) {
            // Omdat schuldrekeningen intern negatief worden opgeslagen,
            // verhoogt een betaling (bij-boeking) het saldo (minder schuld).
            $debtAccount->balance -= $paymentAmount;
            $debtAccount->save();
            activity()
                ->performedOn($debtAccount)
                ->withProperties(['new_balance' => $debtAccount->balance, 'paid_amount' => $paymentAmount])
                ->log("Schuldafboeking: Schuldrekening bijgewerkt.");
        }
    }

    // Werk de oorspronkelijke (doel)rekening bij
    $targetAccountId = $linked['original_account'] ?? null;
    if ($targetAccountId) {
        $targetAccount = FinanceAccount::find($targetAccountId);
        if ($targetAccount) {
            // Hier wordt aangenomen dat op de doelrekening een "bij"-boeking het saldo verhoogt.
            $targetAccount->balance += $paymentAmount;
            $targetAccount->save();
            activity()
                ->performedOn($targetAccount)
                ->withProperties(['new_balance' => $targetAccount->balance, 'received_amount' => $paymentAmount])
                ->log("Schuldafboeking: Doelrekening bijgewerkt.");
        }
    }
}

    /**
     * ProcessBooking: verwerkt de boekingen voor deze financiële transactie.
     */
    public function processBooking()
    {
        DB::transaction(function () {
            if ($this->order_id) {
                $this->bookOrder();
            }
            if ($this->invoice_id) {
                $this->bookInvoice();
            }
            if ($this->invoice_id || $this->order_id) {
                $this->bookVATRecord();
            }
            if ($this->amount < 0) {
                $this->creditAccount();
            }
            $this->updateQuietly(['is_booked' => true]);
            activity()
                ->performedOn($this)
                ->withProperties(['step' => 'processBooking'])
                ->log("Transaction ID {$this->id} marked as booked");
        });
    }

    /**
     * ReverseBooking: maakt een reversale boeking aan om het oude effect te neutraliseren.
     */
    public function reverseBooking()
    {
        $reverseBijAf = strtolower($this->getOriginal('debit_credit')) === 'bij' ? 'af' : 'bij';
        activity()
            ->performedOn($this)
            ->withProperties(['reverse_of' => $this->id])
            ->log("Reversal booking for transaction ID {$this->id}");
        self::create([
            'account_id'      => $this->account_id,
            'debit_credit'          => $reverseBijAf,
            'amount'          => $this->getOriginal('amount') ?? 0,
            'description'     => 'Reversal booking for transaction ' . $this->id,
            'linked_key'      => json_encode(['reverse_of' => $this->id]),
            'transaction_date'=> now(),
            'is_booked'       => true,
        ]);
    }

    /**
     * CalculateVATData: berekent de benodigde VAT-gegevens op basis van de transactie.
     *
     * Retourneert een array met:
     *  - finance_transaction_id, invoice_id, order_id, purchase_order_id
     *  - amount_excl: hoofdbedrag exclusief VAT
     *  - vat_amount: berekend BTW-bedrag
     *  - amount_incl: origineel bedrag
     *  - vat_rate, vat_transaction_type, financial_details
     *
     * @return array|null
     */
    private function calculateVATData()
    {
        $vatAggregate = null;
        $financialDetails = '';
        $vatTransactionType = '';

        if ($this->invoice_id) {
            $vatAggregate = InvoiceLines::where('invoice_id', $this->invoice_id)
                ->selectRaw('SUM(total_vat) as total_vat, MAX(vat_rate) as vat_rate')
                ->first();
            $financialDetails = 'BTW overgenomen vanuit factuurregels (invoice_id: ' . $this->invoice_id . ')';
            $vatTransactionType = 'Herkomst: Invoice';
        } elseif ($this->order_id) {
            $vatAggregate = OrderItem::where('order_id', $this->order_id)
                ->selectRaw('SUM(vat_amount) as total_vat, MAX(vat_rate_id) as vat_rate_id')
                ->first();
            $parameter = Parameter::find($vatAggregate->vat_rate_id);
            $vatRate = $parameter ? $parameter->value : null;
            $vatAggregate->vat_rate = $vatRate;
            $financialDetails = 'BTW overgenomen vanuit orderregels (order_id: ' . $this->order_id . ')';
            $vatTransactionType = 'Herkomst: Orders';
        }

        if ($vatAggregate && $vatAggregate->total_vat > 0) {
            $financialDetailsJson = json_encode($financialDetails);
            return [
                'finance_transaction_id' => $this->id,
                'invoice_id'             => $this->invoice_id ?? null,
                'order_id'               => $this->order_id ?? null,
                'purchase_order_id'      => $this->purchase_order_id ?? null,
                'amount_excl'            => $this->amount - $vatAggregate->total_vat,
                'vat_amount'             => $vatAggregate->total_vat,
                'amount_incl'            => $this->amount,
                'vat_rate'               => $vatAggregate->vat_rate,
                'vat_transaction_type'   => $vatTransactionType,
                'financial_details'      => $financialDetailsJson,
            ];
        }
        activity()
            ->performedOn($this)
            ->log("Geen VAT data berekend voor transaction ID {$this->id}");
        return null;
    }

    /**
     * BookVATRecord: maakt of werkt het VAT-record in de vats-tabel bij en koppelt dit aan de FinanceTransaction.
     * Tevens wordt een aparte FinanceTransaction gemaakt voor de VAT-beweging op de VAT-rekening.
     */
    private function bookVATRecord()
{
    $vatData = $this->calculateVATData();
    activity()
        ->performedOn($this)
        ->withProperties(['step' => 'calculateVATData', 'vatData' => $vatData])
        ->log("Calculated VAT data for transaction ID {$this->id}");
    
    // Als er geen VAT-data berekend kon worden, stoppen we.
    if (!$vatData) {
        activity()
            ->performedOn($this)
            ->log("Geen VAT data berekend voor transaction ID {$this->id}. VAT boeking wordt overgeslagen.");
        return;
    }
    
    $korActive = Setting::get('kor_active', 'false', 'vat');
    if (strtolower($korActive) === 'true') {
        activity()
            ->performedOn($this)
            ->withProperties(['kor_active' => $korActive])
            ->log("KOR actief, VAT boeking wordt overgeslagen voor transaction ID {$this->id}");
        return;
    }

    if ($this->invoice_id) {
        $vatAccountId = (int) Setting::get('btw_paid_account', null, 'financeaccount');
        $vatType = 'invoice';
    } elseif ($this->order_id) {
        $vatAccountId = (int) Setting::get('btw_received_account', null, 'financeaccount');
        $vatType = 'order';
    } else {
        activity()
            ->performedOn($this)
            ->log("Geen invoice of order voor VAT boeking voor transaction ID {$this->id}");
        return;
    }
    
    // Nu kunnen we veilig de VAT data gebruiken
    activity()
        ->performedOn($this)
        ->withProperties(['vat_account_id' => $vatAccountId, 'vat_amount' => $vatData['vat_amount']])
        ->log("Verwerken VAT-record voor transaction ID {$this->id}");
    
    if ($this->vat_id) {
        $vatRecord = Vat::find($this->vat_id);
        if ($vatRecord) {
            $vatRecord->update($vatData);
            activity()
                ->performedOn($vatRecord)
                ->log("VAT record bijgewerkt voor transaction ID {$this->id}");
        } else {
            $newVat = Vat::create($vatData);
            $this->updateQuietly(['vat_id' => $newVat->id]);
            activity()
                ->performedOn($newVat)
                ->log("VAT record (fallback) aangemaakt voor transaction ID {$this->id}");
        }
    } else {
        $newVat = Vat::create($vatData);
        $this->updateQuietly(['vat_id' => $newVat->id]);
        activity()
            ->performedOn($newVat)
            ->log("VAT record aangemaakt voor transaction ID {$this->id}");
    }
    
    // Boek de VAT-beweging op de VAT-rekening.
    $this->updateVATAccountBalance($vatAccountId, $vatData['vat_amount'], $vatType);
}


/**
 * removeVatRecord: Verwijdert het gekoppelde VAT-record (indien aanwezig) en maakt de tegenboeking op de VAT-rekening.
 * Als er geen geldig VAT-record gevonden wordt, stopt deze functie zonder fout.
 */
private function removeVatRecord()
{
    // Zoek het VAT-record op via de vat_id.
    $vatRecord = Vat::find($this->vat_id);
    if (!$vatRecord) {
        // Als er geen VAT-record is gevonden, log dit en stop.
        activity()
            ->performedOn($this)
            ->log("Geen VAT record gevonden voor transaction ID {$this->id} bij verwijdering.");
        return;
    }

    // Bepaal op basis van of het een invoice of order betreft de juiste VAT-rekening.
    if ($this->invoice_id) {
        $vatAccountId = (int) Setting::get('btw_paid_account', null, 'financeaccount');
        $vatType = 'invoice';
    } elseif ($this->order_id) {
        $vatAccountId = (int) Setting::get('btw_received_account', null, 'financeaccount');
        $vatType = 'order';
    } else {
        activity()
            ->performedOn($this)
            ->log("Geen invoice of order voor VAT verwijdering voor transaction ID {$this->id}");
        return;
    }

    // Zorg ervoor dat de VAT-record wel een vat_amount bevat.
    if (!isset($vatRecord->vat_amount)) {
        activity()
            ->performedOn($this)
            ->log("VAT record voor transaction ID {$this->id} bevat geen vat_amount, overslaan verwijdering.");
        return;
    }

    // Haal de VAT-rekening op en maak de tegenboeking.
    $vatAccount = \App\Models\FinanceAccount::find($vatAccountId);
    if ($vatAccount) {
        if ($vatType === 'invoice') {
            // Bij een inkoopfactuur werd het VAT-bedrag eerder afgetrokken, dus voeg dit nu terug toe.
            $vatAccount->balance += $vatRecord->vat_amount;
        } elseif ($vatType === 'order') {
            // Bij een order werd het VAT-bedrag eerder opgeteld, dus trek dit nu af.
            $vatAccount->balance -= $vatRecord->vat_amount;
        }
        $vatAccount->save();
        activity()
            ->performedOn($vatAccount)
            ->withProperties(['new_balance' => $vatAccount->balance])
            ->log("VAT account balance reversed for transaction ID {$this->id}");
    }

    // Verwijder het VAT-record.
    $vatRecord->delete();
    activity()
        ->performedOn($vatRecord)
        ->withProperties(['deleted_transaction_id' => $this->id])
        ->log("VAT record deleted for transaction ID {$this->id}");
}


    /**
     * UpdateVATAccountBalance: past het saldo van de VAT-rekening aan.
     *
     * @param int    $vatAccountId Het ID van de VAT-rekening.
     * @param float  $vatAmount    Het BTW-bedrag.
     * @param string $vatType      'invoice' of 'order'.
     */
    private function updateVATAccountBalance($vatAccountId, $vatAmount, $vatType)
    {
        $vatAccount = \App\Models\FinanceAccount::find($vatAccountId);
        if ($vatAccount) {
            if ($vatType === 'invoice') {
                // Voor inkoopfacturen (BTW betaald) verlagen we de VAT-rekening.
                $vatAccount->balance -= $vatAmount;
            } elseif ($vatType === 'order') {
                // Voor verkooporders (BTW ontvangen) verhogen we de VAT-rekening.
                $vatAccount->balance += $vatAmount;
            }
            $vatAccount->save();
            activity()
                ->performedOn($vatAccount)
                ->withProperties(['new_balance' => $vatAccount->balance])
                ->log("VAT account balance updated voor account ID {$vatAccount->id} via transaction {$this->id}");
        }
    }

    /**
     * BookOrder: boekt een verkooporder op basis van de order_source.
     */
    private function bookOrder()
    {
        $order = \App\Models\Order::find($this->order_id);
        if (!$order) {
            activity()
                ->performedOn($this)
                ->log("Order niet gevonden voor transaction ID {$this->id}");
            return;
        }
        $orderSource = $order->order_source;
        switch (strtolower($orderSource)) {
            case 'vinted':
                $accountId = (int) Setting::get('on_the_way_account_vinted', null, 'financeaccount');
                break;
            case 'webshop':
                $accountId = (int) Setting::get('on_the_way_account_webshop', null, 'financeaccount');
                break;
            default:
                $accountId = (int) Setting::get('on_the_way_account_overig', null, 'financeaccount');
                break;
        }
        activity()
            ->performedOn($this)
            ->withProperties(['account_id' => $accountId, 'order_source' => $orderSource])
            ->log("Verkooporder geboekt voor transaction ID {$this->id}");
        $this->updateQuietly(['account_id' => $accountId]);
    }

    /**
     * BookInvoice: boekt een inkoopfactuur op de kostenrekening.
     */
    private function bookInvoice()
    {
        $accountId = (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount');
        activity()
            ->performedOn($this)
            ->withProperties(['account_id' => $accountId])
            ->log("Inkoopfactuur geboekt voor transaction ID {$this->id}");
        $this->updateQuietly(['account_id' => $accountId]);
    }

    /**
     * CreditAccount: verwerkt een credit/terugbetaling.
     */
    private function creditAccount()
    {
        if ($this->invoice_id) {
            $accountId = (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount');
        } elseif ($this->order_id) {
            $order = \App\Models\Order::find($this->order_id);
            $orderSource = $order->order_source ?? 'overig';
            switch (strtolower($orderSource)) {
                case 'vinted':
                    $accountId = (int) Setting::get('on_the_way_account_vinted', null, 'financeaccount');
                    break;
                case 'webshop':
                    $accountId = (int) Setting::get('on_the_way_account_webshop', null, 'financeaccount');
                    break;
                default:
                    $accountId = (int) Setting::get('on_the_way_account_overig', null, 'financeaccount');
                    break;
            }
        }
        activity()
            ->performedOn($this)
            ->withProperties(['account_id' => $accountId])
            ->log("Credit/terugbetaling geboekt voor transaction ID {$this->id}");
        \App\Models\FinanceTransaction::create([
            'account_id'      => $accountId,
            'debit_credit'          => 'af',
            'amount'          => $this->amount,
            'description'     => 'Credit/terugbetaling voor transaction ' . $this->id,
            'linked_key'      => json_encode(['related_transaction' => $this->id]),
            'transaction_date'=> now(),
            'is_booked'       => true,
        ]);
    }

    /**
     * PayTax: verwerkt de BTW-betaling aan de belastingdienst.
     * (Placeholder: pas de berekeningen aan volgens jouw regels.)
     */
    private function payTax()
    {
        $btwPaidAccount = (int) Setting::get('btw_paid_account', null, 'financeaccount');
        $btwReceivedAccount = (int) Setting::get('btw_received_account', null, 'financeaccount');

        $paidVAT = 0;
        $receivedVAT = 0;
        $difference = $receivedVAT - $paidVAT;

        if ($difference != 0) {
            activity()
                ->performedOn($this)
                ->withProperties(['difference' => $difference])
                ->log("BTW betaling correctie voor transaction ID {$this->id}");
            \App\Models\FinanceTransaction::create([
                'account_id'      => (int) Setting::get('operating_expense_account', null, 'financeaccount'),
                'debit_credit'          => ($difference > 0) ? 'af' : 'bij',
                'amount'          => abs($difference),
                'description'     => 'BTW betaling correctie',
                'linked_key'      => json_encode(['tax_difference' => $difference]),
                'transaction_date'=> now(),
                'is_booked'       => true,
            ]);
        }
    }

    // Relaties

    public function financeAccount()
    {
        return $this->belongsTo(\App\Models\FinanceAccount::class, 'account_id');
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id');
    }

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    public function invoiceLine()
    {
        return $this->belongsTo(\App\Models\InvoiceLines::class, 'invoice_line_id');
    }

    public function vat()
    {
        return $this->hasOne(\App\Models\Vat::class, 'finance_transaction_id', 'id');
    }

    public function orderItem()
    {
        return $this->belongsTo(\App\Models\OrderItem::class, 'order_item_id');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Finance transaction has been {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->dontSubmitEmptyLogs();
    }
}

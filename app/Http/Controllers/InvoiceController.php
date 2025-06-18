<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceLines;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Setting;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {    
        // Update verlopen facturen vooraleer we de lijst ophalen
        $this->updateExpiredInvoices();
        $query = Invoice::query();
        // Filters toepassen
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('amount')) {
            $query->where('amount_incl_vat_total', $request->amount);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        $invoices = $query->orderByRaw("CASE WHEN status = 'vervallen' THEN 0 ELSE 1 END ASC")
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);
    
        return view('invoices.index', compact('invoices'));
    }
    
    public function show($id)
    {
        $invoice = Invoice::with('invoiceLines')->findOrFail($id);
        return view('invoices.show', compact('invoice'));
    }
    
    public function create(Request $request)
    {
        if ($request->has('purchase_order_id')) {
            $existingInvoice = Invoice::where('from_purchase_order', $request->purchase_order_id)->first();
            if ($existingInvoice) {
                return redirect()->back()->with('error', 'Voor deze inkooporder bestaat al een factuur.');
            }
        }
        //dd($request->from_purchase_order);
        $vatRates = Parameter::where('key', 'vat_rate')->get();
        $invoiceTypes = Parameter::where('key', 'invoice_type')->get();
        $invoiceStatuses = Parameter::where('key', 'invoice_status')->get();
        $products = Product::all();
        $existingPOIds = Invoice::whereNotNull('purchase_order_id')->pluck('purchase_order_id')->toArray();
        $purchaseOrders = PurchaseOrder::with('supplier', 'purchaseOrderItems')
            ->whereNotIn('id', $existingPOIds)
            ->get();
        $suppliers = Supplier::all();

        $createFrom = null;
        $prefillSupplier = null;
        if ($request->has('from_purchase_order')) {
            $createFrom = PurchaseOrder::find($request->from_purchase_order);
            $prefillSupplier = $createFrom ? $createFrom->supplier_id : null;
        }
    
        return view('invoices.create', compact(
            'products',
            'vatRates',
            'purchaseOrders',
            'invoiceTypes',
            'invoiceStatuses',
            'suppliers',
            'prefillSupplier',
            'createFrom'
        ));
    }
    
    public function edit($id)
    {
        $invoice = Invoice::with('invoiceLines')->findOrFail($id);
        
        $vatRates = Parameter::where('key', 'vat_rate')->get();
        $invoiceTypes = Parameter::where('key', 'invoice_type')->get();
        $invoiceStatuses = Parameter::where('key', 'invoice_status')->get();
        $products = Product::all();
        $existingPOIds = Invoice::whereNotNull('purchase_order_id')->pluck('purchase_order_id')->toArray();
        $purchaseOrders = PurchaseOrder::with('supplier', 'purchaseOrderItems')
            ->whereNotIn('id', $existingPOIds)
            ->get();
        $suppliers = Supplier::all();
        $prefillSupplier = null;
        if ($invoice->supplier_id > 0) {
            $po = PurchaseOrder::find($invoice->supplier_id);
            $prefillSupplier = $po ? $po->supplier_id : null;
        }
    
        return view('invoices.edit', compact(
            'products',
            'vatRates',
            'purchaseOrders',
            'invoiceTypes',
            'invoiceStatuses',
            'suppliers',
            'prefillSupplier',
            'invoice'
        ));
    }
   
    public function store(Request $request)
{
    $validated = $this->validateInvoice($request);
    $invoiceLinesData = $request->input('invoice_lines', []);

    // Haal de booking_account-invoer op; verwacht bijvoorbeeld 'sanne', 'sander', 'extern' of 'org'
    $bookingAccount = $request->input('booking_account');

    DB::beginTransaction();

    try {
        $validated['invoice_number'] = $this->generateInvoiceNumber();

        if (empty($validated['name']) && !empty($validated['supplier_id'])) {
            $supplier = Supplier::find($validated['supplier_id']);
            $validated['name'] = $supplier ? $supplier->name : null;
        }

        $invoice = Invoice::create($validated);

        // Bereken vervaldatum op basis van de boekdatum en payment_days
        $paymentDaysSetting = Setting::where('category', 'order')
            ->where('key', 'payment_days')
            ->first();
        $paymentDays = $paymentDaysSetting ? intval($paymentDaysSetting->value) : 30;
        $invoiceDate = $validated['date'];
        $invoice_due_date = date('Y-m-d', strtotime("$invoiceDate + $paymentDays days"));
        $invoice->update(['invoice_due_date' => $invoice_due_date]);

        // Maak alle factuurregels aan
        foreach ($invoiceLinesData as $lineData) {
            $lineValidated = $this->validateInvoiceLine($lineData);
            $lineValidated['invoice_id'] = $invoice->id;
            InvoiceLines::create($lineValidated);
        }

        activity()
            ->performedOn($invoice)
            ->withProperties($invoice->toArray())
            ->log('Factuur aangemaakt');

        // Bereken het totaalbedrag; gebruik bij voorkeur de som van de factuurregels
        $totalAmount = $invoice->amount_incl_vat_total;
        if (empty($totalAmount)) {
            $totalAmount = $invoice->invoiceLines()->sum('amount_incl_vat_total');
        }
        if (is_null($totalAmount)) {
            throw new \Exception("Het totaalbedrag van de factuur kon niet worden berekend.");
        }

        // Bepaal de standaard rekening (het 'final target account') en debit/credit op basis van het factuurtype.
        $type = strtolower($invoice->type);
        $defaultDebitCredit = 'bij'; // alle boekingen als positief

        $bookingDescription = '';
        switch ($type) {
            case 'inkoop':
                $defaultAccountId = (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount');
                $bookingDescription = 'Inkoop';
                break;
            case 'verkoop':
                $defaultAccountId = (int) Setting::get('bank_account', null, 'financeaccount');
                $defaultDebitCredit = 'bij';
                $bookingDescription = 'Verkoop';
                break;
            case 'kosten':
                $defaultAccountId = (int) Setting::get('operating_expense_account', null, 'financeaccount');
                $bookingDescription = 'Kosten';
                break;
            case 'shipping':
                $defaultAccountId = (int) Setting::get('shipping_expense_account', null, 'financeaccount');
                $bookingDescription = 'Verzendkosten';
                break;
            case 'marketing':
                $defaultAccountId = (int) Setting::get('advertising_expense_account', null, 'financeaccount');
                $bookingDescription = 'Advertentiekosten';
                break;
            case 'overig':
            default:
                $defaultAccountId = (int) Setting::get('other_expense_account', null, 'financeaccount');
                $bookingDescription = 'Overige uitgaven';
                break;
        }

        // Stel de transactie-account-ID in. Dit is de rekening waarop de transactie initieel geboekt wordt.
        // Als er een specifiek schuld-account is ingegeven, wordt die gebruikt; anders blijft dit gelijk aan de standaard.
        $transactionAccountId = $defaultAccountId; // standaard
        if ($bookingAccount) {
            switch (strtolower($bookingAccount)) {
                case 'sanne':
                    $transactionAccountId = (int) Setting::get('debt_account_sanne', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via Schuld Sanne)';
                    break;
                case 'sander':
                    $transactionAccountId = (int) Setting::get('debt_account_sander', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via Schuld Sander)';
                    break;
                case 'extern':
                    $transactionAccountId = (int) Setting::get('debt_account_extern', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via externe schuld)';
                    break;
                case 'org':
                    // Bij 'org' gebruiken we het standaard account.
                    $transactionAccountId = $defaultAccountId;
                    $bookingDescription .= ' (organisatie)';
                    break;
            }
        }

        // Stel de linked_key JSON samen met schuldverdeling, zodat je later kunt zien:
        // - total: totaalbedrag van de factuur
        // - amount_booked: initieel 0 (nog niet afgebroken)
        // - amount_open: gelijk aan totaal
        // - original_account: het standaard (final target) account
        // - debt_account: als het schuld-account verschilt van het standaard account, anders null
        // - booking_account: de opgegeven booking_account
        $linkedKeyData = [
            'total'            => (float)$totalAmount,
            'amount_booked'    => 0.00,
            'amount_open'      => (float)$totalAmount,
            'original_account' => $defaultAccountId,
            'debt_account'     => ($transactionAccountId != $defaultAccountId) ? $transactionAccountId : null,
            'booking_account'  => $bookingAccount,
        ];

        // Maak de FinanceTransaction aan voor de factuur
        $transaction = FinanceTransaction::create([
            'account_id'       => $transactionAccountId,
            'debit_credit'     => $defaultDebitCredit,
            'amount'           => $totalAmount,
            'description'      => 'Factuur aangemaakt: ' . $invoice->invoice_number . ' ' . $bookingDescription,
            'linked_key'       => json_encode($linkedKeyData),
            'invoice_id'       => $invoice->id,
            'order_id'         => $validated['order_id'] ?? null,
            'purchase_order_id'=> $validated['purchase_order_id'] ?? null,
            'transaction_date' => \Carbon\Carbon::now(),
        ]);

        activity()
            ->performedOn($transaction)
            ->causedBy(auth()->user())
            ->withProperties([
                'invoice_id'     => $invoice->id,
                'amount'         => $totalAmount,
                'booking_account'=> $bookingAccount,
            ])
            ->log('Financiële transactie aangemaakt bij factuuraanmaak');

        DB::commit();

        return redirect()->route('invoices.index')
            ->with('success', 'Factuur succesvol aangemaakt.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()
            ->withInput()
            ->withErrors('Er is een fout opgetreden bij het opslaan van de factuur: ' . $e->getMessage());
    }
}

public function update(Request $request, $id)
{
    $invoice = Invoice::findOrFail($id);
    $validated = $this->validateInvoice($request);
    $invoiceLinesData = $request->input('invoice_lines', null);

    // Haal de booking_account-invoer op (optioneel, bijvoorbeeld 'sanne', 'sander', 'extern' of 'org')
    $bookingAccount = $request->input('booking_account'); 

    DB::beginTransaction();

    try {
        // Vul naam in op basis van leverancier indien nodig
        if (empty($validated['name']) && !empty($validated['supplier_id'])) {
            $supplier = Supplier::find($validated['supplier_id']);
            $validated['name'] = $supplier ? $supplier->name : null;
        }

        // Update de factuurgegevens
        $invoice->update($validated);

        // Bereken vervaldatum op basis van de boekdatum en de setting 'payment_days'
        $paymentDaysSetting = Setting::where('category', 'order')
            ->where('key', 'payment_days')
            ->first();
        $paymentDays = $paymentDaysSetting ? intval($paymentDaysSetting->value) : 30;
        $invoiceDate = $validated['date'];
        $invoice_due_date = date('Y-m-d', strtotime("$invoiceDate + $paymentDays days"));
        $invoice->update(['invoice_due_date' => $invoice_due_date]);

        // Werk de factuurregels bij: verwijder oude regels en maak nieuwe aan
        if ($invoiceLinesData !== null) {
            $invoice->invoiceLines()->delete();
            foreach ($invoiceLinesData as $lineData) {
                $lineValidated = $this->validateInvoiceLine($lineData);
                $lineValidated['invoice_id'] = $invoice->id;
                InvoiceLines::create($lineValidated);
            }
        }

        activity()
            ->performedOn($invoice)
            ->withProperties($invoice->toArray())
            ->log('Factuur bijgewerkt');

        // Bereken het nieuwe totaalbedrag op basis van de factuurregels
        $total = InvoiceLines::where('invoice_id', $invoice->id)
                    ->selectRaw('SUM(amount_incl_vat_total) as total')
                    ->first();
        $newTotal = $total->total;
        Log::info('Nieuw totaalbedrag', ['new total' => $newTotal]);

        // Stel de standaard waarden vast op basis van het factuurtype
        $type = strtolower($invoice->type);
        $defaultDebitCredit = 'bij'; // alle boekingen als positief
        $bookingDescription = '';
        switch ($type) {
            case 'inkoop':
                $defaultAccountId = (int) Setting::get('purchase_invoice_expense_account', null, 'financeaccount');
                $bookingDescription = 'Inkoop';
                break;
            case 'verkoop':
                $defaultAccountId = (int) Setting::get('bank_account', null, 'financeaccount');
                $defaultDebitCredit = 'bij';
                $bookingDescription = 'Verkoop';
                break;
            case 'kosten':
                $defaultAccountId = (int) Setting::get('operating_expense_account', null, 'financeaccount');
                $bookingDescription = 'Kosten';
                break;
            case 'shipping':
                $defaultAccountId = (int) Setting::get('shipping_expense_account', null, 'financeaccount');
                $bookingDescription = 'Verzendkosten';
                break;
            case 'marketing':
                $defaultAccountId = (int) Setting::get('advertising_expense_account', null, 'financeaccount');
                $bookingDescription = 'Advertentiekosten';
                break;
            case 'overig':
            default:
                $defaultAccountId = (int) Setting::get('other_expense_account', null, 'financeaccount');
                $bookingDescription = 'Overige uitgaven';
                break;
        }

        // Bepaal de transactie-account-ID: dit is de account waarop de transactie initieel geboekt wordt.
        // Als er een specifieke booking_account is opgegeven, gebruiken we het bijbehorende schuld-account;
        // anders blijft dit gelijk aan de standaard (doel)rekening.
        $transactionAccountId = $defaultAccountId;
             
        if ($bookingAccount) {
            switch (strtolower($bookingAccount)) {
                case 'sanne':
                    $transactionAccountId = (int) Setting::get('debt_account_sanne', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via Schuld Sanne)';
                    break;
                case 'sander':
                    $transactionAccountId = (int) Setting::get('debt_account_sander', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via Schuld Sander)';
                    break;
                case 'extern':
                    $transactionAccountId = (int) Setting::get('debt_account_extern', null, 'financeaccount');
                    $bookingDescription .= ' (geboekt via externe schuld)';
                    break;
                case 'org':
                    $transactionAccountId = $defaultAccountId;
                    $bookingDescription .= ' (organisatie)';
                    break;
            }
        }

        // Stel de linked_key JSON samen met schuldverdeling (alleen voor inkoopfacturen)
        if ($type === 'inkoop') {
            $linkedKeyData = [
                'total'            => (float)$newTotal,
                'amount_booked'    => 0.00,
                'amount_open'      => (float)$newTotal,
                'original_account' => $defaultAccountId,
                'debt_account'     => ($transactionAccountId != $defaultAccountId) ? $transactionAccountId : null,
                'booking_account'  => $bookingAccount,
            ];
        } else {
            $linkedKeyData = [
                'total'            => (float)$newTotal,
                'amount_booked'    => 0.00,
                'amount_open'      => (float)$newTotal,
                'original_account' => $transactionAccountId,
                'debt_account'     => null,
                'booking_account'  => $bookingAccount,
            ];
        }
        $account = \App\Models\FinanceAccount::find($transactionAccountId);
        $oldBalanc = $account->balance;
        
        // Zoek de bestaande FinanceTransaction voor deze factuur
        $transaction = FinanceTransaction::where('invoice_id', $invoice->id)->first();
        if ($transaction) {
            $oldAmount = $transaction->amount;
            $transaction->update([
                'amount'           => $newTotal,
                'transaction_date' => \Carbon\Carbon::now(),
                'linked_key'       => json_encode($linkedKeyData),
                'order_id'         => $invoice->order_id ?? null,
                'purchase_order_id'=> $invoice->purchase_order_id ?? null,
                'account_id'       => $transactionAccountId,
                'debit_credit'     => $defaultDebitCredit,
            ]);
            activity()
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_amount'      => $oldAmount,
                    'new_amount'      => $newTotal,
                    'invoice_id'      => $invoice->id,
                    'booking_account' => $bookingAccount,
                ])
                ->log('Financiële transactie bijgewerkt bij factuurupdate');
                
        } else {
            $transaction = FinanceTransaction::create([
                'invoice_id'       => $invoice->id,
                'account_id'       => $transactionAccountId,
                'debit_credit'     => $defaultDebitCredit,
                'amount'           => $newTotal,
                'description'      => 'Factuur update: ' . $invoice->invoice_number . ' ' . $bookingDescription,
                'linked_key'       => json_encode($linkedKeyData),
                'order_id'         => $invoice->order_id ?? null,
                'purchase_order_id'=> $invoice->purchase_order_id ?? null,
                'transaction_date' => \Carbon\Carbon::now(),
            ]);
            activity()
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->withProperties([
                    'invoice_id'      => $invoice->id,
                    'amount'          => $newTotal,
                    'booking_account' => $bookingAccount,
                ])
                ->log('Financiële transactie aangemaakt bij factuurupdate');
        }
    
        DB::commit();
    
        return redirect()->route('invoices.index')
            ->with('success', 'Factuur succesvol bijgewerkt.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors('Er is een fout opgetreden bij het bijwerken van de factuur: ' . $e->getMessage());
    }
}


   
public function destroy($id)
{
    $invoice = Invoice::with('invoiceLines')->findOrFail($id);

    
    return DB::transaction(function () use ($invoice) {
        $financeTransactions = FinanceTransaction::where('invoice_id', $invoice->id)->get();
        foreach ($financeTransactions as $transaction) {
            // Verwijder de finance transaction zelf
            $transaction->delete();
        }

            // Verwijder de oorspronkelijke transactie
            $transaction->delete();
        

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Factuur succesvol verwijderd.');
    });
}


    
    protected function validateInvoice(Request $request)
    {
        return $request->validate([
            'supplier_id'           => 'nullable|integer|exists:suppliers,id',
            'purchase_order_id'     => 'nullable|integer|exists:purchase_orders,id',
            'name'                  => 'nullable|string',
            'invoice_reference'     => 'nullable|string',
            'description'           => 'nullable|string',
            'notes'                 => 'nullable|string',
            'date'                  => 'required|date',
            'type'                  => 'required|string',
            'status'                => 'required|string',
            'linking_documents'     => 'nullable|json',   
            'invoice_lines'         => 'required|array|min:1',
            'invoice_lines.*.quantity'  => 'required|numeric|min:0.01',
            'invoice_lines.*.unit_price'=> 'required|numeric|min:0.01',
        ]);
    }
    
    protected function validateInvoiceLine(array $data)
    {
        return Validator::make($data, [
            'product_id'            => 'nullable|integer|exists:products,id',
            'quantity'              => 'required|numeric|min:0.01',
            'description'           => 'nullable|string',
            'amount_excl_vat_total' => 'required|numeric',
            'amount_incl_vat_total' => 'required|numeric',
            'total_vat'             => 'required|numeric',
            'vat_rate'              => 'required|numeric',
            'remarks'               => 'nullable|string',
        ])->validate();
    }
    
    protected function generateInvoiceNumber()
    {
        $year = date('Y');
        $lastInvoice = Invoice::where('invoice_number', 'like', $year . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
    
        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, 5));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
    
        $newNumberPadded = str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    
        return $year . "-" . $newNumberPadded;
    }
    
    protected function updateExpiredInvoices()
    {
        $now = now();
        $expiredInvoices = Invoice::whereNotNull('invoice_due_date')
            ->where('status', '!=', 'betaald')
            ->get();
        foreach ($expiredInvoices as $invoice) {
            if (Carbon::parse($invoice->invoice_due_date)->lessThan($now)) {
                $oldStatus = $invoice->status;
                $invoice->update(['status' => 'vervallen']);
                activity()
                    ->performedOn($invoice)
                    ->withProperties(['old_status' => $oldStatus, 'new_status' => 'vervallen'])
                    ->log('Automatische statusupdate: Factuur is vervallen.');
            }
        }
    }
}

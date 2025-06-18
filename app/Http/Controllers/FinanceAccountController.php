<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceAccountController extends Controller
{
    /**
     * Haal een lijst van alle financiële rekeningen op.
     */
    public function index()
    {
        // Haal alle hoofdrekeningen (zonder parent) op met hun children (hiermee worden de kinderen niet recursief ingeladen, dus als er meer niveaus zijn kun je eventueel 'children.children' meegeven of een recursive functie gebruiken)
        $accounts = FinanceAccount::with('children')
                      ->whereNull('parent_id')
                      ->orderBy('account_code')
                      ->orderBy('id', 'desc')
                      ->get();
    
        // Voor de modal: lijst met hoofdrekeningen waar je als bovenliggende rekening uit kunt kiezen
        $parentAccounts = FinanceAccount::whereNull('parent_id')
                    ->orderBy('account_code')
                    ->get();
        $accountCategories = Parameter::where('key', 'account_category')->get();
        return view('financial.accounts', compact('accounts', 'accountCategories', 'parentAccounts'));
    }
    
    
    /**
     * Maak een nieuwe financiële rekening aan.
     */
    public function store(Request $request)
    {  
        $active = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $validatedData = $request->validate([
            'parent_id'     => 'nullable|exists:finance_accounts,id',
            'account_code'  => 'required|string|max:50|unique:finance_accounts,account_code',
            'account_name'  => 'required|string|max:255',
            'category'      => 'nullable|string|max:100',
            'balance'       => 'nullable|numeric',
        ]);

        // Als een bovenliggende rekening is opgegeven, moet deze een hoofdrekening zijn.
        if (!empty($validatedData['parent_id'])) {
            $parent = FinanceAccount::find($validatedData['parent_id']);
            if ($parent && $parent->parent_id !== null) {
                return back()->withErrors(['parent_id' => 'De bovenliggende rekening moet een hoofdrekening zijn.'])->withInput();
            }
        }
        if(empty($validatedData['balance'])){
            $validatedData['balance'] = 0;
        }
        // Als is_active false maar saldo ongelijk 0 is, dan fout.
        if (isset($validatedData['is_active']) && !$validatedData['is_active'] && floatval($validatedData['balance']) != 0) {
            return response()->json(['errors' => ['is_active' => ['Een rekening met een saldo ongelijk aan 0 kan niet inactief worden gezet.']]], 422);
        }
        if (empty($validatedData['parent_id']) && floatval($validatedData['balance']) != 0) {
            return back()->withErrors(['balance' => 'Een hoofdrekening (zonder bovenliggende rekening) moet een saldo van 0 hebben.'])->withInput();
        }

        $account = FinanceAccount::create($validatedData);


        activity()
        ->performedOn($account)
        ->causedBy(auth()->user())
        ->withProperties($account->toArray())
        ->log('Bankrekening aangemaakt');

        return response()->json($account, 201);
    }

    /**
     * Toon een specifieke financiële rekening.
     */
    public function show($id)
    {
        $account = FinanceAccount::with(['parent', 'children', 'financeTransactions'])->findOrFail($id);
        return response()->json($account);
    }

    /**
     * Update een bestaande financiële rekening.
     */
    public function update(Request $request, $id)
    {
        $account = FinanceAccount::findOrFail($id);
        $oldValue = $account->value;
        
        $active = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $validatedData = $request->validate([
            'parent_id'     => 'nullable|exists:finance_accounts,id',
            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('finance_accounts', 'account_code')->ignore($account->id),
            ],
            'account_name'  => 'required|string|max:255',
            'category'      => 'nullable|string|max:100',
        ]);

        // Indien iemand inactief wil zetten maar het saldo niet 0 is:
        if (isset($validatedData['is_active']) && !$validatedData['is_active'] && floatval($validatedData['balance']) != 0) {
            return response()->json(['errors' => ['is_active' => ['Een rekening met een saldo ongelijk aan 0 kan niet inactief worden gezet.']]], 422);
        }    

        // Controleer bovenliggende rekening.
        if (!empty($validatedData['parent_id'])) {
            if ($validatedData['parent_id'] == $account->id) {
                return back()->withErrors(['parent_id' => 'Je kunt niet dezelfde rekening als bovenliggende kiezen.'])->withInput();
            }
            $parent = FinanceAccount::find($validatedData['parent_id']);
            if ($parent && $parent->parent_id !== null) {
                return back()->withErrors(['parent_id' => 'De bovenliggende rekening moet een hoofdrekening zijn.'])->withInput();
            }
        }

        // Als het account een hoofdrekening is (heeft kinderen), dan wordt het saldo berekend als de som van de children
        if ($account->children()->count() > 0) {
            $childrenSum = $account->children()->sum('balance');
            $validatedData['balance'] = $childrenSum;
        }

        $account->update($validatedData);


        activity()
        ->performedOn($account)
        ->causedBy(auth()->user())
        ->withProperties([
            'old' => $oldValue,
            'new' => $account->value
        ])
        ->log('Bankaccount bijgewerkt');

        return response()->json($account);
    }
    /**
     * Overview van de schuldenrekeningen en de mogelijkheid deze te boeken.
     */
public function debtOverview()
{
    // Haal alle FinanceAccounts op die een parent_id hebben, behoren tot de categorie 'schulden'
    // en waarvan het saldo (bijvoorbeeld intern als negatief opgeslagen) niet nul is.
    $accounts = FinanceAccount::whereNotNull('parent_id')
        ->where('category', 'schulden')
        ->where('balance', '!=', 0)
        ->orderBy('account_code')
        ->get();

    // Voor ieder account halen we de FinanceTransactions op waarop in het linked_key veld de schuld-reeks is opgeslagen.
    // We zoeken bijvoorbeeld naar transacties waarbij JSON_EXTRACT(linked_key, '$.debt_account') gelijk is aan het account-ID.
    foreach ($accounts as $account) {
        $account->openTransactions = FinanceTransaction::whereNotNull('linked_key')
            ->whereRaw("JSON_EXTRACT(linked_key, '$.debt_account') = ?", [$account->id])
            ->get()
            ->filter(function ($transaction) {
                $data = json_decode($transaction->linked_key, true);
                return isset($data['amount_open']) && floatval($data['amount_open']) > 0;
            });
    }

    return view('financial.debts', compact('accounts'));
}


    /**
     * Staat aanpassingen in schulden toe
     */
    public function debtReversalUpdate(Request $request, FinanceAccount $account)
    {
        $validated = $request->validate([
            'amount'     => 'required|numeric|min:0',
            'description'=> 'required|string|max:255',
            'transaction_id' => 'required|integer', // De ID van de FinanceTransaction die je wilt aanpassen
        ]);
    
        $amount = floatval($validated['amount']);
    
        if ($amount > abs($account->balance)) {
            return back()->withErrors("Het af te boeken bedrag mag niet hoger zijn dan het huidige saldo (" . number_format(abs($account->balance), 2, ',', '.') . ").");
        }
    
        // Vind de FinanceTransaction die je wilt aanpassen.
        $transaction = FinanceTransaction::findOrFail($validated['transaction_id']);
    
        // Decodeer het linked_key veld.
        $data = json_decode($transaction->linked_key, true);
        if (!$data || !isset($data['amount_open'])) {
            return back()->withErrors("Deze transactie heeft geen schuldinformatie.");
        }
    
        // Controleer dat het ingevoerde bedrag niet hoger is dan het open bedrag.
        if ($amount > floatval($data['amount_open'])) {
            return back()->withErrors("Het af te boeken bedrag mag niet hoger zijn dan het openstaande bedrag (" . number_format(floatval($data['amount_open']), 2, ',', '.') . ").");
        }
    
        // Pas de schuldgegevens aan.
        $data['amount_booked'] = isset($data['amount_booked']) ? floatval($data['amount_booked']) + $amount : $amount;
        $data['amount_open'] = floatval($data['total']) - $data['amount_booked'];
        if ($data['amount_open'] <= 0) {
            $data = null; // Volledig afbetaald, maak linked_key leeg.
        } else {
            $data = json_encode($data);
        }
        $transaction->linked_key = $data;
        $transaction->saveQuietly();
    
        // Update het saldo van de schuldrekening (verhogen, zodat het negatieve saldo dichter bij 0 komt).
        $account->balance += $amount;
        $account->save();
    
        // Update ook het saldo van de doelrekening (waar de originele transactie op staat)
        $targetAccount = FinanceAccount::find($transaction->account_id);
        if ($targetAccount) {
            $targetAccount->balance += $amount;
            $targetAccount->save();
            activity()
                ->performedOn($targetAccount)
                ->withProperties([
                    'new_balance' => $targetAccount->balance,
                    'settled_amount' => $amount,
                ])
                ->log("Ontvangen externe betaling geboekt op account ID {$targetAccount->id} via schuldafboeking");
        }
    
        activity()
            ->performedOn($account)
            ->withProperties([
                'new_balance' => $account->balance,
                'reversed_amount' => $amount
            ])
            ->log("Schuld afboeking verwerkt voor account ID {$account->id}");
    
        return redirect()->route('financial.debts')
            ->with('success', 'Afboeking verwerkt.');
    }
    
   /**
     * Toggle de actieve status via AJAX.
     */
    public function toggleActive(Request $request, $id)
    {
        $account = FinanceAccount::findOrFail($id);
        $oldValue = $account->value;
        $newActive = $request->boolean('is_active');
        if (!$newActive && floatval($account->balance) != 0) {
            return response()->json(['error' => 'Een rekening met een saldo ongelijk aan 0 kan niet inactief worden gezet.'], 422);
        }
        $account->update(['is_active' => $newActive]);

        activity()
        ->performedOn($account)
        ->causedBy(auth()->user())
        ->withProperties([
            'old' => $oldValue,
            'new' => $account->value
        ])
        ->log('Bankaccount is gewijzigd');
        
        return response()->json($account);
    }

    /**
     * Update het saldo van een account op basis van een correctie.
     */
    public function updateBalance(Request $request, $id)
    {
        $validatedData = $request->validate([
            'new_balance' => 'required|numeric',
            'reason'      => 'required|string',
        ]);
    
        $account = FinanceAccount::findOrFail($id);
        $oldBalance = $account->balance;
        $newBalance = $validatedData['new_balance'];
        $difference = $newBalance - $oldBalance;
    
        $account->balance = $newBalance;
        $account->save();
    
        if ($difference < 0) {
            // Correctie waarbij het saldo moet dalen: gebruik een af boeking
            FinanceTransaction::create([
                'account_id'      => $account->id,
                'debit_credit'    => 'af',
                'amount'          => abs($difference),
                'description'     => 'Saldo correctie: ' . $validatedData['reason'],
                'transaction_date'=> now(),
            ]);
        } else {
            // Correctie waarbij het saldo moet stijgen
            FinanceTransaction::create([
                'account_id'      => $account->id,
                'debit_credit'    => 'bij',
                'amount'          => $difference, // verschil is hier al positief
                'description'     => 'Saldo correctie: ' . $validatedData['reason'],
                'transaction_date'=> now(),
            ]);
        }
        
        activity()
            ->performedOn($account)
            ->causedBy(auth()->user())
            ->withProperties([
                'old'        => $oldBalance,
                'new'        => $newBalance,
                'difference' => $difference,
                'reason'     => $validatedData['reason']
            ])
            ->log('Banksaldo is gewijzigd');
    
        return response()->json(['success' => 'Saldo bijgewerkt'], 200);
    }
    
    /**
     * Haal de logs op voor een account.
     */
    public function logs($id)
    {
        $account = FinanceAccount::findOrFail($id);
        $logs = $account->activities()->orderBy('created_at', 'desc')->get();
        return response()->json(['logs' => $logs]);
    }
    
    /**
     * Verwijder een financiële rekening.
     * Zorg ervoor dat een rekening niet verwijderd kan worden zolang er nog transacties aan hangen.
     */
    public function destroy($id)
    {
        $account = FinanceAccount::findOrFail($id);
        if ($account->financeTransactions()->count() > 0) {
            return response()->json(['error' => 'Deze rekening kan niet worden verwijderd omdat er nog transacties aan hangen.'], 422);
        }
        $account->delete();
        return response()->json(null, 204);
    }
}

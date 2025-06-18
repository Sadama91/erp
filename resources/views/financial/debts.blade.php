@extends('layouts.app')

@section('page_title', 'Schuld Afboeking')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-6">Schuld Afboeking</h1>
    
    @if($accounts->isEmpty())
        <p class="text-gray-700">Er zijn op dit moment geen schuldenrekeningen met een openstaand saldo.</p>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Naam</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($accounts as $account)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $account->account_code }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $account->account_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            € {{ number_format(abs($account->balance), 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                            <button type="button" 
                                    class="open-debt-modal inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                    data-account-id="{{ $account->id }}"
                                    data-account-balance="{{ abs($account->balance) }}">
                                Afboeken
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<!-- Modal voor schuldafboeking -->
<div id="debtModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded shadow-lg w-full max-w-lg p-6 relative">
        <h2 class="text-xl font-bold mb-4">Afboeken voor Schuldrekening</h2>
        <p id="modalAccountInfo" class="mb-4 text-gray-700"></p>
        
        <!-- Hier tonen we een lijst van open transacties -->
        <div id="openTransactionsContainer" class="mb-4">
            <!-- Dit wordt via AJAX of via vooraf geladen data ingevuld -->
        </div>
        
        <form id="debtForm" method="POST" action="{{ route('financial.debt_settlement') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="account_id" id="modalAccountId" value="">
            <!-- Indien nodig, voeg hier een verborgen field toe voor meerdere transactie IDs, 
                 of werk per transactie in de modal (bijvoorbeeld met een iteratie) -->
            <div class="mb-4">
                <label for="paymentAmount" class="block font-semibold mb-1">Te boeken bedrag</label>
                <input type="number" step="0.01" min="0" name="amount" id="paymentAmount" class="w-full border p-2 rounded" required>
                <small id="maxAmountInfo" class="text-gray-500"></small>
            </div>
            <div class="mb-4">
                <label for="paymentDescription" class="block font-semibold mb-1">Reden</label>
                <textarea name="description" id="paymentDescription" rows="3" class="w-full border p-2 rounded" required></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="modalCancel" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">Annuleren</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Boeken</button>
            </div>
        </form>
        <button id="modalClose" class="absolute top-2 right-2 text-gray-600 hover:text-gray-800">&times;</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function getDebtTransactionUrl(accountId) {
        return "{{ route('financial.debt_transactions', ['account' => '__ACCOUNT_ID__']) }}".replace('__ACCOUNT_ID__', accountId);
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('debtModal');
    const openButtons = document.querySelectorAll('.open-debt-modal');
    const modalClose = document.getElementById('modalClose');
    const modalCancel = document.getElementById('modalCancel');
    const modalAccountId = document.getElementById('modalAccountId');
    const modalAccountInfo = document.getElementById('modalAccountInfo');
    const paymentAmount = document.getElementById('paymentAmount');
    const maxAmountInfo = document.getElementById('maxAmountInfo');
    const openTransactionsContainer = document.getElementById('openTransactionsContainer');
    const debtForm = document.getElementById('debtForm');

    openButtons.forEach(button => {
        button.addEventListener('click', function() {
            const accountId = this.getAttribute('data-account-id');
            const balance = parseFloat(this.getAttribute('data-account-balance'));
            modalAccountId.value = accountId;
            modalAccountInfo.textContent = 'Huidig saldo: € ' + balance.toFixed(2);
            paymentAmount.max = balance;
            maxAmountInfo.textContent = 'Maximaal af te boeken: € ' + balance.toFixed(2);
            paymentAmount.value = '';
            
            // Optioneel: Laad open transacties via AJAX (bijv. GET /finance/debt-transactions/{account})
           fetch(getDebtTransactionUrl(accountId))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if(data.transactions && data.transactions.length > 0) {

                       html += `<table class="w-full mb-4">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-2 py-1 text-left text-xs">Kies</th>
                                    <th class="px-2 py-1 text-left text-xs">Transactie</th>
                                    <th class="px-2 py-1 text-right text-xs">Open bedrag</th>
                                </tr>
                            </thead><tbody>`;
                        data.transactions.forEach(tx => {
                            html += `<tr>
                        <td class="px-2 py-1 text-center">
                            <input type="checkbox"
                                name="transaction_ids[]"
                                value="${tx.id}"
                                class="form-checkbox">
                        </td>
                        <td class="px-2 py-1 text-sm">
                            #${tx.id}${tx.invoice_id ? ' (Factuur ' + tx.invoice_id + ')' : ''}
                        </td>
                        <td class="px-2 py-1 text-sm text-right">
                            € ${Number(tx.amount_open).toFixed(2)}
                        </td>
                    </tr>`;
                    console.log(tx);
                        });
                        html += '</tbody></table>';
                    } else {
                        console.log(data);
                        html = '<p class="text-gray-600">Er zijn geen open transacties voor deze rekening.</p>';
                    }
                    openTransactionsContainer.innerHTML = html;
                })
                .catch(err => {
                    console.error('Fout bij laden open transacties:', err);
                    openTransactionsContainer.innerHTML = '<p class="text-red-600">Kon open transacties niet laden.</p>';
                });

            // Laat de modal zien
            modal.classList.remove('hidden');
        });
    });

    modalClose.addEventListener('click', () => modal.classList.add('hidden'));
    modalCancel.addEventListener('click', () => modal.classList.add('hidden'));
});
</script>
@endsection

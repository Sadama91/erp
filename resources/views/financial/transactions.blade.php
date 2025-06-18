@extends('layouts.app')

@section('page_title', 'Financiële Transacties Overzicht')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-8">Financiële Transacties</h1>

  <!-- Globale foutmelding -->
  <div id="listError" class="text-red-500 mb-4 hidden"></div>

  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">ID</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Datum</th>
          <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">Bedrag</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Rekening</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Bestelling</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Factuur</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Inkoop Order</th>
          <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">BTW koppeling</th>
          <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Acties</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        @foreach($transactions as $transaction)
        <tr>
          <td class="px-4 py-2 text-sm text-gray-800">{{ $transaction->id }}</td>
          <td class="px-4 py-2 text-sm text-gray-800">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y') }}</td>
          <td class="px-4 py-2 text-sm text-gray-800 text-right">
            {{ number_format($transaction->amount, 2, ',', '.') }} &euro;
          </td>
          <td class="px-4 py-2 text-sm text-gray-800">
            {{ $transaction->financeAccount->account_name ?? 'N.v.t.' }}
          </td>
          <td class="px-4 py-2 text-sm text-gray-800">
            {{ $transaction->order_id ?? '-' }}
          </td>
          <td class="px-4 py-2 text-sm text-gray-800">
            {{ $transaction->invoice_id ?? '-' }}
          </td>
          <td class="px-4 py-2 text-sm text-gray-800">
            {{ $transaction->purchase_order_id ?? '-' }}
          </td>
          <td class="px-4 py-2 text-sm text-gray-800">
            {{ $transaction->vat_id ?? '-' }}
          </td>
          <td class="px-4 py-2 text-center">
            <button type="button" class="logsTransactionBtn bg-blue-500 text-white px-3 py-1 rounded" data-id="{{ $transaction->id }}">
              Logs
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
  // Functies voor foutmeldingen
  function showError(selector, message) {
    $(selector).removeClass('hidden').html(message);
  }
  function clearError(selector) {
    $(selector).addClass('hidden').html('');
  }

  // Open de logs modal voor een transactie
  $('.logsTransactionBtn').on('click', function(){
    const transactionId = $(this).data('id');
    $('#transactionLogsModal').removeClass('hidden');
    $('#transactionLogsContent').html('<p class="text-sm">Loading logs...</p>');

    $.ajax({
      url: '/financial/transactions/' + transactionId + '/logs',
      type: 'GET',
      success: function(response){
        let logsHtml = '';
        if(response.logs && response.logs.length > 0){
          logsHtml += '<ul class="space-y-2">';
          response.logs.forEach(function(log){
            const date = new Date(log.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            logsHtml += '<li class="border-b pb-2">';
            logsHtml += '<strong class="text-sm">' + log.description + '</strong><br>';
            logsHtml += '<small class="text-gray-600">' + formattedDate + '</small>';
            if(log.properties && log.properties.old !== undefined && log.properties.new !== undefined){
              logsHtml += '<div class="text-xs text-gray-600"><em>Oud: ' + log.properties.old + ' | Nieuw: ' + log.properties.new + '</em></div>';
            }
            logsHtml += '</li>';
          });
          logsHtml += '</ul>';
        } else {
          logsHtml = '<p class="text-sm text-gray-600">Geen logs gevonden.</p>';
        }
        $('#transactionLogsContent').html(logsHtml);
      },
      error: function(){
        $('#transactionLogsContent').html('<p class="text-red-500 text-sm">Fout bij het laden van logs.</p>');
      }
    });
  });

  // Sluit de transactie logs modal
  $('#closeTransactionLogsBtn').on('click', function(){
    $('#transactionLogsModal').addClass('hidden');
  });
});
</script>

<!-- Modal voor transactie logs -->
<div id="transactionLogsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
    <button id="closeTransactionLogsBtn" class="absolute top-2 right-2 text-gray-500 text-xl">&times;</button>
    <h2 class="text-lg font-bold mb-4">Transactie Logs</h2>
    <div id="transactionLogsContent" class="max-h-64 overflow-y-auto"></div>
  </div>
</div>
@endsection

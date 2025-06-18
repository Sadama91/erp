@extends('layouts.app')

@section('page_title', 'Financiële Rekeningen beheren')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow"

  <!-- Globale foutmelding -->
  <div id="listError" class="text-red-500 mb-4 hidden"></div>

  <!-- Knop voor nieuwe rekening -->
  <div class="mb-6">
    <button id="addAccountBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Nieuwe Rekening Toevoegen</button>
  </div>

  <!-- Overzicht met geneste rekeningen -->
  <div class="space-y-4">
    @foreach($accounts->where('parent_id', null) as $account)
      <!-- Hoofdrekening -->
      <div class="bg-gray-100 border border-gray-300 rounded p-4 flex justify-between items-center">
        <div class="flex-1">
          <div class="font-bold">{{ $account->account_name }}</div>
          <div class="text-sm text-gray-600">
            @php
              $cat = collect($accountCategories)->firstWhere('value', $account->category);
            @endphp
            {{ $cat ? $cat['name'] : '-' }}
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-lg font-semibold">
            € {{ number_format($account->children()->count() ? $account->children()->sum('balance') : $account->balance, 2) }}
          </div>
          <div>
            <label class="inline-flex items-center">
              <input type="checkbox" class="toggleActive" data-id="{{ $account->id }}" data-balance="{{ $account->balance }}"
                {{ $account->is_active ? 'checked' : '' }}
                {{ $account->balance != 0 ? 'disabled' : '' }}>
            </label>
          </div>
          <div class="space-x-2">
            <button type="button" class="editAccountBtn bg-yellow-500 text-white px-2 py-1 rounded" data-id="{{ $account->id }}">Bewerken</button>
            <button type="button" class="saldoCorrectieBtn bg-purple-500 text-white px-2 py-1 rounded" data-id="{{ $account->id }}">Saldo Correctie</button>
            <button type="button" class="logsAccountBtn bg-blue-500 text-white px-2 py-1 rounded" data-id="{{ $account->id }}">Logs</button>
            @if($account->children()->count())
              <button type="button" class="toggleChildrenBtn bg-gray-500 text-white px-2 py-1 rounded" data-id="{{ $account->id }}">subrekeningen</button>
            @endif
          </div>
        </div>
      </div>

      <!-- Kind-rekeningen (ingesproken en standaard verborgen) -->
      @if($account->children->count())
        <div id="children-{{ $account->id }}" class="ml-6 space-y-2">
          @foreach($account->children as $child)
            <div class="bg-white border border-gray-200 rounded p-3 flex justify-between items-center">
              <div class="flex-1">
                <div>{{ $child->account_name }}</div>
                <div class="text-sm text-gray-600">
                  @php
                    $childCat = collect($accountCategories)->firstWhere('value', $child->category);
                  @endphp
                  {{ $childCat ? $childCat['name'] : '-' }}
                </div>
              </div>
              <div class="flex items-center space-x-4">
                <div class="text-lg font-semibold">
                  € {{ number_format($child->balance, 2) }}
                </div>
                <div>
                  <label class="inline-flex items-center">
                    <input type="checkbox" class="toggleActive" data-id="{{ $child->id }}" data-balance="{{ $child->balance }}"
                      {{ $child->is_active ? 'checked' : '' }}
                      {{ $child->balance != 0 ? 'disabled' : '' }}>
                  </label>
                </div>
                <div class="space-x-2">
                  <button type="button" class="editAccountBtn bg-yellow-500 text-white px-2 py-1 rounded" data-id="{{ $child->id }}">Bewerken</button>
                  <button type="button" class="saldoCorrectieBtn bg-purple-500 text-white px-2 py-1 rounded" data-id="{{ $child->id }}">Saldo Correctie</button>
                  <button type="button" class="logsAccountBtn bg-blue-500 text-white px-2 py-1 rounded" data-id="{{ $child->id }}">Logs</button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    @endforeach
  </div>
</div>

<!-- Modal voor saldo correctie -->
<div id="saldoCorrectieModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
    <h2 class="text-lg font-bold mb-4">Saldo Correctie</h2>
    <form id="saldoCorrectieForm">
      @csrf
      <input type="hidden" name="account_id" id="saldoAccountId">
      <div class="mb-4">
        <label class="block font-semibold mb-1">Nieuw Saldo</label>
        <input type="number" step="0.01" name="new_balance" id="saldoCorrectieNewBalance" class="w-full border p-2 rounded" required>
      </div>
      <div class="mb-4">
        <label class="block font-semibold mb-1">Reden</label>
        <textarea name="reason" id="saldoCorrectieReason" class="w-full border p-2 rounded" required></textarea>
      </div>
      <div id="saldoCorrectieError" class="text-red-500 mb-2 hidden"></div>
      <div class="flex justify-end">
        <button type="button" id="cancelSaldoCorrectieBtn" class="bg-red-600 text-white px-4 py-2 rounded mr-2">Annuleren</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Opslaan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal voor bewerken/toevoegen van een account -->
<div id="accountModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
    <h2 class="text-lg font-bold mb-4" id="accountModalTitle">Rekening Bewerken</h2>
    <form id="accountForm">
      @csrf
      <input type="hidden" name="id" id="accountId">
      
      <div class="mb-4">
        <label class="block font-semibold mb-1">Account Code</label>
        <input type="text" name="account_code" id="accountCode" class="w-full border p-2 rounded" required>
      </div>
      
      <div class="mb-4">
        <label class="block font-semibold mb-1">Account Naam</label>
        <input type="text" name="account_name" id="accountName" class="w-full border p-2 rounded" required>
      </div>
      
      <!-- Dropdown voor hoofdrekening -->
      <div class="mb-4">
        <label class="block font-semibold mb-1">Hoofdrekening</label>
        <select name="parent_id" id="accountParent" class="w-full border p-2 rounded">
          <option value="">-- Geen hoofdrekening --</option>
          @foreach($accounts->where('parent_id', null) as $parent)
            <option value="{{ $parent->id }}">{{ $parent->account_name }}</option>
          @endforeach
        </select>
      </div>
      
      <!-- Dropdown voor categorie -->
      <div class="mb-4">
        <label class="block font-semibold mb-1">Categorie</label>
        <select name="category" id="accountCategory" class="w-full border p-2 rounded">
          <option value="">-- Selecteer een categorie --</option>
          @foreach($accountCategories as $cat)
            <option value="{{ $cat['value'] }}">{{ $cat['name'] }}</option>
          @endforeach
        </select>
      </div>
      
      <div id="accountError" class="text-red-500 mb-2 hidden"></div>
      
      <div class="flex justify-end">
        <button type="button" id="cancelAccountBtn" class="bg-red-600 text-white px-4 py-2 rounded mr-2">Annuleren</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Opslaan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal voor account logs -->
<div id="accountLogsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
    <button id="closeAccountLogsBtn" class="absolute top-2 right-2 text-gray-500 text-xl">&times;</button>
    <h2 class="text-lg font-bold mb-4">Account Logs</h2>
    <div id="accountLogsContent" class="max-h-64 overflow-y-auto"></div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {

  function showError(selector, message) {
      $(selector).removeClass('hidden').html(message);
  }
  function clearError(selector) {
      $(selector).addClass('hidden').html('');
  }

  // Toggle active status via AJAX
  $('.toggleActive').on('change', function() {
      clearError('#listError');
      const accountId = $(this).data('id');
      const newActive = $(this).is(':checked');
      const balance = parseFloat($(this).data('balance'));
      if (!newActive && balance !== 0.00) {
          showError('#listError', "Een rekening met een saldo ongelijk aan 0 kan niet inactief worden gezet.");
          $(this).prop('checked', true);
          return;
      }
      $.ajax({
          url: '/financial/accounts/' + accountId + '/toggle-active',
          type: 'POST',
          data: { is_active: newActive, _token: '{{ csrf_token() }}' },
          success: function(response) {
              // Succesmelding kan hier worden verwerkt
          },
          error: function(xhr) {
              let errorMsg = "Er is een fout opgetreden bij het wijzigen van de status.";
              if (xhr.responseJSON && xhr.responseJSON.error) {
                  errorMsg = xhr.responseJSON.error;
              }
              showError('#listError', errorMsg);
              $(this).prop('checked', !newActive);
          }.bind(this)
      });
  });

  // Toggle de weergave van kind-rekeningen met een slide-effect
  $('.toggleChildrenBtn').on('click', function() {
      const accountId = $(this).data('id');
      $('#children-' + accountId).slideToggle();
  });

  // Open modal voor account logs
  $('.logsAccountBtn').on('click', function() {
      const accountId = $(this).data('id');
      $('#accountLogsModal').removeClass('hidden');
      $('#accountLogsContent').html('<p>Loading logs...</p>');
      $.ajax({
          url: '/financial/accounts/' + accountId + '/logs',
          type: 'GET',
          success: function(response) {
              let logsHtml = '';
              if (response.logs && response.logs.length > 0) {
                  logsHtml += '<ul class="space-y-2">';
                  response.logs.forEach(function(log) {
                      const date = new Date(log.created_at);
                      const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                      logsHtml += '<li class="border-b pb-2">';
                      logsHtml += '<strong>' + log.description + '</strong><br>';
                      logsHtml += '<small>' + formattedDate + '</small>';
                      if(log.properties && log.properties.old !== undefined && log.properties.new !== undefined) {
                          logsHtml += '<div><em>Oud: ' + log.properties.old + ' | Nieuw: ' + log.properties.new + '</em></div>';
                      }
                      logsHtml += '</li>';
                  });
                  logsHtml += '</ul>';
              } else {
                  logsHtml = '<p>Geen logs gevonden.</p>';
              }
              $('#accountLogsContent').html(logsHtml);
          },
          error: function() {
              $('#accountLogsContent').html('<p class="text-red-500">Fout bij het laden van logs.</p>');
          }
      });
  });

  // Sluit de logs modal
  $('#closeAccountLogsBtn').on('click', function() {
      $('#accountLogsModal').addClass('hidden');
  });

  // Open modal voor toevoegen van een account
  $('#addAccountBtn').on('click', function() {
      clearError('#accountError');
      $('#accountModal').removeClass('hidden');
      $('#accountModalTitle').text('Nieuwe Rekening');
      $('#accountId').val('');
      $('#accountCode').val('');
      $('#accountName').val('');
      $('#accountParent').val('');
      $('#accountCategory').val('');
      $('#accountParent option').prop('disabled', false);
  });

  // Open modal voor bewerken van een account
  $('.editAccountBtn').on('click', function() {
      const accountId = $(this).data('id');
      $.ajax({
          url: '/financial/accounts/' + accountId,
          type: 'GET',
          success: function(account) {
              clearError('#accountError');
              $('#accountModal').removeClass('hidden');
              $('#accountModalTitle').text('Rekening Bewerken');
              $('#accountId').val(account.id);
              $('#accountCode').val(account.account_code);
              $('#accountName').val(account.account_name);
              $('#accountParent').val(account.parent_id);
              $('#accountCategory').val(account.category);
              $('#accountParent option').prop('disabled', false);
              $('#accountParent option[value="'+ account.id +'"]').prop('disabled', true);
          },
          error: function() {
              showError('#listError', "Accountgegevens konden niet worden geladen.");
          }
      });
  });

  // Sluit de account modal
  $('#cancelAccountBtn').on('click', function() {
      $('#accountModal').addClass('hidden');
  });

  // Verzend accountformulier via AJAX
  $('#accountForm').on('submit', function(e) {
      e.preventDefault();
      clearError('#accountError');
      const formData = $(this).serialize();
      const accountId = $('#accountId').val();
      let ajaxUrl = accountId ? '/financial/accounts/' + accountId : '/financial/accounts';
      let ajaxType = accountId ? 'PUT' : 'POST';
      $.ajax({
          url: ajaxUrl,
          type: ajaxType,
          data: formData,
          success: function(response) {
              location.reload();
          },
          error: function(xhr) {
              let errorMsg = 'Er is een fout opgetreden.';
              if (xhr.responseJSON && xhr.responseJSON.errors) {
                  errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
              }
              showError('#accountError', errorMsg);
          }
      });
  });

  // Open saldo correctie modal
  $('.saldoCorrectieBtn').on('click', function() {
      const accountId = $(this).data('id');
      $('#saldoCorrectieModal').removeClass('hidden');
      $('#saldoAccountId').val(accountId);
      $('#saldoCorrectieNewBalance').val('');
      $('#saldoCorrectieReason').val('');
      clearError('#saldoCorrectieError');
  });

  // Sluit saldo correctie modal
  $('#cancelSaldoCorrectieBtn').on('click', function() {
      $('#saldoCorrectieModal').addClass('hidden');
  });

  // Verzend saldo correctie formulier via AJAX
  $('#saldoCorrectieForm').on('submit', function(e) {
      e.preventDefault();
      clearError('#saldoCorrectieError');
      const formData = $(this).serialize();
      const accountId = $('#saldoAccountId').val();
      $.ajax({
          url: '/financial/accounts/' + accountId + '/update-balance',
          type: 'PUT',
          data: formData,
          success: function(response) {
              location.reload();
          },
          error: function(xhr) {
              let errorMsg = 'Er is een fout opgetreden.';
              if (xhr.responseJSON && xhr.responseJSON.errors) {
                  errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
              }
              showError('#saldoCorrectieError', errorMsg);
          }
      });
  });
});
</script>
@endsection

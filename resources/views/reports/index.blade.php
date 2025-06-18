{{-- resources/views/reports/index.blade.php --}}
@extends('layouts.app')

@section('page_title', 'Rapporten')

@section('content')
<div class="container mx-auto p-4">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Beschikbare Rapporten</h1>
    <button
      class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded"
      onclick="openEditModal(null)"
    >
      Nieuwe rapport
    </button>
  </div>

  @if($reports->isEmpty())
    <p class="text-gray-600">Je hebt nog geen rapporten aangemaakt.</p>
  @else
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white border">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 border text-left">Nr</th>
            <th class="px-4 py-2 border text-left">Naam</th>
            <th class="px-4 py-2 border text-left"># Filters</th>
            <th class="px-4 py-2 border text-left">Acties</th>
            @auth
            <th class="px-4 py-2 border text-left">Bewerk</th>
            @endauth
          </tr>
        </thead>
        <tbody>
          @foreach($reports as $report)
            @php
              $all       = json_decode($report->views ?? '{}', true) ?: [];
              $viewsArr  = $all['views'] ?? [];
              $filterCols = [];
              foreach($viewsArr as $cfg){
                if(!empty($cfg['filters']) && is_array($cfg['filters'])){
                  $filterCols = array_merge($filterCols, array_keys($cfg['filters']));
                }
              }
              $filterCount = count(array_unique($filterCols));
            @endphp
            <tr class="even:bg-gray-50">
              <td class="px-4 py-2 border">{{ $report->id }}</td>
              <td class="px-4 py-2 border">{{ $report->name }}</td>
              <td class="px-4 py-2 border">{{ $filterCount }}</td>
              <td class="px-4 py-2 border">
                <form method="GET" action="{{ route('reports.generate') }}" class="inline">
                  <input type="hidden" name="reporting_id" value="{{ $report->id }}">
                  @if($filterCount > 0)
                  <button
                    type="button"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                    onclick="openSettingsModal({{ $report->id }})"
                  >Instellingen</button>
                  @else
                  <button
                    type="submit"
                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded"
                  >Genereer</button>
                  @endif
                </form>
              </td>
              @auth
              <td class="px-4 py-2 border">
                @if($report->user_id === auth()->id())
                  <button
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded"
                    onclick="openEditModal({{ $report->id }})"
                  >Bewerk</button>
                @endif
              </td>
              @endauth
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- Settings Modal --}}
<div id="settings-modal" class="fixed max-h-[90vh] inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-6 rounded shadow-lg w-96">
    <h2 class="text-xl font-semibold mb-4">Instellingen Rapport</h2>
    <form id="settings-form" method="GET" action="{{ route('reports.generate') }}">
      <input type="hidden" name="reporting_id" id="report-id-input" />
      <div class="mb-4">
        <strong>Kies preset:</strong>
        <div id="preset-options" class="mt-2 space-y-2"></div>
      </div>
      <div id="filter-fields" class="space-y-4"></div>
      <div class="mt-6 flex justify-end space-x-2">
        <button type="button" class="px-4 py-2 bg-gray-300 rounded" onclick="closeSettingsModal()">Annuleer</button>
        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Genereer</button>
      </div>
    </form>
  </div>
</div>
{{-- Slide-over Edit/Create Modal --}}
<div id="edit-modal" class="fixed inset-0 z-10 hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
  <!-- Backdrop -->
  <div 
    class="fixed inset-0 bg-gray-500/75 transition-opacity" 
    aria-hidden="true"
    onclick="closeEditModal()"
  ></div>

  <div class="fixed inset-0 overflow-hidden">
    <div class="absolute inset-0 overflow-hidden">
      <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div 
            id="edit-panel"
            class="pointer-events-auto relative w-screen max-w-md transform transition ease-in-out duration-500 translate-x-full"
            >

          <!-- Close button -->
          <div class="absolute top-0 left-0 -ml-8 flex pt-4 pr-2 sm:-ml-10 sm:pr-4">
            <button 
              type="button" 
              class="relative rounded-md text-gray-300 hover:text-white focus:ring-2 focus:ring-white"
              onclick="closeEditModal()"
            >
              <span class="sr-only">Close panel</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                   stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="flex h-full flex-col overflow-y-scroll bg-white py-6 shadow-xl">
            <div class="px-4 sm:px-6">
              <h2 id="slide-over-title" class="text-base font-semibold text-gray-900">
                <span id="edit-modal-title">Rapport bewerken</span>
              </h2>
            </div>
            <div class="relative mt-6 flex-1 px-4 sm:px-6">
              <!-- Form content -->
              <form id="edit-form" class="h-full flex flex-col" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="edit-method" value="POST">

                <div class="space-y-4 flex-1 overflow-auto">
                  <div>
                    <label class="block font-medium mb-1">Naam</label>
                    <input type="text" name="name" id="edit-name"
                           class="w-full border p-2 rounded" />
                  </div>
                  <div>
                    <label class="block font-medium mb-1">Beschrijving</label>
                    <textarea name="description" id="edit-description"
                              class="w-full border p-2 rounded h-20"></textarea>
                  </div>
                  <div>
                    <label class="block font-medium mb-1">SQL Query</label>
                    <textarea name="query" id="edit-query" rows="6"
                              class="w-full border p-2 rounded font-mono text-sm"></textarea>
                  </div>
                  <div>
                    <label class="block font-medium mb-1">Available Filters (JSON)</label>
                    <textarea name="available_filters" id="edit-views" rows="6"
                              class="w-full border p-2 rounded font-mono text-sm"></textarea>
                  </div>
                  <div>
                    <label class="block font-medium mb-1">Reporting Columns (JSON)</label>
                    <textarea name="reporting_columns" id="edit-columns" rows="4"
                              class="w-full border p-2 rounded font-mono text-sm"></textarea>
                  </div>
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                  <button type="button"
                          class="px-4 py-2 bg-gray-300 rounded"
                          onclick="closeEditModal()"
                  >Annuleer</button>
                  <button type="submit"
                          class="px-4 py-2 bg-blue-600 text-white rounded"
                  >Opslaan</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  // Open filter-settings modal
  async function openSettingsModal(reportId) {
    document.getElementById('report-id-input').value = reportId;
    document.getElementById('preset-options').innerHTML = '';
    document.getElementById('filter-fields').innerHTML  = '';

    const resp = await fetch(`/reports/${reportId}/filters`);
    const { views, filterOptions } = await resp.json();

    // render presets
    Object.entries(views).forEach(([key,cfg]) => {
      document.getElementById('preset-options').innerHTML += `
        <label class="flex items-center space-x-2">
          <input type="radio" name="view" value="${key}" />
          <span>${cfg.label}</span>
        </label>`;
    });

    const first = document.querySelector('#preset-options input');
    if (first) {
      first.checked = true;
      renderFilterFields(first.value, views[first.value].filters || {}, filterOptions);
    }

    document.querySelectorAll('#preset-options input[name="view"]').forEach(radio => {
      radio.addEventListener('change', e => {
        renderFilterFields(e.target.value, views[e.target.value].filters || {}, filterOptions);
      });
    });

    document.getElementById('settings-modal').classList.remove('hidden');
  }

  function renderFilterFields(viewKey, filters, filterOptions) {
    const container = document.getElementById('filter-fields');
    container.innerHTML = '';

    Object.entries(filters).forEach(([col,conf]) => {
      if (conf.type === 'date_range') {
        const [d1,d2] = conf.default || ['',''];
        container.innerHTML += `
          <div>
            <label class="block font-medium mb-1">${conf.label}</label>
            <div class="flex space-x-2">
              <input type="date" name="period1" value="${d1}" class="border p-1 rounded"/>
              <input type="date" name="period2" value="${d2}" class="border p-1 rounded"/>
            </div>
          </div>`;
      } else if (conf.type === 'json_array') {
        const opts = filterOptions[col] || [];
        container.innerHTML += `
          <div>
            <label class="block font-medium mb-1">${conf.label}</label>
            <select name="${col}[]" multiple class="w-full border p-1 rounded">
              ${opts.map(o => `
                <option value="${o.value}"
                  ${ (conf.default||[]).includes(o.value) ? 'selected' : '' }>
                  ${o.label}
                </option>`).join('')}
            </select>
          </div>`;
      }
    });
  }

  function closeSettingsModal() {
    document.getElementById('settings-modal').classList.add('hidden');
  }

  // Open edit/create modal
  async function openEditModal(reportId) {
    const modal = document.getElementById('edit-modal');
    const title = document.getElementById('edit-modal-title');
    const form  = document.getElementById('edit-form');
    const methodInput = document.getElementById('edit-method');
document.getElementById('edit-panel').classList.remove('translate-x-full');
document.getElementById('edit-panel').classList.add('translate-x-0');

    // form fields
    const fName  = document.getElementById('edit-name');
    const fDesc  = document.getElementById('edit-description');
    const fQuery = document.getElementById('edit-query');
    const fViews = document.getElementById('edit-views');
    const fCols  = document.getElementById('edit-columns');

    if (reportId) {
      title.textContent = 'Rapport bewerken #' + reportId;
      methodInput.value = 'PUT';
      form.action = `/reports/${reportId}`;
      const res = await fetch(`/reports/${reportId}/edit-data`);
      const data = await res.json();
      fName.value  = data.name;
      fDesc.value  = data.description || '';
      fQuery.value = data.query;
      fViews.value = JSON.stringify(data.available_filters, null, 2);
      fCols.value  = JSON.stringify(data.reporting_columns, null, 2);
    console.log(data);
   
    
    } else {
      title.textContent = 'Nieuw rapport';
      methodInput.value = 'POST';
      form.action = `/reports`;
      fName.value = fDesc.value = fQuery.value = '';
      fViews.value = '{}';
      fCols.value  = '[]';
    }

    modal.classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('edit-panel').classList.remove('translate-x-0');
document.getElementById('edit-panel').classList.add('translate-x-full');

    document.getElementById('edit-modal').classList.add('hidden');
  }
</script>
@endsection

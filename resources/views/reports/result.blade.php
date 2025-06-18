{{-- resources/views/reports/result.blade.php --}}
@extends('layouts.app')

@section('page_title', $report->name)

@section('style')
<style>
  /* Tabel: zachtere lijnen en full-width */
  #report-table, #report-table th, #report-table td {
    border: 1px solid #ddd;
  }
  #report-table {
    border-collapse: collapse;
    width: 100%;
  }

  /* PRINT: verberg alles buiten #printable-area, reset margins */
  @media print {
    html, body { margin:0; padding:0; }
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }
    #printable-area {
      position: absolute; top:0; left:0;
      width:100%; margin:0; padding:0;
    }
    table { page-break-after: auto; }
    thead { display: table-header-group; }
    tr    { page-break-inside: avoid; }
    .no-print { display: none !important; }
    a { color: inherit; text-decoration: none; pointer-events: none; }
  }

  /* PDF: landscape + marge */
  @page {
    size: A4 landscape;
    margin: 1cm;
  }
</style>
@endsection

@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">{{ $report->name }}</h1>
  {{-- 1) Always show the query name and bindings --}}
  @if($data->isEmpty())
 <div class="mb-4 text-sm">
    <p class="text-gray-600">Er zijn geen resultaten voor deze query.</p>
  <div class="mb-2 font-semibold">Query inhoud</div>
  <ul class="list-disc list-inside ml-4 mb-4">
    @foreach($bindings as $key => $val)
      <li><code>{{ $key }}</code>: <code>{{ $val }}</code></li>
    @endforeach
  </ul>



  {{-- 2) If no data, friendly message --}}
     @else
  {{-- Toon geselecteerde preset en filters --}}
  @if(!empty($views))
    <div class="mb-4">
      <strong>Preset:</strong> {{ $views[$selectedView]['label'] ?? '-' }}
    </div>
  @endif

  @if(!empty($filters))
    <div class="mb-6 space-y-2">
      @foreach($filters as $col => $val)
        <div>
          <strong>{{ $views[$selectedView]['filters'][$col]['label'] }}:</strong>
          @if(is_array($val))
            {{ $val[0] }} t/m {{ $val[1] }}
          @else
            {{ $val }}
          @endif
        </div>
      @endforeach
    </div>
  @endif

  {{-- Export- en sorteerknoppen --}}
  <div class="flex space-x-2 mb-4 no-print">
    <button id="print-table"    class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">Print</button>
    <button id="export-csv"     class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Export CSV</button>
    <button id="export-pdf"     class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Export PDF</button>
    <button id="refresh-table"  class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Vernieuwen</button>
    <button id="reset-filters"  class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Reset filters</button>
  </div>

  <div id="printable-area" class="overflow-x-auto">
    <table id="report-table" class="min-w-full bg-white">
      <thead class="bg-gray-100">
        <tr>
          @foreach($columns as $col)
            @if(is_string($col))
              @php
                $key      = $col;
                $label    = Str::title(str_replace('_',' ',$col));
                $sortable = false;
                $tooltip  = '';
              @endphp
            @else
              @php
                $key      = $col['key'];
                $label    = $col['label'];
                $sortable = $col['sortable'] ?? false;
                $tooltip  = $col['tooltip']  ?? '';
              @endphp
            @endif

            <th
              class="px-4 py-2 text-left {{ $sortable ? 'sortable cursor-pointer' : '' }}"
              @if($tooltip) title="{{ $tooltip }}" @endif
            >
              {{ $label }}
              @if($sortable)
                <span class="sort-indicator inline-block w-3"></span>
              @endif
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($data as $row)
          <tr class="even:bg-gray-50">
            @foreach($columns as $col)
              @php
                $key   = is_string($col) ? $col : $col['key'];
                $value = $row->$key ?? '';
              @endphp
              <td class="px-4 py-2">{{ $value }}</td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

@endif
  <div class="mt-4 no-print">
    <a href="{{ route('reports.index') }}" class="text-blue-500 hover:underline">
      ← Terug naar rapporten
    </a>
  </div>
</div>
@endsection

@section('scripts')
  <!-- jsPDF en AutoTable plugin -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const table        = document.getElementById('report-table');
    const tbody        = table.querySelector('tbody');
    const headers      = Array.from(table.querySelectorAll('th.sortable'));
    const originalRows = Array.from(tbody.querySelectorAll('tr'));
    let sortOrder = [];

    // Multi-kolom sortering
    headers.forEach((th, idx) => {
      th.dataset.index     = idx;
      th.dataset.direction = 'none';
      th.addEventListener('click', () => {
        const cur = th.dataset.direction;
        const nxt = cur === 'none' ? 'asc' : cur === 'asc' ? 'desc' : 'none';
        th.dataset.direction = nxt;
        sortOrder = sortOrder.filter(i => i.index !== idx);
        if (nxt !== 'none') sortOrder.unshift({ index: idx, direction: nxt });
        headers.forEach(h => {
          const d = h.dataset.direction;
          h.querySelector('.sort-indicator').textContent = d === 'asc' ? '▲' : d === 'desc' ? '▼' : '';
        });
        applySort();
      });
    });

    function applySort() {
      if (!sortOrder.length) {
        tbody.innerHTML = '';
        originalRows.forEach(r => tbody.appendChild(r));
        return;
      }
      const rows = Array.from(tbody.querySelectorAll('tr'));
      rows.sort((a, b) => {
        for (const { index, direction } of sortOrder) {
          const aText = a.children[index].textContent.trim();
          const bText = b.children[index].textContent.trim();
          const aNum = parseFloat(aText.replace(/[^0-9.-]/g, ''));
          const bNum = parseFloat(bText.replace(/[^0-9.-]/g, ''));
          let cmp = (!isNaN(aNum) && !isNaN(bNum))
            ? aNum - bNum
            : aText.localeCompare(bText, undefined, { numeric: true });
          if (cmp !== 0) return direction === 'asc' ? cmp : -cmp;
        }
        return 0;
      });
      rows.forEach(r => tbody.appendChild(r));
    }

    // Print
    document.getElementById('print-table').addEventListener('click', () => window.print());

    // CSV
    document.getElementById('export-csv').addEventListener('click', () => {
      const data = [
        Array.from(table.querySelectorAll('th')).map(th => `"${th.textContent.trim()}"`),
        ...Array.from(table.querySelectorAll('tbody tr')).map(tr =>
          Array.from(tr.children).map(td => `"${td.textContent.trim().replace(/"/g,'""')}"`)
        )
      ];
      const csv = data.map(r => r.join(',')).join('\r\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href     = url;
      a.download = '{{ \Str::slug($report->name) }}.csv';
      a.click();
      URL.revokeObjectURL(url);
    });

    // PDF via jsPDF + AutoTable
    document.getElementById('export-pdf').addEventListener('click', () => {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
      const margin = 40, titleY = 30, tableY = 60;
      doc.setFontSize(16);
      doc.text('{{ addslashes($report->name) }}', margin, titleY);
      doc.autoTable({
        html: '#report-table',
        startY: tableY,
        theme: 'grid',
        styles: {
          lineColor: [200, 200, 200],
          lineWidth: 0.5,
          cellPadding: 4,
          fontSize: 10
        },
        headStyles: {
          fillColor: [240, 240, 240],
          textColor: 50,
          fontStyle: 'bold'
        },
        alternateRowStyles: {
          fillColor: [255, 255, 255]
        },
        margin: { top: tableY, left: margin, right: margin, bottom: 40 },
        pageBreak: 'auto',
        showHead: 'everyPage'
      });
      doc.save('{{ \Str::slug($report->name) }}.pdf');
    });

    // Vernieuwen & reset
    document.getElementById('refresh-table').addEventListener('click', () => location.reload());
    document.getElementById('reset-filters').addEventListener('click', () => {
      sortOrder = [];
      headers.forEach(h => {
        h.dataset.direction = 'none';
        h.querySelector('.sort-indicator').textContent = '';
      });
      applySort();
    });
  });
  </script>
@endsection

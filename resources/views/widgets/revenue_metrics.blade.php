<div class="widget revenue-metrics col-span-1 bg-white shadow rounded-lg p-4 mb-4 overflow-x-auto">
  <h3 class="text-xl font-bold mb-4">Omzet &amp; KPI’s</h3>

  <div class="mb-4">
    <label for="filter" class="block text-sm font-medium text-gray-700">Periode</label>
    <select id="filter"
            onchange="location.search='?filter='+this.value"
            class="mt-1 block w-48 border-gray-300 rounded-md">
      <option value="all"            {{ $filter === 'all'            ? 'selected' : '' }}>Alles</option>
      <option value="current_month"  {{ $filter === 'current_month'  ? 'selected' : '' }}>Huidige maand</option>
      <option value="last_3_months"  {{ $filter === 'last_3_months'  ? 'selected' : '' }}>Laatste 3 maanden</option>
      <option value="last_year"      {{ $filter === 'last_year'      ? 'selected' : '' }}>Vorig jaar</option>
      <option value="year"           {{ $filter === 'year'           ? 'selected' : '' }}>Dit jaar</option>
    </select>
  </div>

  <ul class="divide-y divide-gray-200">
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Totale omzet:</span>
      <span class="text-right font-semibold">€{{ number_format($revenue, 2, ',', '.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Gem. omzet p/m:</span>
      <span class="text-right font-semibold">€{{ number_format($avgPerMonth, 2, ',', '.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Aantal bestellingen:</span>
      <span class="text-right font-semibold">{{ $ordersCount }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Gem. bestelwaarde:</span>
      <span class="text-right font-semibold">€{{ number_format($avgOrderValue, 2, ',', '.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Aantal verkochte items:</span>
      <span class="text-right font-semibold">{{ $itemsCount }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Gem. itemwaarde:</span>
      <span class="text-right font-semibold">€{{ number_format($avgItemValue, 2, ',', '.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Gerealiseerde marge (€):</span>
      <span class="text-right font-semibold">€{{ number_format($marginValue, 2, ',', '.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span class="text-gray-700">Gerealiseerde marge (%):</span>
      <span class="text-right font-semibold">{{ number_format($marginPercent, 2, ',', '.') }}%</span>
    </li>
  </ul>
</div>

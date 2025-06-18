<div class="widget inventory-balance bg-white shadow rounded-lg p-4 mb-4 max-w-md overflow-x-auto">
  <h3 class="text-xl font-bold mb-4">Voorraad &amp; Balans</h3>

  <table class="w-full text-sm mb-6">
    <thead>
      <tr>
        <th></th>
        <th class="text-right">Totaal</th>
        <th class="text-right">Bestellingen</th>
        <th class="text-right">Aanwezig</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="font-medium">Inkoop artik. #</td>
        <td class="text-right">{{ $totalPurchasedQty }}</td>
        <td class="text-right">{{ $soldQty }}</td>
        <td class="text-right">{{ $presentQty }}</td>
      </tr>
      <tr>
        <td class="font-medium">Inkoopwaarde</td>
        <td class="text-right">€{{ number_format($purchaseValueTotal,2,',','.') }}</td>
        <td class="text-right">–</td>
        <td class="text-right">€{{ number_format($presentValue,2,',','.') }}</td>
      </tr>
      <tr>
        <td class="font-medium">Verkoopwaarde</td>
        <td class="text-right">€{{ number_format($salesValueTotal,2,',','.') }}</td>
        <td class="text-right">–</td>
        <td class="text-right">€{{ number_format($onHoldValue,2,',','.') }}</td>
      </tr>
    </tbody>
  </table>

  <h3 class="text-xl font-bold mb-4 border-t pt-4">Financiële Saldi</h3>
  <ul class="text-sm mb-6">
    <li class="grid grid-cols-2 py-2">
      <span>Schuld Sanne:</span>
      <span class="text-right">€{{ number_format($schuldSanne,2,',','.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span>Schuld Sander:</span>
      <span class="text-right">€{{ number_format($schuldSander,2,',','.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span>Schuld totaal:</span>
      <span class="text-right">€{{ number_format($schuldTotaal,2,',','.') }}</span>
    </li>
    <li class="grid grid-cols-2 py-2">
      <span>Gelden onderweg:</span>
      <span class="text-right">€{{ number_format($onderweg,2,',','.') }}</span>
    </li>
  </ul>

  <div class="border-t pt-4 text-sm space-y-2">
    <div class="flex justify-between">
      <span>Theoretisch saldo:</span>
      <span>€{{ number_format($theoretischSaldo,2,',','.') }}</span>
    </div>
    <div class="flex justify-between">
      <span>Actueel saldo:</span>
      <span>€{{ number_format($actueelSaldo,2,',','.') }}</span>
    </div>
    <div class="flex justify-between font-semibold">
      <span>Verschil (theoretisch − actueel):</span>
      <span class="text-right">€{{ number_format($verschil,2,',','.') }}</span>
    </div>
    
  </div>
</div>

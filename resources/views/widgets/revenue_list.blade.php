

<div class="widget latest-orders-widget bg-white shadow rounded-lg p-4 mb-4 max-w-md overflow-x-auto">
    <h3 class="text-xl font-bold mb-4">Omzet &amp; Marge</h3>

    @if(count($data))
        <ul class="space-y-2">
            @foreach($data as $row)
                <li class="flex justify-between">
                    <span class="font-medium">{{ $row['label'] }}</span>
                    <div class="flex space-x-4">
                        <span>€{{ number_format($row['revenue'], 2, ',', '.') }}</span>
                        <span class="text-sm text-gray-500">{{ number_format($row['margin'], 2, ',', '.') }}%</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-gray-500">Geen omzetgegevens beschikbaar.</p>
    @endif
</div>

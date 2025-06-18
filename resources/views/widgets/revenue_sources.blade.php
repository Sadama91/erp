
<div class="widget latest-orders-widget bg-white shadow rounded-lg p-4 mb-4 max-w-md overflow-x-auto">
    <h3 class="text-xl font-bold mb-4">Omzet per Bron</h3>
<table class="w-full text-left">
    <thead>
        <tr>
            <th>Bron</th>
            @foreach($periods as $period)
                <th>{{ $period }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($table as $source => $months)
            <tr>
                <td class="font-medium">{{ $source }}</td>
                @foreach($periods as $period)
                    <td>
                        €{{ number_format($months[$period] ?? 0, 2, ',', '.') }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

</div>

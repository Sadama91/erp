@if (!empty($importedItems))
    <table border="1" style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr>
                <th>Naam</th>
                <th>Slug</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($importedItems as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['slug'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Merken</h1>

    <button wire:click="create" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Nieuw Merk</button>

    <table class="min-w-full mt-4 border border-gray-200">
        <thead>
            <tr>
                <th class="border-b px-4 py-2 text-left">ID</th>
                <th class="border-b px-4 py-2 text-left">Naam</th>
                <th class="border-b px-4 py-2 text-left">Acties</th>
            </tr>
        </thead>
        <tbody>
            @foreach($brands as $brand)
                <tr class="border-b hover:bg-gray-100">
                    <td class="px-4 py-2">{{ $brand->id }}</td>
                    <td class="px-4 py-2">{{ $brand->name }}</td>
                    <td class="px-4 py-2">
                        <button wire:click="edit({{ $brand->id }})" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Bewerken</button>
                        <button wire:click="delete({{ $brand->id }})" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Verwijderen</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    
</div>

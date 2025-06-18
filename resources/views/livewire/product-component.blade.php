<div class="fixed inset-0 flex items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-1/3">
        <form wire:submit.prevent="save">
            <h2 class="text-lg font-semibold mb-4">{{ $productId ? 'Bewerken' : 'Nieuw Product' }}</h2>

            <div class="mb-4">
                <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                <input type="text" wire:model="sku" id="sku" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Product naam</label>
                <input type="text" wire:model="name" id="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div class="mb-4">
                <label for="price" class="block text-sm font-medium text-gray-700">Prijs</label>
                <input type="number" wire:model="price" id="price" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" step="0.01">
            </div>

            <div class="mb-4">
                <label for="subgroup_id" class="block text-sm font-medium text-gray-700">Subgroep</label>
                <select wire:model="subgroup_id" id="subgroup_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="">Kies een subgroep</option>
                    @foreach($subgroups as $subgroup)
                        <option value="{{ $subgroup->id }}">{{ $subgroup->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="category_id" class="block text-sm font-medium text-gray-700">Categorie</label>
                <select wire:model="category_id" id="category_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="">Kies een categorie</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
                <select wire:model="selectedTags" id="tags" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">{{ $productId ? 'Bijwerken' : 'Toevoegen' }}</button>
                <button type="button" wire:click="$set('showModal', false)" class="ml-2 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Annuleren</button>
            </div>
        </form>
    </div>
</div>

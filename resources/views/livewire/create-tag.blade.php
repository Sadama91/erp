<div class="fixed inset-0 flex items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-1/3">
        <form wire:submit.prevent="save">
            <h2 class="text-lg font-semibold mb-4">{{ $tagId ? 'Bewerken' : 'Nieuwe Tag' }}</h2>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Tag naam</label>
                <input type="text" wire:model="name" id="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">{{ $tagId ? 'Bijwerken' : 'Toevoegen' }}</button>
                <button type="button" wire:click="$set('showModal', false)" class="ml-2 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Annuleren</button>
            </div>
        </form>
    </div>
</div>

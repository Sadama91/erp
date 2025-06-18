@extends('layouts.app')

@section('page_title', 'Importeer Data')

@section('content')
<div class="container mx-auto my-8">
    <!-- Titel en instructies -->
    <div class="mb-4">
        <p class="text-gray-600">Kies een importtype en upload een CSV-bestand om gegevens te importeren.</p>
    </div>

    <!-- Formulier -->
    <div class="bg-white shadow rounded-lg p-6">
        <form action="#" method="POST" enctype="multipart/form-data" id="import-form">
            @csrf
            <!-- Importtype -->
            <div class="mb-4">
                <label for="import-type" class="block text-sm font-medium text-gray-700">Kies een importtype</label>
                <select id="import-type" name="type" class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mt-1 block w-full" required>
                    <option value="" disabled selected>Kies een optie...</option>
                    @foreach ($imports as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- CSV-bestand -->
            <div class="mb-4">
                <label for="csv-file" class="block text-sm font-medium text-gray-700">Selecteer CSV-bestand</label>
                <input type="file" id="csv-file" name="csv_file" class="form-control rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mt-1 block w-full" required>
            </div>

            <!-- Submitknop -->
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i class="fas fa-upload mr-2"></i> Importeren
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Dynamische formulieractie -->
<script>
    document.getElementById('import-type').addEventListener('change', function () {
        const form = document.getElementById('import-form');
        const selectedType = this.value;

        if (selectedType) {
            form.action = `{{ url('/import') }}/${selectedType}`;
        }
    });
</script>
@endsection

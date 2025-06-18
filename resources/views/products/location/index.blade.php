@extends('layouts.app')

@section('page_title', 'Beheer locaties van producten')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Locaties Beheren</h1>
    <button class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-500 transition duration-300" onclick="openModal()">Nieuwe Locatie</button>


    <div class="overflow-hidden shadow rounded-lg">
        <div class="overflow-hidden shadow rounded-lg">
            <table class="min-w-full bg-white">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 text-left">Naam</th>
                        <th class="px-4 py-2 text-left">Key</th>
                        <th class="px-4 py-2 text-left">Gekoppelde Producten</th>
                        <th class="px-4 py-2 text-left">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $location)
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border-b border-gray-200">{{ $location->name }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $location->value }}</td>
                            <td class="px-4 py-2 border-b border-gray-200">{{ $location->linkedProductsCount }}</td>
                            <td class="px-4 py-2 border-b border-gray-200 flex items-center">
                                <!-- Link naar productpagina met status 70 of lager -->
                                @if($location->lowStockProducts->isNotEmpty())
                                    <a href="{{ url('products?location[]=' . $location->value . '&search=&per_page=200') }}" title="Bekijk producten" class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="eye">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM12 10a2 2 0 100 4 2 2 0 000-4z"></path>
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-gray-400" title="Geen producten met status 70 of lager">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="eye-off">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.003 17.003A9.978 9.978 0 0012 16a9.978 9.978 0 00-5.003 1.003M4.218 4.218l15.566 15.566M4.218 4.218a9.978 9.978 0 00-.172 1.103c0 1.43.293 2.781.846 4.014M12 4c-2.211 0-4.212.9-5.684 2.344M16 8a9.978 9.978 0 012.966 1.557M12 12c0 .51-.013 1.008-.038 1.5"></path>
                                        </svg>
                                    </span>
                                @endif
                                
                                <!-- Bewerken Actie -->
                                <button class="ml-2 text-yellow-500" onclick="editLocation('{{ $location->id }}', '{{ $location->name }}', '{{ $location->value }}')" title="Bewerken">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="edit">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 7l-2-2m2 0h5a2 2 0 012 2v5m-2 0l-2-2M21 21l-6-6"></path>
                                    </svg>
                                </button>
        
                                <!-- Verwijder Actie -->
                                <form action="{{ route('locations.destroy', $location) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-red-500" title="Verwijderen">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-feather="trash-2">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12h.01M9 12h.01M21 4H8l-1 1H3v2h18V5h-2l-1-1z"></path>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </div>
    
</div>
<!-- Modal -->
<div id="locationModal" class="modal hidden">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h1 id="locationModalLabel" class="text-xl font-bold my-3">Locatie Toevoegen</h1>
        <form id="locationForm" action="{{ route('locations.store') }}" method="POST">
            @csrf
            <table>
                <tr>
                    <td><label for="name">Naam</label></td>
                    <td>
                        <input type="text" class="border-0 rounded-lg bg-gray-100 p-2" id="name" name="name" required>
                    </td>
                </tr>
                <tr>
                    <td><label for="value">Waarde</label></td>
                    <td>
                        <input type="text" class="border-0 rounded-lg bg-gray-100 my-2 p-2" id="value" name="value" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" id="location_id" name="location_id">
                        <input type="hidden" id="key" name="key" value="location">
                        <input type="hidden" name="_method" id="method"> <!-- Verborgen invoerveld voor de PUT-methode -->
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-500 transition duration-300">Opslaan</button>
                        <button type="button" class=" mx-5 bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-500 transition duration-300" onclick="closeModal()">Annuleren</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

@endsection

@section('style')
<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5); /* Grijze achtergrond */
    }
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Breedte van de modal */
        max-width: 500px; /* Maximale breedte */
        border-radius: 8px; /* Ronde hoeken */
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>

@endsection

@section('scripts')
<script>
    function openModal() {
        document.getElementById('locationModal').style.display = 'block';
        document.body.style.overflow = 'hidden'; // Voorkom scrollen op de achtergrond
    }

    function closeModal() {
        document.getElementById('locationModal').style.display = 'none';
        document.body.style.overflow = 'auto'; // Sta scrollen weer toe
        resetForm();
    }

    function resetForm() {
        document.getElementById('locationForm').reset();
        document.getElementById('location_id').value = '';
        document.getElementById('method').value = ''; // Reset de verborgen invoerveld voor de methode
        document.getElementById('locationModalLabel').innerText = 'Locatie Toevoegen';
    }

    function editLocation(id, name, value) {
        document.getElementById('locationForm').action = '/locations/' + id; // Zet de actie naar update
        document.getElementById('name').value = name;
        document.getElementById('value').value = value;
        document.getElementById('location_id').value = id;
        document.getElementById('method').value = 'PUT'; // Zet de methode naar PUT

        document.getElementById('locationModalLabel').innerText = 'Locatie Bewerken'; // Wijzig de modal titel
        openModal(); // Open de modal
    }
</script>
@endsection

@extends('layouts.app')

@section('page_title', 'Instellingen beheren')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-6">Instellingen beheren</h2>

    <!-- Globale foutmelding voor de lijst (indien van toepassing) -->
    <div id="listError" class="text-red-500 mb-4 hidden"></div>

    <div class="mb-6">
        <button id="addSettingBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Nieuwe Instelling Toevoegen</button>
    </div>

    <!-- Groepering van instellingen op basis van categorie -->
    <div id="settingsContainer">
        @foreach($settings->groupBy('category') as $category => $settingsInCategory)
            <h3 class="text-lg font-bold mb-4">{{ ucfirst($category) }} Instellingen</h3>
            <ul class="space-y-4">
                @foreach($settingsInCategory as $setting)
                    <li class="flex items-center justify-between">
                        <span>{{ $setting->key }}: {{ $setting->value ?? 'Niet ingesteld' }}</span>
                        
                        <!-- Actieve checkbox -->
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="toggleActive" data-id="{{ $setting->id }}" {{ $setting->active ? 'checked' : '' }} />
                            <span class="ml-2">{{ $setting->active ? 'Actief' : 'Inactief' }}</span>
                        </label>

                        <!-- Bewerken knop -->
                        <button class="editSettingBtn bg-yellow-500 text-white px-3 py-1 rounded" 
                            data-id="{{ $setting->id }}" 
                            data-key="{{ $setting->key }}" 
                            data-value="{{ $setting->value }}" 
                            data-active="{{ $setting->active }}"
                            data-category="{{ $setting->category }}">
                            Bewerken
                        </button>
                        
                        <!-- Logs knop -->
                        <button class="logsSettingBtn bg-blue-500 text-white px-3 py-1 rounded" 
                            data-id="{{ $setting->id }}">
                            Logs
                        </button>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>

    <!-- Modal voor toevoegen of bewerken van instellingen -->
    <div id="settingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
            <!-- Foutmelding boven de modal -->
            <div id="modalError" class="text-red-500 mb-4 hidden"></div>

            <h2 class="text-lg font-bold mb-4" id="modalTitle">Nieuwe Instelling</h2>
            <form id="settingForm">
                @csrf
                <input type="hidden" name="id" id="settingId">

                <!-- Model Selectie (bijv. Supplier, Invoice, etc.) -->
                <div class="mb-4">
                    <label for="category" class="block font-semibold mb-2">Model (Categorie)</label>
                    <select name="category" id="settingCategory" class="border p-2 rounded w-full" required>
                        @foreach($models as $model)
                            <option value="{{ strtolower($model) }}">{{ ucfirst($model) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sleutel van de instelling -->
                <div class="mb-4">
                    <label for="key" class="block font-semibold mb-2">Sleutel</label>
                    <input type="text" name="key" id="settingKey" class="border p-2 rounded w-full" required>
                </div>

                <!-- Waarde van de instelling -->
                <div class="mb-4">
                    <label for="value" class="block font-semibold mb-2">Waarde (tekst)</label>
                    <input type="text" name="value" id="settingValue" class="border p-2 rounded w-full" required>
                </div>

                <!-- Actief vinkje (toggle als checkbox) -->
                <div class="mb-4">
                    <label for="active" class="inline-flex items-center">
                        <input type="checkbox" name="active" id="settingActive" class="form-checkbox h-5 w-5 text-green-600" />
                        <span class="ml-2">Actief</span>
                    </label>
                </div>

                <!-- Eventuele veldspecifieke foutmelding voor "active" -->
                <div id="errorActive" class="text-red-500 mt-2 hidden">
                    <p>De waarde voor "Actief" moet waar of onwaar zijn.</p>
                </div>

                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Opslaan</button>
                <button type="button" id="cancelBtn" class="bg-red-600 text-white px-4 py-2 rounded ml-2">Annuleren</button>
            </form>
        </div>
    </div>

    <!-- Modal voor Activity Logs -->
    <div id="logsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3 relative">
            <!-- Sluitknop -->
            <button id="closeLogsBtn" class="absolute top-2 right-2 text-gray-500 text-xl">&times;</button>
            <h2 class="text-lg font-bold mb-4">Activity Logs</h2>
            <div id="logsContent" class="max-h-64 overflow-y-auto"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {

        // Functie om foutmelding in de lijst te tonen
        function showListError(message) {
            $('#listError').removeClass('hidden').html(message);
        }
        function clearListError() {
            $('#listError').addClass('hidden').html('');
        }

        // Functie om foutmelding in de modal te tonen
        function showModalError(message) {
            $('#modalError').removeClass('hidden').html(message);
        }
        function clearModalError() {
            $('#modalError').addClass('hidden').html('');
        }

        // Open de modal voor het toevoegen van een nieuwe instelling
        $('#addSettingBtn').on('click', function() {
            clearModalError();
            $('#settingModal').removeClass('hidden');
            $('#modalTitle').text('Nieuwe Instelling');
            $('#settingId').val('');
            $('#settingKey').val('').prop('disabled', false).css('background-color', '');
            $('#settingCategory').val('').prop('disabled', false).css('background-color', '');
            $('#settingValue').val('');
            $('#settingActive').prop('checked', false);
        });

        // Open de modal voor het bewerken van een instelling
        $('.editSettingBtn').on('click', function() {
            clearModalError();
            const settingId = $(this).data('id');
            const settingKey = $(this).data('key');
            const settingValue = $(this).data('value');
            const settingActive = $(this).data('active');
            const settingCategory = $(this).data('category');

            $('#settingModal').removeClass('hidden');
            $('#modalTitle').text('Instelling Bewerken');
            $('#settingId').val(settingId);
            // Disable de velden en geef een lichte grijze achtergrond
            $('#settingKey').val(settingKey).prop('disabled', true).css('background-color', '#f0f0f0');
            $('#settingCategory').val(settingCategory).prop('disabled', true).css('background-color', '#f0f0f0');
            $('#settingValue').val(settingValue);
            $('#settingActive').prop('checked', settingActive);
        });

        // Annuleer de modal
        $('#cancelBtn').on('click', function() {
            $('#settingModal').addClass('hidden');
        });

        // Verzend de form via AJAX
        $('#settingForm').on('submit', function(e) {
            e.preventDefault();
            clearModalError();
            clearListError();

            // Haal de waarde van de 'active' checkbox op
            const isActive = $('#settingActive').is(':checked');
            const formData = $(this).serialize() + '&active=' + isActive;
            const settingId = $('#settingId').val();
            let ajaxUrl = '';
            if (settingId) {
                // Bewerken van bestaande instelling
                ajaxUrl = '/settings/update/' + settingId;
            } else {
                // Nieuwe instelling toevoegen
                ajaxUrl = '/settings/store';
            }

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    let errorMsg = 'Er is een fout opgetreden.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            errorMsg = Object.values(xhr.responseJSON.errors)
                                .flat()
                                .join('<br>');
                        } else if (xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                    }
                    if ($('#settingModal').is(':visible')) {
                        showModalError(errorMsg);
                    } else {
                        showListError(errorMsg);
                    }
                }
            });
        });

        // Event handler voor het toggelen van de actieve status in de lijst
        $('.toggleActive').on('change', function() {
            clearListError();
            const settingId = $(this).data('id');
            const newActive = $(this).is(':checked');
            // Kies de juiste URL op basis van de nieuwe status
            const ajaxUrl = newActive ? '/settings/activate/' + settingId : '/settings/deactivate/' + settingId;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { active: newActive, _token: '{{ csrf_token() }}' },
                success: function(response) {
                    // Update het label naast de checkbox
                    $(this).next('span').text(newActive ? 'Actief' : 'Inactief');
                }.bind(this),
                error: function(xhr) {
                    let errorMsg = 'Er is een fout opgetreden bij het wijzigen van de status.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    showListError(errorMsg);
                    // Indien fout, zet checkbox terug naar vorige status
                    $(this).prop('checked', !newActive);
                }.bind(this)
            });
        });

        // Event handler voor het openen van de logs modal
       // Event handler voor het openen van de logs modal
        $('.logsSettingBtn').on('click', function() {
            const settingId = $(this).data('id');
            // Toon de logs modal en maak inhoud leeg
            $('#logsModal').removeClass('hidden');
            $('#logsContent').html('<p>Loading logs...</p>');
            
            $.ajax({
                url: '/settings/' + settingId +'/logs/',
                type: 'GET',
                success: function(response) {
                    let logsHtml = '';
                    if (response.logs && response.logs.length > 0) {
                        logsHtml += '<ul class="space-y-2">';
                        response.logs.forEach(function(log) {
                            // Formatteer datum en tijd
                            const date = new Date(log.created_at);
                            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                            
                            logsHtml += '<li class="border-b pb-2">';
                            logsHtml += '<strong>' + log.description + '</strong><br>';
                            logsHtml += '<small>' + formattedDate + '</small>';
                            
                            // Indien log-properties aanwezig zijn (bij "Instelling bijgewerkt")
                            if (log.description === 'Instelling bijgewerkt' && log.properties && log.properties.old !== undefined && log.properties.new !== undefined) {
                                logsHtml += '<div><em>Oud: ' + log.properties.old + ' | Nieuw: ' + log.properties.new + '</em></div>';
                            }
                            
                            logsHtml += '</li>';
                        });
                        logsHtml += '</ul>';
                    } else {
                        logsHtml = '<p>Geen logs gevonden.</p>';
                    }
                    $('#logsContent').html(logsHtml);
                },
                error: function(xhr) {
                    $('#logsContent').html('<p class="text-red-500">Fout bij het laden van logs.</p>');
                }
            });
        });

        // Sluit de logs modal
        $('#closeLogsBtn').on('click', function() {
            $('#logsModal').addClass('hidden');
        });
    });
</script>
@endsection

@extends('layouts.app')

@section('page_title', 'Leverancier bekijken: ' . $supplier->name)

@section('content')
<div class="max-w-5xl mx-auto p-4" x-data="{ tab: 'details' }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('suppliers.index') }}" class="flex items-center text-sm text-gray-600 hover:underline">
            <svg class="w-5 h-5 mr-1" data-feather="arrow-left"></svg> Terug
        </a>
        <h1 class="text-2xl font-bold">Leverancier: {{ $supplier->name }}</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b mb-4">
        <nav class="flex space-x-6 text-sm font-medium">
            <button @click="tab = 'details'" :class="tab === 'details' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="file-text"></svg> Details
            </button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-600'" class="pb-2 focus:outline-none">
                <svg class="w-4 h-4 inline mr-1" data-feather="activity"></svg> Logs
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div>
        {{-- Details Tab --}}
        <div x-show="tab === 'details'" x-cloak>
            <div class="bg-white shadow rounded p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p><span class="font-semibold">Naam:</span> {{ $supplier->name }}</p>
                        <p><span class="font-semibold">Status:</span> {{ $supplier->status ? 'Actief' : 'Inactief' }}</p>
                        <p><span class="font-semibold">Telefoon:</span> {{ $supplier->telephone }}</p>
                        <p>
                            <span class="font-semibold">Website:</span>
                            @if($supplier->website)
                                <a href="{{ $supplier->website }}" target="_blank" class="text-blue-600 hover:underline">{{ $supplier->website }}</a>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p><span class="font-semibold">Inkoop Via:</span> {{ $supplier->purchase_via }}</p>
                        <p><span class="font-semibold">Contact Info:</span> {{ $supplier->contact_info }}</p>
                        <p><span class="font-semibold">Betaaltermijn:</span> {{ $supplier->payment_days }} dagen</p>
                        <p><span class="font-semibold">Voorwaarden:</span> {{ $supplier->terms }}</p>
                        <p><span class="font-semibold">Opmerkingen:</span> {{ $supplier->remarks }}</p>
                        <p><span class="font-semibold">Laatste mutatie:</span> {{ $supplier->updated_at->format('d-m-Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 flex space-x-4">
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="flex items-center text-blue-600 hover:underline">
                    <svg class="w-5 h-5 mr-1" data-feather="edit"></svg> Bewerken
                </a>
                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Weet je zeker dat je deze leverancier wilt verwijderen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center text-red-600 hover:underline">
                        <svg class="w-5 h-5 mr-1" data-feather="trash-2"></svg> Verwijderen
                    </button>
                </form>
            </div>
        </div>

        {{-- Logs Tab --}}
  <div x-show="tab === 'logs'" x-cloak>
    <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-bold mb-4">Activity Logs</h2>
        <div class="space-y-4">
            @forelse($supplier->activities as $log)
                <div class="border-l-4 border-blue-500 pl-3 py-1 bg-blue-50">
                    <div>
                        <span class="font-semibold">{{ $log->created_at->format('d-m-Y H:i') }}</span> – 
                        {{ $log->description }}
                    </div>
                    <div class="text-gray-600">Door: {{ $log->causer->name ?? 'Onbekend' }}</div>

                    <!-- Weergave van alleen de gemuteerde velden -->
                    @if($log->properties)
                        @php
                            $properties = json_decode($log->properties, true);
                            $changes = [];

                            // Vergelijk 'old' en 'new' en haal de gemuteerde velden eruit
                            if (isset($properties['old']) && isset($properties['new'])) {
                                foreach ($properties['new'] as $key => $newValue) {
                                    $oldValue = $properties['old'][$key] ?? null;

                                    // Negeer 'created_at' en 'updated_at'
                                    if (in_array($key, ['created_at', 'updated_at'])) {
                                        continue;
                                    }

                                    if ($oldValue !== $newValue) {
                                        $changes[$key] = [
                                            'old' => $oldValue,
                                            'new' => $newValue
                                        ];
                                    }
                                }
                            }
                        @endphp
                        
                        @if(count($changes) > 0)
                            <div class="mt-2">
                                <strong>Wijzigingen:</strong>
                                <ul class="list-disc pl-5">
                                    @foreach($changes as $field => $change)
                                        <li>
                                            <strong>{{ ucfirst(str_replace('_', ' ', $field)) }}:</strong>
                                            <span class="text-gray-600">
                                                <strong>Oud:</strong> {{ $change['old'] ?? 'Niet beschikbaar' }}
                                                <strong>Nieuw:</strong> {{ $change['new'] }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>
            @empty
                <p class="text-gray-500">Geen logs beschikbaar.</p>
            @endforelse
        </div>
    </div>
</div>



    </div>
</div>
@endsection

@section('scripts')
<script>
    feather.replace();
</script>
@endsection

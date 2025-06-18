@extends('layouts.app')

@section('page_title', 'Parameters voor Key: ' . $key)
@section('content')
<div class="container">
    {{-- Header met knoppen --}}
    <div class="flex space-x-4 mb-4">
        <a href="{{ route('parameters.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-400">
            &larr; Terug
        </a>
        <!-- Nieuwe Waarde Toevoegen -->
        <a href="{{ route('parameters.create') }}" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-blue-400">
            <i data-feather="plus" class="w-5 h-5"></i> Nieuwe Waarde
        </a>
    </div>
    <table class="table table-striped w-full border border-gray-300">
        <thead>
            <tr class="bg-gray-100 text-gray-600">
                <th class="px-4 py-2 border-b">Naam</th>
                <th class="px-4 py-2 border-b">Waarde</th>
                <th class="px-4 py-2 border-b">Acties</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parameters as $parameter)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border-b">{{ $parameter->name }}</td>
                <td class="px-4 py-2 border-b">{{ $parameter->value }}</td>
                <td class="px-4 py-2 border-b flex items-center gap-2">
                    <!-- Bewerken -->
                    <a href="{{ route('parameters.edit', $parameter->id) }}" class="text-blue-500 hover:text-blue-700">
                        <i data-feather="edit" class="w-5 h-5"></i>
                    </a>
                    <!-- Verwijderen -->
                    <form action="{{ route('parameters.destroy', $parameter->id) }}" method="POST" onsubmit="return confirm('Weet je zeker dat je deze waarde wilt verwijderen?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700">
                            <i data-feather="trash-2" class="w-5 h-5"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>


</div>
@endsection

@push('scripts')
<script>
    // Initialiseer Feather-icons
    feather.replace();
</script>
@endpush

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Widget-instellingen</h1>
    
    <form id="widget-settings-form">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($widgets as $widgetName => $widget)
                <div class="p-4 border rounded bg-gray-100">
                    <h2 class="text-lg font-bold">{{ $widgetName }}</h2>
                    
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="widgets[{{ $widgetName }}][active]" 
                               value="1" {{ $widget['active'] ? 'checked' : '' }}>
                        <span>Actief</span>
                    </label>
                    
                    <input type="hidden" name="widgets[{{ $widgetName }}][position]" value="{{ $widget['position'] }}">
                </div>
            @endforeach
        </div>

        <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Opslaan</button>
    </form>
</div>

<script>
document.getElementById('widget-settings-form').addEventListener('submit', function(event) {
    event.preventDefault();

    fetch('{{ route("widgets.update") }}', {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value }
    }).then(response => response.json()).then(data => {
        if (data.success) {
            alert('Instellingen opgeslagen!');
            location.reload();
        }
    });
});
</script>
@endsection

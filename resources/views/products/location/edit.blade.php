@extends('layouts.app')

@section('page_title', 'Bewerk locaties van pruducten: ' . $location->name)


@section('content')
<div class="container">
    <h1>Locatie Bewerken</h1>
    <form action="{{ route('locations.update', $location) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Naam</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $location->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Bijwerken</button>
    </form>
</div>
@endsection


@extends('layouts.app')

@section('page_title', 'Maak nieuwe opslag locatie aan voor pruducten')

@section('content')
<div class="container">
    <h1>Nieuwe Locatie</h1>
    <form action="{{ route('locations.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Naam</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="value" class="form-label">Waarde</label>
            <input type="text" class="form-control" id="value" name="value" required>
        </div>
        <button type="submit" class="btn btn-primary">Opslaan</button>
    </form>
</div>
@endsection


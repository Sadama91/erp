@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Merk Details</h1>
    @livewire('brand-show', ['brand' => $brand])
</div>
@endsection

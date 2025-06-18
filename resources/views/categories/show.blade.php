@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Categorie Details</h1>
    @livewire('category-show', ['category' => $category])
</div>
@endsection
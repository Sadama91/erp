@extends('layouts.app')
@section('page_title', 'Subgroep').
@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Subgroep Details</h1>
    @livewire('subgroup-show', ['subgroup' => $subgroup])
</div>
@endsection

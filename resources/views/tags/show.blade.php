@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Tag Details</h1>
    @livewire('tag-show', ['tag' => $tag])
</div>
@endsection

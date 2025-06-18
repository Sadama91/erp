@extends('layouts.app')
@section('page_title', 'Dashboard')

@section('content')<div class="container mx-auto px-4">
    <div class="grid grid-cols-3 gap-4">
        {!! $widgetsHtml !!}
    </div>
</div>
@endsection
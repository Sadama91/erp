@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 text-2xl font-bold">Parameters</h1>

    <table class="table table-striped w-full border border-gray-300">
        <thead>
            <tr class="bg-gray-100 text-gray-600">
                <th class="px-4 py-2 border-b">Naam</th>
                <th class="px-4 py-2 border-b">Waarden</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parameters as $key => $values)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border-b">
                    <a href="{{ route('parameters.show', $key) }}" class="text-blue-600 hover:underline">{{ $values->first()->key }}</a>
                </td>
                <td class="px-4 py-2 border-b">
                    @foreach ($values as $parameter)
                        {{ $parameter->value }} @if (!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('parameters.create') }}" class="btn btn-primary mt-4 px-3 py-1 bg-indigo-600 text-white rounded-md">Nieuwe Waarde Toevoegen</a>
</div>
@endsection

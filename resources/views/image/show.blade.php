@extends('layouts.app')

@section('page_title', 'Afbeelding Details: ' . $image->id)

@section('content')
    <div class="container">

    <div class="mb-4">
            <h4>Gelinkte Producten:</h4>
            <ul>
                @foreach ($image->imageLinks as $link)
                    <li>
                        <a href="{{ route('products.show', $link->product->id) }}" target="_blank">
                            SKU: {{ $link->product->sku }} - {{ $link->role }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <!-- Afbeelding en gelinkte producten -->
        <div class="mb-4">
            <h4>Afbeelding:</h4>
            <a href="{{ $image->location }}" target="_blank">
              
            <img src="{{ $image->location }}" alt="Afbeelding" style="max-width: 100%; cursor: zoom-in;">
     </a>     </div>


        <!-- Terugknop naar de overzichtspagina van afbeeldingen -->
     <!-- Terugknop naar de vorige pagina -->
<a href="{{ url()->previous() }}" class="btn btn-secondary btn btn-primary bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-yellow-400 transition duration-300">
    Terug naar vorige pagina
</a>    </div>
@endsection

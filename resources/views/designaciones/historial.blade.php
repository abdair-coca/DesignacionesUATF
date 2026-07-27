@extends('app')
@section('content')
<h1>Historial</h1>
<p>{{ $designacion->id }}</p>
@foreach($historial as $h)
    <p>{{ $h->campo }}</p>
@endforeach
@endsection

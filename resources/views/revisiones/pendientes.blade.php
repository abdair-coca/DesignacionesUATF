@extends('app')
@section('content')
<h1>Revisiones Pendientes</h1>
@foreach($pendientes as $p)
    <p>{{ $p['id'] }}</p>
@endforeach
@endsection

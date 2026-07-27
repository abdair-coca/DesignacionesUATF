@extends('app')
@section('content')
<h1>Carreras</h1>
@foreach($carreras as $c)
    <p>{{ $c->nombre }}</p>
@endforeach
@endsection

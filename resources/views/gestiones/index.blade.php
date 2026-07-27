@extends('app')
@section('content')
<h1>Gestiones</h1>
@foreach($gestiones as $g)
    <p>{{ $g->nombre }}</p>
@endforeach
@endsection

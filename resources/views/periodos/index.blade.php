@extends('app')
@section('content')
<h1>Periodos</h1>
@foreach($periodos as $p)
    <p>{{ $p->nombre }}</p>
@endforeach
@endsection

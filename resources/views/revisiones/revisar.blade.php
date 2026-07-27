@extends('app')
@section('content')
<h1>Revisar</h1>
<p>{{ $revision['carrera_nombre'] }}</p>
@foreach($designaciones as $d)
    <p>{{ $d['docente_nombre'] }}</p>
@endforeach
@endsection

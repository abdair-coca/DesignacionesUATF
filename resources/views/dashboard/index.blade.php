@extends('app')
@section('content')
<h1>Dashboard</h1>
@isset($gestiones)
    <p>Gestiones: {{ $gestiones->count() }}</p>
@endisset
@isset($periodos)
    <p>Periodos: {{ $periodos->count() }}</p>
@endisset
@isset($resumenCarreras)
    <p>Carreras: {{ $resumenCarreras->count() }}</p>
@endisset
@endsection

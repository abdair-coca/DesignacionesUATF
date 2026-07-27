@extends('app')
@section('content')
<h1>Editar Materia</h1>
<form>@csrf @method('PUT')</form>
@endsection

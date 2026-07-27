@extends('app')
@section('content')
<h1>Editar Grupo</h1>
<form>@csrf @method('PUT')</form>
@endsection

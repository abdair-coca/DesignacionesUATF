@extends('layouts.app')

@section('title', 'Editar designación')

@section('content')
    <form action="{{ route('designaciones.update', $designacion) }}" method="POST">
        @csrf
        @method('PUT')
        @include('designaciones._form')
    </form>
@endsection

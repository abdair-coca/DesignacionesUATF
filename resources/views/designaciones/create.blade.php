@extends('layouts.app')

@section('title', 'Nueva designación')

@section('content')
    <form action="{{ route('designaciones.store') }}" method="POST">
        @csrf
        @include('designaciones._form')
    </form>
@endsection

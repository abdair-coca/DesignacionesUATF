@extends('app')
@section('content')
<h1>Lista de Designaciones</h1>
@foreach($designaciones as $d)
    <p>{{ $d->id }}</p>
@endforeach
{{ $designaciones->links() }}
@endsection

@extends('app')
@section('content')
<h1>Materias</h1>
@foreach($materias as $m)
    <p>{{ $m->nombre }}</p>
@endforeach
{{ $materias->links() }}
@endsection

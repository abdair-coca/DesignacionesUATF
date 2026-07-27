@extends('app')
@section('content')
<h1>Grupos</h1>
@foreach($grupos as $g)
    <p>{{ $g->codigo }}</p>
@endforeach
{{ $grupos->links() }}
@endsection

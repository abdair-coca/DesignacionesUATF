@extends('app')
@section('content')
<h1>Docentes</h1>
@foreach($docentes as $d)
    <p>{{ $d->nombre }}</p>
@endforeach
{{ $docentes->links() }}
@endsection

@extends('app')
@section('content')
<form method="POST" action="/login">
    @csrf
    <input type="email" name="email" placeholder="Email">
    <input type="password" name="password" placeholder="Password">
    <button type="submit">Ingresar</button>
</form>
@if($errors->any())
    <p>{{ $errors->first() }}</p>
@endif
@endsection

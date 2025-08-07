@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Welcome Artist {{ Auth::user()->name }}</h1>
</div>
@endsection

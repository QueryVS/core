@extends('layouts.app')
@section('content')
<nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{server()->name}}</li>
        </ol>
    </nav>
<link href="{{ asset("js/app.js") }}" rel=preload as=script><noscript><strong>We're sorry but this extension doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript><div locale="{{ app()->getLocale() }}" id=app></div><script src="{{ asset("js/app.js") }}"></script>
@endsection
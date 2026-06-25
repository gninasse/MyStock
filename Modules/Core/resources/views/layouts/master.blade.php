@extends('layouts.lumiere')

@section('title')
    @yield('title', 'CHU-YO-KEYSTONE')
@endsection

@section('breadcrumb')
    @yield('breadcrumb')
@endsection

@section('content')
    @yield('content')
@endsection

@push('styles')
    @stack('css')
@endpush

@push('scripts')
    @stack('js')
@endpush
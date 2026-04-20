@extends('student.layouts.app')
@section('title', ($city['name'] ?? 'Şehir') . ' — Almanya\'da Yaşam')
@section('page_title', ($city['name'] ?? '') . ' Rehberi')

@section('content')
    @include('partials.city-detail-content')
@endsection

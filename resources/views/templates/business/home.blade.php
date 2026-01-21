@extends('templates.base.layout')

@section('content')
    @include('templates.base.components.header')
    
    @include('templates.base.components.hero')
    
    @include('templates.base.components.about')
    
    @include('templates.base.components.services')
    
    @include('templates.base.components.features')
    
    @include('templates.base.components.testimonials')
    
    @include('templates.base.components.faq')
    
    @include('templates.base.components.cta')
    
    @include('templates.base.components.contacts')
    
    @include('templates.base.components.footer')
@endsection

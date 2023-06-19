@extends('layouts.app', ['page_class' => 'master-wrap common-popup cart-items-page font1'])
@section('meta')
    <!-- Search Engine -->
    <title>@yield('title')</title>
    <!-- Search Engine -->
    <meta name="description" content="We bring the best and Selected Creative Goods for accelerating your creativity and productivity. We will add Best free Creative Goods Here, which Collected from leading Creative Marketplaces">
    <meta name="image" content="{{asset('images/cg-meta-logo.png')}}">
    <meta name="author"  content="{{ env('APP_NAME') }}">
    <meta name="copyright"  content="{{ env('APP_NAME') }}">
    <meta name="keywords" content="creativegoods, creative marketplace, premium creative goods, wordpress themes, premium fonts, free fonts, vectors for craft works,  premium templates, bootstrap, Webhance Studio network, Webhance Studio inc, html5, web devlopment, jquery animations, css3, jQuery, parallax, minimalist website, interactive html5, animated html5 websites, web design india, creativegoods.net, premium web development," />
    <!-- Schema.org for Google -->
    <meta  content="{{ env('APP_NAME') }}">
    <meta content="We bring the best and Selected Creative Goods for accelerating your creativity and productivity.">
    <meta content="{{asset('images/cg-meta-banner.png')}}">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ env('APP_NAME') }}">
    <meta name="twitter:description" content="We bring the best and Selected Creative Goods for accelerating your creativity and productivity.">
    <meta name="twitter:site" content="@{{ env('APP_NAME') }}">
    <meta name="twitter:creator" content="@{{ env('APP_NAME') }}">
    <meta name="twitter:image:src" content="{{asset('images/cg-meta-banner.png')}}">
    <!-- Open Graph general (Facebook, Pinterest & Google+) -->
   <meta name="og:site_name" content="{{ env('APP_NAME') }}">
    <meta name="og:description" content="We bring the best and Selected Creative Goods for accelerating your creativity and productivity.">
    <meta name="og:image" content="{{asset('images/cg-meta-banner.png')}}">
    <meta name="og:url" content="{{ url('/') }}">
    <meta name="og:site_name" content="{{ env('APP_NAME') }}">
    <meta name="fb:admins" content="814789562597194">
    <meta name="og:type" content="website">
@endsection


@section('content')
    <div class="cg-404-wrap">

        <div class="cg-404-content-wrap text-center pad-btm-sml">
            <h3 class="font1 second w600">  @yield('code') </h3>
            <h4 class="font1 grey w600"> @yield('message')</h4>
            <div class="cg-404-img-wrap text-center pad-top-sml pad-btm-sml">
                <img src="{{asset('/')}}@yield('image')" class="img-fluid" alt="creativegoods-404"/>
            </div>
            <a href="{{ url('/') }}" class="first">Back to Home</a>
        </div>


    </div>

@endsection


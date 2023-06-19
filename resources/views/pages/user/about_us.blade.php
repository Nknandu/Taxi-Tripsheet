@extends('layouts.user.user_app', ['body_class' => 'about-page'])
@section('title', 'About Us')
@section('content')
<!-- INTERMEDIATE --------------------------------------------------------------------------------------->
<section class="about-mini-nav white-bg font1 pt-8">
    <div class="container">
        <div class="row">
            <ul class="d-flex align-items-start justify-content-center">
                <li><a class="px-active">About</a></li>
                <li><a>Career</a></li>
                <li><a>Our team</a></li>
            </ul>
        </div>
    </div>
</section>
<section class="about-banner white-bg">
    <div class="containser">
        <div class="row">
            <img class="img-fluid" src="{{ asset('assets/user/images/about-1.jpg') }}" alt="projectX">
        </div>
    </div>
</section>

<!-- LANDING-THREE --------------------------------------------------------------------------------------->

<section class="white-bg pt-10 pb-6 third-landing">
    <div class="container">
        <div class="row">
            <div class="landing-03-contents">
                <h2 class="font1 black">About ProjectX</h2>
                <h1 class="font1 black">Falling in love with ProjectX is so natural.</h1>
            </div>
        </div>
        <!-- FEATURES -->
        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-12 px-feature-box pb-4 font1">
                <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. </p>
            </div>
        </div>
        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>WordPress</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>Elementor</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>Designova</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
        </div>
        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>Free WordPress Plugin</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>100+ Elementor Kits</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
            <div class="col-md-3 px-feature-box pb-5 font1">
                <h3>100+ Elementor templates</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>
        </div>
    </div>
</section>


<!-- INTERMEDIATE --------------------------------------------------------------------------------------->


<section class="px-intermediate font1">
    <div class="px-overlay d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="about-end-inter pt-10 pb-6">
                <h2 class="font1 white">About ProjectX</h2>
                <h1 class="font1 white">Falling in love with ProjectX is so natural.</h1>
                <p class="font1 white pb-4 pt-4">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
                <button type="button" class="btn btn-primary border-l-btn">Get Started</button>
            </div>
        </div>
    </div>
</section>
@endsection

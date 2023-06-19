@extends('layouts.user.user_app', ['body_class' => ''])
@section('title', 'Home')
@section('content')
<section class="thseme-bg px-landing-one d-flex align-items-end justify-content-center mt-5">
    <div class="container text-center black">
        <h1 class="font1 black">Create Websites, Design Your Future</h1>
        <p class="pt-4 pb-4 black">Join our community of millions of web creators who build websites and grow together, with the #1 web creation platform for WordPress.</p>
        <button type="button" class="btn btn-primary black-btn">Free Download</button>
    </div>
</section>



<!-- Banner --------------------------------------------------------------------------------------->

<section class="prime-banner">
    <img class="img-fluid" src="{{ asset('assets/user/images/landing07.png') }}" alt="projectX">
</section>


<!-- LANDING-TWO --------------------------------------------------------------------------------------->


<section class="white-bg pt-14 pb-14 third-landing">
    <div class="container">
        <div class="row">
            <div class="landing-03-contents">
                <h2 class="font1 black">Create a Website in a click</h2>
                <h1 class="font1 black">Easily add personalized videos to your HubSpot emails, snippets, sequences</h1>
            </div>
            <!-- <span class="v-spacer"></span> -->
        </div>
        <!-- FEATURES -->
        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h2 class="step-count d-flex align-items-center justify-content-center"><span>1</span></h2>
                <h3>Install WordPress</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h2 class="step-count d-flex align-items-center justify-content-center"><span>2</span></h2>
                <h3>Install Plugin</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h2 class="step-count d-flex align-items-center justify-content-center"><span>3</span></h2>
                <h3>Enjoy!</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

        </div>


        <div class="row px-button-group mt-6 d-flex align-items-center justify-content-start">
            <button type="button" class="btn-primary black-btn">Get Started</button>
            <button type="button" class="ml-20p btn-primary border-btn">I have a Question</button>
        </div>


    </div>
</section>

<!-- INTERMEDIATE --------------------------------------------------------------------------------------->

<section class="px-intermediate-2 font1"></section>




<!-- LANDING-THREE --------------------------------------------------------------------------------------->


<section class="white-bg pt-14 pb-14 third-landing">
    <div class="container">
        <div class="row">
            <div class="landing-03-contents">
                <h2 class="font1 black">Create a Website in a click</h2>
                <h1 class="font1 black">Easily add personalized videos to your HubSpot emails, snippets, sequences</h1>
            </div>
            <!-- <span class="v-spacer"></span> -->
        </div>
        <!-- FEATURES -->
        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>Free WordPress Plugin</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>100+ Elementor Kits</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>100+ Elementor templates</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

        </div>

        <div class="row d-flex align-items-start justify-content-between">
            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>100+ Premium Fonts</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>100+ Elementor Widgets</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

            <div class="col-md-3 px-feature-box pb-5 pt-5 font1">
                <h3>100+ Ready Made Websites</h3>
                <p>Easily add personalized videos to your HubSpot emails, snippets, sequences</p>
            </div>

        </div>
        <div class="row px-button-group mt-6 d-flex align-items-center justify-content-start">
            <button type="button" class="btn-primary black-btn">Get Started</button>
            <button type="button" class="ml-20p btn-primary border-btn">I have a Question</button>
        </div>


    </div>
</section>



<!-- INTERMEDIATE --------------------------------------------------------------------------------------->

<section class="px-intermediate font1"></section>
@endsection

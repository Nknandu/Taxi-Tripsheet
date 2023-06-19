@extends('layouts.user.user_app', ['body_class' => 'kit-page'])
@section('title', $kit_library->title)
@section('content')
    <!-- INTERMEDIATE --------------------------------------------------------------------------------------->

    <section class="design-store-banner">

        <div class="common-overlay-mini d-flex align-items-start justify-content-center flex-column">
            <div class="container">
                <h1 class="design-banner-text font1 text-left">{{ $kit_library->title }}</h1>
                <ul class="kit-tags font1 mt-4">
                    @foreach(explode(", ", $kit_library->getTagNames()) as $tag)
                    <li class="item-type"><a>{{ ltrim($tag) }}</a></li>
                    @endforeach
                </ul>
                <div class="design-store-category btn-group btn-group-toggle" data-toggle="buttons">
                    <a href="{{ $kit_library->preview_link }}" target="_blank" class="btn btn-secondary active">Preview Design</a>
                    <a href="#" class="btn btn-secondary">Use Design</a>
                </div>
            </div>
        </div>

    </section>
<section class="white-bg pt-12 pb-8 designs-landing">
    <div class="container">

        <div class="kit-preview-image-box">
            <div class="browser-style">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#ff5552" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-circle"><circle cx="12" cy="12" r="10"></circle></svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#ffb43e" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-circle"><circle cx="12" cy="12" r="10"></circle></svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#00c144" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-circle"><circle cx="12" cy="12" r="10"></circle></svg>
            </div>
            <img class="img-fluid kit-preview-image-box-image" src="{{ $kit_library->getOriginalImage() }}" alt="projectX">
        </div>

    </div>
</section>


<!-- Kit Details --------------------------------------------------------------------------------------->

<section class="kit-library-details white-bg pb-10">
    <div class="container">
        <div class="row font1">
            <h2>About this Kit</h2>
           {!! $kit_library->description !!}
        </div>

        @if($kit_library->kit_library_features->count())
        <div class="row font1"><h3>Features Overview</h3></div>
        <div class="row font1">
            @foreach($kit_library->kit_library_features as $kit_library_feature)
            <div class="kit-library-spec col-md-4 d-flex align-items-start justify-content-start">
                <div class="kit-spec-icon d-flex align-items-center justify-content-center">
                    {!! $kit_library_feature->icon !!}
                </div>
                <div class="">
                    <h2>{{ $kit_library_feature->title }}</h2>
                    <p>{{ $kit_library_feature->description }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>

@if($similar_kit_libraries->count())
<section class="px-intermediate related-kits-wrap font1">
    <div class="px-overlay d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="about-end-inter">
                <h2 class="font1 white">Designs like this</h2>
                <div class="d-flex align-items-center justify-content-center">
                    @foreach($similar_kit_libraries as $similar_kit_library)
                    <div class="col mb-4">
                        <div class="card">
                            <a href="{{ url('kit-library/'.$similar_kit_library->slug) }}" class="designs-image-wrap"><img src="{{ $similar_kit_library->getThumbImage() }}" class="card-img-top" alt="projectX"></a>
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <h5 class="font1 card-title">{{ $similar_kit_library->title }}</h5>
                                <a class="d-flex align-items-center justify-content-center" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-up-right"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg></a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <a href="{{url('kit-libraries')}}" class="btn btn-primary border-l-btn">View All</a>
            </div>
        </div>
    </div>
</section>
@endif
@endsection

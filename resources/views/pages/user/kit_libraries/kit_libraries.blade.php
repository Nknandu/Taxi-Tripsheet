@extends('layouts.user.user_app', ['body_class' => 'designs-page'])
@section('title', 'Kit Library')
@section('content')
<section class="design-store-banner">
    <div class="common-overlay-mini d-flex align-items-center justify-content-center flex-column">
        <h1 class="design-banner-text font1 text-center">Explore Inspiring Websites<br>Built With ProjectX</h1>
        <h5 class="font1 white mt-4">Select your plan to see Designs</h5>
        <div class="design-store-category btn-group btn-group-toggle" data-toggle="buttons">
            @foreach($packages as $key => $package)
                <label class="btn btn-secondary active"><input type="radio" name="options" id="{{ $package->id }}"  @if($key == 0) checked @endif> {{ $package->title }}</label>
            @endforeach
        </div>
    </div>
</section>
<section class="about-mini-nav white-bg font1 pt-6">
    <div class="">
        <ul class="d-flex align-items-start justify-content-center">
            <li><a class="px-active">All</a></li>
            @foreach($categories as $category)
                <li><a href="#">{{ $category->title }}</a></li>
            @endforeach
        </ul>
    </div>
</section>
<section class="white-bg pt-6 pb-6 designs-landing" id="kitLibrariesSection">
    @include('pages.user.kit_libraries.kit_libraries_ajax')
</section>


<!-- INTERMEDIATE --------------------------------------------------------------------------------------->


<section class="px-intermediate font1">
    <div class="px-overlay d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="about-end-inter">
                <h2 class="font1 white">About ProjectX</h2>
                <h1 class="font1 white">Falling in love with ProjectX is so natural.</h1>
                <p class="font1 white pb-4 pt-4">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>
                <button type="button" class="btn btn-primary border-l-btn">Get Started</button>
            </div>
        </div>
    </div>
</section>

@endsection
@push('footer-scripts')
    <script type="text/javascript">
        $(window).on('hashchange', function() {
            if (window.location.hash) {
                var currentPage = window.location.hash.replace('#', '');
                if (currentPage == Number.NaN || currentPage <= 0) {
                    return false;
                }else{
                    getKitLibraries(currentPage);
                }
            }
        });
        $(document).ready(function()
        {
            $(document).on('click', '.pagination li a',function(event)
            {
                $('li').removeClass('active');
                $(this).parent('li').addClass('active');
                event.preventDefault();
                var currentPage=$(this).attr('href').split('currentPage=')[1];
                getKitLibraries(currentPage);
            });
        });
        function getKitLibraries(currentPage)
        {
            $.ajax(
                {
                    url: '?page=' + currentPage,
                    type: "get",
                    datatype: "html",
                })
                .done(function(data)
                {
                    $("#kitLibrariesSection").empty().html(data);
                    location.hash = currentPage;
                })
                .fail(function(jqXHR, ajaxOptions, thrownError)
                {
                    alert('Sorry, No Any response from server side');
                });
        }
    </script>
@endpush

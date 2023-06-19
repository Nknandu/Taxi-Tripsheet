<section class="px-primary-nav black-bg d-flex align-items-center">
    <div class="container-fluid all-zero">
        <div class="row">
            <div class="col-md-1 d-flex align-items-center justify-content-end">
                <a href="{{ url('/') }}"><img class="img-fluid px-logo" src="{{ asset('assets/user/images/logo.png') }}" alt="projectX"></a>
            </div>
            <div class="page-menu-ul d-flex justify-content-start align-items-center col-md-4">
                <ul>
                    <li><a href="{{ url('/about_us') }}">About</a></li>
                    <li><a href="{{ url('/pricing') }}">Pricing</a></li>
                    <li><a href="{{ url('/kit-libraries') }}">Kit Libraries</a></li>
                </ul>
            </div>
            <div class="prime-menu-ul d-flex justify-content-end align-items-center col-md-7">
                <ul><li><a href="{{ url('/sign-in') }}">Login</a></li></ul>
                <button type="button" class="btn btn-primary white-btn">Get Started</button>
            </div>
        </div>
    </div>
</section>

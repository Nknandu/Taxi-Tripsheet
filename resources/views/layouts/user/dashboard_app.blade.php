<!DOCTYPE html>
<html lang="en">
@include('layouts.user.partials.head')
<body  class="body-bg {{ ($body_class)?$body_class:'' }}">
@include('layouts.user.partials.dashboard_nav_bar')
<div class="text-center" id="customAjaxLoader">
    <div class="spinner-wrap">
        <div class="loadingspinner"></div>
    </div>
</div>
@yield('content')
<!-- FOOTER --------------------------------------------------------------------------------------->
@include('layouts.user.partials.footer')
<!-- PAGE ELEMENTS ENDS HERE -->
<!-- Bootstrap-addons -->
<script
    src="https://code.jquery.com/jquery-3.7.0.min.js"
    integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
<script src="{{ asset('assets/user/javascripts/focal.js') }}"></script>
<!-- ○══════════════════════════════════○ -->
@stack('footer-scripts')
</body>
</html>

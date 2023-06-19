@include('layouts.admin.partials.admin_head')
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="app-blank app-blank bgi-size-cover bgi-position-center bgi-no-repeat">
<!--begin::Theme mode setup on page load-->
<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-theme-mode")) { themeMode = document.documentElement.getAttribute("data-theme-mode"); } else { if ( localStorage.getItem("data-theme") !== null ) { themeMode = localStorage.getItem("data-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-theme", themeMode); }</script>
<!--end::Theme mode setup on page load-->
<!--begin::Root-->

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Page bg image-->
        <style>body { background-image: url('{{ asset('assets/media/auth/bg1.jpg') }}'); } [data-theme="dark"] body { background-image: url('{{ asset('assets/media/auth/bg1-dark.jpg') }}'); }</style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Signup Welcome Message -->
        <div class="d-flex flex-column flex-center flex-column-fluid">
            <!--begin::Content-->
            <div class="d-flex flex-column flex-center text-center p-10">
                <!--begin::Wrapper-->
                <div class="card card-flush w-lg-650px py-5">
                    <div class="card-body py-15 py-lg-20">
                        <!--begin::Title-->
                        <h1 class="fw-bolder fs-2hx text-gray-900 mb-4">{{ __('messages.oops') }}</h1>
                        <!--end::Title-->
                        <!--begin::Text-->
                        <div class="fw-semibold fs-6 text-gray-500 mb-7">@yield('message')</div>
                        <!--end::Text-->
                        <!--begin::Illustration-->
                        <div class="mb-3">
                            <img src="{{asset('/')}}@yield('image')" class="mw-100 mh-300px theme-light-show" alt="" />
                            <img src="{{asset('/')}}@yield('image_dark')" class="mw-100 mh-300px theme-dark-show" alt="" />
                        </div>
                        <!--end::Illustration-->
                        <!--begin::Link-->
                        <div class="mb-0">
                            @if(Request::segment(1) == 'admin')
                               <a href="{{ url('/admin/dashboard') }}" class="btn btn-sm btn-primary">{{ __('messages.home') }}</a>
                            @else
                                <a href="{{ url('/') }}" class="btn btn-sm btn-primary">{{ __('messages.home') }}</a>
                            @endif
                        </div>
                        <!--end::Link-->
                    </div>
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Authentication - Signup Welcome Message-->
    </div>
    <!--end::Root-->

<!--begin::Javascript-->
<script>var hostUrl = "assets/";</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<!--end::Global Javascript Bundle-->
<!--end::Javascript-->
</body>
<!--end::Body-->
</html>

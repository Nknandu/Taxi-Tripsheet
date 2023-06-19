<!DOCTYPE html>
@if(session()->get('locale') == 'ar')
    <html  lang="{{ str_replace('_', '-', app()->getLocale()) }}" direction="rtl" dir="rtl" style="direction: rtl">
@else
    <html  lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
@endif
<!--begin::Head-->
<head><base href=""/>
    <title>{{ env('APP_NAME') }} | @yield('title')</title>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Beiaat Admin" />
    <meta name="keywords" content="Beiaat Admin" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Beiaat Admin" />
    <meta property="og:url" content="{{ env('APP_URL') }}" />
    <meta property="og:site_name" content="Beiaat Admin" />
    <link rel="canonical" href="Beiaat Admin" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/fav_1.png') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    @if(session()->get('locale') == 'ar')
        <link href="{{ asset('assets/plugins/global/plugins.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
    @else
        <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link href="{{ asset('assets/css/admin_style.css') }}" rel="stylesheet" type="text/css" />
    <script type="text/javascript">
        let time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        document.cookie = 'time_zone='+time_zone;
    </script>
@stack('head-scripts')
<!--end::Global Stylesheets Bundle-->
</head>

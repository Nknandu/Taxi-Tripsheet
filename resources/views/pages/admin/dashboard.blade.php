@extends('layouts.admin.admin_app', ['body_class' => ''])
@section('title', 'Dashboard')
@push('breadcrumbs')
    <!--begin::Item-->
    <li class="breadcrumb-item active"><a href="{{ url('admin') }}" class="text-muted text-hover-primary">Dashboard</a></li>
    <!--end::Item-->
@endpush
@php
    if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
@endphp
@section('content')
    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <!--end::Content container-->
    </div>
    <!--end::Content-->
    @push('head-scripts')
        <!--begin::Vendor Stylesheets(used for this page only)-->
        <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.css') }}" rel="stylesheet" type="text/css" />
        <!--end::Vendor Stylesheets-->
    @endpush

    @push('footer-scripts')
        <!--begin::Custom Javascript(used for this page only)-->
        <!--begin::Vendors Javascript(used for this page only)-->
        <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script src="{{ asset('assets/plugins/custom/vis-timeline/vis-timeline.bundle.js') }}"></script>
        <!--end::Vendors Javascript-->
    @endpush
@endsection

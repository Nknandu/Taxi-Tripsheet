@extends('layouts.admin.admin_app', ['body_class' => ''])
@section('title', 'Kit Libraries')
@push('breadcrumbs')
    <!--begin::Item-->
    <li class="breadcrumb-item active"><a href="" class="text-muted text-hover-primary">Kit Libraries</a></li>
    <!--end::Item-->
@endpush
@section('content')
    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12 col-lg-12 col-xl-12 col-xxl-12 mb-md-5 mb-xl-10">
                  <div class="card">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>Kit Library Lists</h2>
                        </div>
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                                <a class="btn btn-icon btn-active-light-primary w-30px h-30px"  href="{{ route('admin.kit_libraries.create') }}" >
                                    <!--begin::Svg Icon | path: icons/duotune/general/gen027.svg-->
                                    <span class="svg-icon svg-icon-3">
                                      <i class="bi bi-plus-square text-success fs-1"></i>
                                    </span>
                                    <!--end::Svg Icon-->
                                </a>
                        </div>
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0">
                        <table id="kitLibraryDataTable" class="table table-row-bordered gy-5">
                            <thead>
                            <tr class="fw-semibold fs-6 text-muted">
                                <th>#</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Packages</th>
                                <th>Kit Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <!--end::Card body-->
                </div>
               </div>
            </div>
        </div>
        <!--end::Content container-->
    </div>
    <!--end::Content-->
@endsection
@push('head-scripts')
    <link rel="stylesheet" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush
@push('footer-scripts')
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <!--end::Custom Javascript-->
    <script src="{{ asset('assets/js/admin/kit_libraries/kit_libraries.js') }}"></script>
    <script type="text/javascript">
        $(function () {
            var table = $('#kitLibraryDataTable').DataTable({
                language: {
                    url: "{{ asset('assets/js/custom_plugins/data_tables/'.app()->getLocale().'.json') }}",
                    sSearchPlaceholder: "Search Here",
                    sSearch: "",
                },
                dom:'rfltip',
                processing: true,
                serverSide: true,
                searchable:true,
                paging:true,
                responsive: true,
                ajax: "{{ route('admin.kit_libraries.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'title', name: 'title'},
                    {data: 'category', name: 'category'},
                    {data: 'packages', name: 'packages'},
                    {data: 'kit_type', name: 'kit_type'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action'},
                ]
            });
        });
    </script>
@endpush

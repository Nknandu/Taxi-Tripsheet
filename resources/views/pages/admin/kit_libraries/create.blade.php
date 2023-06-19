@extends('layouts.admin.admin_app', ['body_class' => ''])
@section('title', 'Add Kit Library')
@push('breadcrumbs')
    <!--begin::Item-->
    <li class="breadcrumb-item text-muted"><a href="{{ route('admin.kit_libraries.index') }}" class="text-muted text-hover-primary">Kit Libraries</a></li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-400 w-5px h-2px"></span>
    </li>
    <!--end::Item-->
    <!--begin::Item-->
    <li class="breadcrumb-item active"><a href="" class="text-muted text-hover-primary">Add Kit Library</a></li>
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
                                <h2>Add Kit Library</h2>
                            </div>
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <form class="form" action="#" id="addKitLibraryForm" enctype="multipart/form-data" method="POST">
                                <!--begin::Modal body-->
                                <div class="modal-body py-10 px-lg-17">
                                        <div class="row g-9 mb-7">
                                            <div class="col-md-8 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Title</label>
                                                <input type="text" class="form-control" placeholder="Title" name="title">
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-4 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Preview Link</label>
                                                <input type="url" class="form-control" placeholder="Preview Link" name="preview_link">
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-6 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Kit Type</label>
                                                <select class="form-select" id="kit_type" name="kit_type" data-control="select2" data-placeholder="Select Any" data-allow-clear="true">
                                                    <option></option>
                                                    @foreach($kit_types as $kit_type)
                                                    <option value="{{ $kit_type->id }}">{{ $kit_type->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-6 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Category</label>
                                                <select class="form-select" id="category" name="category" data-control="select2" data-placeholder="Select Any" data-allow-clear="true">
                                                    <option></option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-12 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Tags</label>
                                                <select class="form-select" id="tags" name="tags[]" data-control="select2" data-placeholder="Select Any" data-allow-clear="true"  multiple="multiple">
                                                    <option></option>
                                                    @foreach($tags as $tag)
                                                        <option value="{{ $tag->id }}">{{ $tag->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-12 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Packages</label>
                                                <select class="form-select" id="packages" name="packages[]" data-control="select2" data-placeholder="Select Any" data-allow-clear="true"  multiple="multiple">
                                                    <option></option>
                                                    @foreach($packages as $package)
                                                        <option value="{{ $package->id }}">{{ $package->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-12 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Description</label>
                                                <textarea  id="description" name="description" placeholder="Description"></textarea> <!--end::Input-->
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-6 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Image (1200px X 800px)</label> <br>
                                                <div class="image-input image-input-empty" data-kt-image-input="true" style="background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}')">
                                                    <div class="image-input-wrapper w-125px h-125px"></div>
                                                    <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                           data-kt-image-input-action="change"
                                                           data-bs-toggle="tooltip"
                                                           data-bs-dismiss="click"
                                                           title="Change">
                                                         <i class="bi bi-pencil-fill fs-7"></i>
                                                        <input type="file" name="image" accept="image/*" />
                                                        <input type="hidden" name="avatar_remove" />
                                                    </label>
                                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                          data-kt-image-input-action="cancel"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-dismiss="click"
                                                          title="Cancel">
                                                          <i class="bi bi-x fs-2"></i>
                                                    </span>
                                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                          data-kt-image-input-action="remove"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-dismiss="click"
                                                          title="Remove">
                                                          <i class="bi bi-x fs-2"></i>
                                                     </span>
                                                </div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-6 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Cover Image (1200px X 400px)</label> <br>
                                                <div class="image-input image-input-empty" data-kt-image-input="true" style="background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}')">
                                                    <div class="image-input-wrapper w-125px h-125px"></div>
                                                    <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                           data-kt-image-input-action="change"
                                                           data-bs-toggle="tooltip"
                                                           data-bs-dismiss="click"
                                                           title="Change">
                                                        <i class="bi bi-pencil-fill fs-7"></i>
                                                        <input type="file" name="cover_image" accept="image/*" />
                                                        <input type="hidden" name="avatar_remove" />
                                                    </label>
                                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                          data-kt-image-input-action="cancel"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-dismiss="click"
                                                          title="Cancel">
                                                        <i class="bi bi-x fs-2"></i>
                                                    </span>
                                                    <span class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-25px h-25px bg-body shadow"
                                                          data-kt-image-input-action="remove"
                                                          data-bs-toggle="tooltip"
                                                          data-bs-dismiss="click"
                                                          title="Remove">
                                                        <i class="bi bi-x fs-2"></i>
                                                    </span>
                                                </div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-12 fv-row fv-plugins-icon-container">
                                                <label class="required fs-6 fw-semibold mb-2">Choose File</label> <br>
                                                <div class="preview-zone hidden">
                                                    <div class="box box-solid">
                                                        <div class="box-body"></div>
                                                    </div>
                                                </div>
                                                <div class="dropzone-wrapper">
                                                    <div class="dropzone-desc">
                                                        <i class="bi-upload"></i>
                                                        <p>Choose File</p>
                                                    </div>
                                                    <input type="file" name="file" id="dropzoneExcel" class="dropzone" accept=".json">
                                                </div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                            <div class="col-md-12 fv-row fv-plugins-icon-container">
                                                <label class="fs-6 fw-semibold mb-2">Status</label>
                                                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                                    <input class="form-check-input" name="status" type="checkbox" checked value="1" id="status">
                                                </div>
                                                <div class="fv-plugins-message-container invalid-feedback"></div>
                                            </div>
                                         </div>

                                        <div class="row g-9 mb-7">
                                            <h4>Features</h4>
                                            <div id="kit_library_feature">
                                                <div class="form-group">
                                                    <div data-repeater-list="kit_library_feature">
                                                        <div data-repeater-item class="mb-8">
                                                            <div class="form-group row">
                                                                <div class="col-md-3">
                                                                    <label class="required form-label">Title</label>
                                                                    <input type="text" name="kit_library[title][]" class="form-control" placeholder="Title"/>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="required form-label">Description</label>
                                                                    <textarea class="form-control" placeholder="Description"  name="kit_library[description][]"></textarea>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label class="required form-label">Icon (SVG Code)</label>
                                                                    <textarea class="form-control" placeholder="Icon (SVG Code)"  name="kit_library[icon][]"></textarea>
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <a href="javascript:;" data-repeater-delete class="btn btn-sm btn-light-danger mt-3 mt-md-8">
                                                                        <i class="la la-trash-o"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group mt-5">
                                                    <a href="javascript:;" data-repeater-create class="btn btn-light-primary">
                                                        <i class="la la-plus"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                 </div>
                                <!--end::Modal body-->
                                <!--begin::Modal footer-->
                                <div class="modal-footer">
                                    <!--begin::Button-->
                                    <button type="submit" id="createButton" class="btn btn-primary">
                                        <span class="indicator-label">Save</span>
                                        <span class="indicator-progress">Please Wait
		                            	<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                    <!--end::Button-->
                                </div>
                                <!--end::Modal footer-->
                            </form>
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
    <style>
        .container {
            padding: 50px 10%;
        }

        .box {
            position: relative;
            background: #ffffff;
            width: 100%;
        }

        .box-header {
            color: #444;
            display: block;
            padding: 10px;
            position: relative;
            border-bottom: 1px solid #f4f4f4;
            margin-bottom: 10px;
        }

        .box-tools {
            position: absolute;
            right: 10px;
            top: 5px;
        }

        .dropzone-wrapper {
            border: 2px dashed #91b0b3;
            color: #92b0b3;
            position: relative;
            height: 150px;
        }

        .dropzone-desc {
            position: absolute;
            margin: 0 auto;
            left: 0;
            right: 0;
            text-align: center;
            width: 40%;
            top: 50px;
            font-size: 16px;
        }

        .dropzone,
        .dropzone:focus {
            position: absolute;
            outline: none !important;
            width: 100%;
            height: 150px;
            cursor: pointer;
            opacity: 0;
        }

        .dropzone-wrapper:hover,
        .dropzone-wrapper.dragover {
            background: #ecf0f5;
        }

        .preview-zone, .preview-zone-image, .preview-zone-video {
            text-align: center;
        }

        .preview-zone .box, .preview-zone-image .box, .preview-zone-video .box {
            box-shadow: none;
            border-radius: 0;
            margin-bottom: 0;
        }

    </style>
@endpush
@push('footer-scripts')
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ asset('assets/js/custom_plugins/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script>
    <!--end::Custom Javascript-->
    <script src="{{ asset('assets/js/admin/kit_libraries/create_kit_libraries.js') }}"></script>
@endpush

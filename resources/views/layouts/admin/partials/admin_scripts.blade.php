<script type="text/javascript">
    let base_url = "{{ url('') }}"
    let admin_base_url = "{{ url('admin') }}"
    let hostUrl = "assets/";
    let are_you_sure_want_to_delete ='Are You Sure Want To Delete ?';
    let are_you_sure_want_to_change ='Are You Sure Want To Change ?';
    let yes_text ='Yes';
    let no_text ='No';
    let ok_text ='OK';
    let clear_text ='Clear';
    let apply_text ='Apply';
    let locale_language ='{{ app()->getLocale() }}';
</script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
{{--<script src="https://code.jquery.com/jquery-3.6.0.js"></script>--}}

<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
<script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('assets/js/admin/admin.js') }}"></script>
<script src="{{ asset('assets/js/custom_plugins/select2/'.app()->getLocale().'.js') }}"></script>
@if(session()->get('locale') == 'ar')
    <script src="{{ asset('assets/js/custom_plugins/validation/messages_ar.js') }}"></script>
    <script src="{{ asset('assets/js/custom_plugins/date_picker/ar.js') }}"></script>
@endif
<!--end::Global Javascript Bundle-->
<script src="{{ asset('assets/js/jquery-ui.js') }}"></script>

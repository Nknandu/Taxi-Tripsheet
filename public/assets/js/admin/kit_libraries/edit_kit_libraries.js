$(document).ready(function () {
    ClassicEditor.create(document.querySelector('#description'),
        {
            toolbar: [ 'bold', 'italic','underline', 'bulletedList', 'numberedList', 'link', 'blockQuote', 'heading' ]
        })
        .then(editor => {
            console.log(editor);
        })
        .catch(error => {
            console.error(error);
        });
    $('#kit_type').select2();
    $('#category').select2();
    $('#tags').select2();
    $('#packages').select2();
    function readFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                var htmlPreview =
                    '<img width="50" src="/assets/media/dummy/json.jpeg" />' +
                    '<p></p>';
                var wrapperZone = $(input).parent();
                var previewZone = $(input).parent().parent().find('.preview-zone');
                var boxZone = $(input).parent().parent().find('.preview-zone').find('.box').find('.box-body');

                wrapperZone.removeClass('dragover');
                previewZone.removeClass('hidden');
                boxZone.empty();
                boxZone.append(htmlPreview);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }


    $("#dropzoneExcel").change(function() {
        readFile(this);
    });


    $('.dropzone-wrapper').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $('.dropzone-wrapper').on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $('.remove-preview').on('click', function() {
        var boxZone = $(this).parents('.preview-zone').find('.box-body');
        var previewZone = $(this).parents('.preview-zone');
        var dropzone = $(this).parents('.form-group').find('.dropzone');
        boxZone.empty();
        previewZone.addClass('hidden');
        reset(dropzone);
    });
});

$('#kit_library_feature').repeater({
    initEmpty: false,

    defaultValues: {
        'text-input': 'foo'
    },

    show: function () {
        $(this).slideDown();
    },

    hide: function (deleteElement) {
        $(this).slideUp(deleteElement);
    }
});


$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

$(document).on('submit', "#editKitLibraryForm", function(e){
    e.preventDefault();
    var editButton = document.querySelector("#editButton");
    var editKitLibraryForm = $("#editKitLibraryForm");
    var form_data = new FormData(this);
    console.log(form_data)
    editKitLibraryForm.validate({
        // lang:'ar',
        rules: {
            name: {
                required: true,
            },
            name_ar: {
                required: true,
            },
        },
    });
    if (!editKitLibraryForm.valid()){
        return;
    }
    loadPreloader(true)
    editButton.setAttribute("data-kt-indicator", "on");
    let id = $("input[name=id]").val();
    var update_url = admin_base_url+"/kit_libraries/"+id+"/update"
    $.ajax({
        type:'POST',
        mimeType: "multipart/form-data",
        cache: false,
        contentType: false,
        processData: false,
        url:update_url,
        data:form_data,
        success:function(data){
            data = JSON.parse(data);
            editButton.removeAttribute("data-kt-indicator", "on");
            if(data.success == true)
            {
                printSingleSuccessToast(data.message);
            }
            else if(data.success == false)
            {
                if($.isEmptyObject(data.errors))
                {
                    printSingleErrorToast(data.message);
                }
                else
                {
                    printMultipleErrorToast(data.errors);
                }
            }
            loadPreloader(false)
        },
        error: function(xhr, status, error){
            printSingleErrorToast(error);
        },
    });

});

function deleteAttributeValue(id)
{
    Swal.fire({
        html: are_you_sure_want_to_delete,
        icon: "question",
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: yes_text,
        cancelButtonText: no_text,
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: 'btn btn-primary'
        }
    }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
            var delete_url = admin_base_url+"/kit_library_features/"+id+"/delete"
            $.ajax({
                type:'POST',
                mimeType: "multipart/form-data",
                cache: false,
                contentType: false,
                processData: false,
                url:delete_url,
                data:{},
                success:function(data){
                    data = JSON.parse(data);
                    if(data.success == true) {
                        Swal.fire({
                            html: data.message,
                            icon: "success",
                            confirmButtonText: ok_text,
                        }).then((result) => {
                            /* Read more about isConfirmed, isDenied below */
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        })
                    }
                    else if(data.success == false)
                    {
                        if($.isEmptyObject(data.errors))
                        {
                            printSingleErrorToast(data.message);
                        }
                        else
                        {
                            printMultipleErrorToast(data.errors);
                        }
                    }
                    loadPreloader(false)
                },
                error: function(xhr, status, error){
                    printSingleErrorToast(error);
                },
            });
        }
    })
}

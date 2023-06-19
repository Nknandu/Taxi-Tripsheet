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
$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

$(document).on('submit', "#addKitLibraryForm", function(e){
    e.preventDefault();
    var createButton = document.querySelector("#createButton");
    var addKitLibraryForm = $("#addKitLibraryForm");
    var form_data = new FormData(this);
    console.log(form_data)
    addKitLibraryForm.validate({
        // lang:'ar',
        rules: {
            title: {
                required: true,
            },
            preview_link: {
                required: true,
            },
            'tags[]': {
                required: true,
            },
            kit_type: {
                required: true,
            },
            category: {
                required: true,
            },
            'packages[]': {
                required: true,
            },
            image: {
                required: true,
            },
            cover_image: {
                required: true,
            },
            file: {
                required: true,
            },
            description: {
                required: true,
            },
        },
    });
    if (!addKitLibraryForm.valid()){
        return;
    }
    loadPreloader(true)
    createButton.setAttribute("data-kt-indicator", "on");
    store_url = admin_base_url+"/kit_libraries"
    $.ajax({
        type:'POST',
        mimeType: "multipart/form-data",
        cache: false,
        contentType: false,
        processData: false,
        url:store_url,
        data:form_data,
        success:function(data){
            data = JSON.parse(data);
            createButton.removeAttribute("data-kt-indicator", "on");
            if(data.success == true)
            {
                printSingleSuccessToast(data.message);
                window.location.reload();
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

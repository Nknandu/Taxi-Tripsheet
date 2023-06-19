$(document).ready(function () {
    $("#kt_datatable_zero_configuration").DataTable();
});
$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });



function deleteAttribute(id)
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
            var delete_url = admin_base_url+"/attributes/"+id+"/delete"
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
                                $('#attributeDataTable').DataTable().columns.adjust().draw();
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


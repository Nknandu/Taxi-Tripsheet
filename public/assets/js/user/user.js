
function loadPreloader(status) {
    if(status == true)
    {
        $('#customAjaxLoader').show();
    }
    else
    {
        $('#customAjaxLoader').hide();
    }
}

// Multiple Error Toast Display
function printMultipleErrorToast (errors) {
    $.each( errors, function( key, value ) {
       alert(value)
    });
}

// Error Toast Display
function printSingleErrorToast (message) {
    alert(message)
}


// Success Toast
function printSingleSuccessToast(message)
{
    alert(message)
}

<?php
use App\Models\Localization;
use Carbon\Carbon;


function testHelperFunction()
{
    return "Test Helper Function Successfully";
}

function localizeValidationFormElements()
{
    $language = app()->getLocale();
    $form_elements = Localization::select('key','en','ar')->where('type', 'form_element')->where('status', 1)->get()->toArray();
    $response = [];
    foreach($form_elements as $key => $form_element ){
        $resp[$form_element['key']] = $language == 'en' ? $form_element['en'] : $form_element['ar'];
    }
    return $response;
}

function getMessageText($message_key)
{
    $language = app()->getLocale();
    $message_array = Localization::select('key','en','ar')->where('key',$message_key)->where('type', 'message')->where('status', 1)->first();
    if($message_array)
    {
        $message =  $language == 'en' ? $message_array['en'] : $message_array['ar'];
    }
    else
    {
        $message = $message_key;
    }
    return $message;
}

function getPerPageLimit($per_page)
{
    if($per_page == "" || $per_page == null) {
        return 10;
    }
    return $per_page;
}

function getLocalTimeFromUtc($utc_time, $local_time_zone = "Asia/Kolkata")
{
    // $utc_time must be in y-m-d H:i:s Format
    // $local_time_zone in "Asia/Kolkata" format
    if($utc_time == NULL) return "";
    if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
    $given_utc_time  = Carbon::createFromFormat('Y-m-d H:i:s', $utc_time, 'UTC');
    return $given_utc_time->setTimezone($local_time_zone)->toDateTimeString();
}

function getReadableLocalTimeFromUtc($utc_time, $local_time_zone = "Asia/Kolkata")
{
    // $utc_time must be in y-m-d H:i:s Format
    // $local_time_zone in "Asia/Kolkata" format
    if($utc_time == NULL) return "";
    if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
    $given_utc_time  = Carbon::createFromFormat('Y-m-d H:i:s', $utc_time, 'UTC');
    return $given_utc_time->setTimezone($local_time_zone)->format('d-m-Y h:i A');
}

function getReadableDate($date)
{
    if($date)
    {
        return date('d-m-Y', strtotime($date));
    }
    else
    {
        return null;
    }

}

function getUtcTimeFromLocal($local_time, $local_time_zone = "Asia/Kolkata")
{
    // $local_time must be in y-m-d H:i:s Format
    // $local_time_zone in "Asia/Kolkata" format
    if($local_time == NULL) return "";
    if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
    $given_local_time  = Carbon::createFromFormat('Y-m-d H:i:s', $local_time, $local_time_zone);
    return $given_local_time->setTimezone('UTC')->toDateTimeString();
}

function getLocalTimeZone($local_time_zone = "Asia/Kolkata")
{
    if(!$local_time_zone) $local_time_zone = "Asia/Kolkata";
    return $local_time_zone;
}

function getDbDateTimeFormat($date_time)
{
    if($date_time)
    {
        return date('Y-m-d H:i:s', strtotime($date_time));
    }
    else
    {
        return null;
    }

}

function getDbDateFormat($date)
{
    if($date)
    {
        return date('Y-m-d', strtotime($date));
    }
    else
    {
        return null;
    }

}

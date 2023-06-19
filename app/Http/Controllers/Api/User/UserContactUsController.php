<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserContactUsController extends Controller
{
    public function userContactUs(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'contact_number' => 'required|max:15',
                'email' => 'required|email|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        //'errors' => $validator->errors()->all()
                    ], 200);
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $user_id = NULL;
            if(auth('api')->check()) $user_id = auth('api')->id();

            $contact_us = new ContactEnquiry();
            $contact_us->user_id = $user_id;
            $contact_us->contact_number = $request->contact_number;
            $contact_us->email = $request->email;
            $contact_us->save();

            $contact_array = [
                'id' => $contact_us->id,
                'contact_number' => (string )$contact_us->contact_number,
                'email' => (string) $contact_us->email,
                'updated_at' =>(string)  getLocalTimeFromUtc($contact_us->updated_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_contact_us_success'),
                    'message_code' => 'user_contact_us_success',
                    'data' => [
                        'contact_enquiry' => $contact_array,
                        'cart_count' => (string) getCartCount()
                    ]
                ], 200);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }
}

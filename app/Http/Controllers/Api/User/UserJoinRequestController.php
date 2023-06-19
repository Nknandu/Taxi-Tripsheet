<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\JoinRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Image;

class UserJoinRequestController extends Controller
{
    public function getUserTypes(Request $request)
    {
        try
        {
            $user_type_array = [
                [
                    'id' => "User",
                    'name' => __('messages.individual')
                ],
                [
                    'id' => "Vendor",
                    'name' => __('messages.company')
                ],
            ];


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_types_success',
                    'data' => [
                        "user_types" => $user_type_array,
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
    public function userJoinUs(Request $request)
    {
        try
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('upload_max_filesize', '20M');
            ini_set('post_max_size', '30M');

            $validator = Validator::make($request->all(), [
                'user_type' => 'required|string|max:100',
                'user_name' => 'required|string|max:100',
                'contact_number' => 'required|string|max:15',
                'email' => 'email|max:100',
                'industry' => 'required|string|max:50',
                'country' => 'required|string|max:50',
                'company_name' => 'required|string|max:50',
//                'nationality' => 'required|string|max:50',
//                'website' => 'required|url|max:100',
                'expected_monthly_orders' => 'required',
                'expected_monthly_income' => 'required',
                'boutique_description' => 'required|string',
                'commercial_registration' => 'required|string',
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



            $slug = Str::slug($request->user_name);
            $image_name_in_db = null;
            if($request->hasfile('commercial_registration'))
            {
                $image = $request->file('commercial_registration');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $original_image_file = $original_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/join_requests/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                   $image_name_in_db = $image_name;
                }
            }
            elseif(isset($request->commercial_registration) && $request->commercial_registration)
            {
                $img_data = base64_decode($request->commercial_registration);
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $img_data, FILEINFO_MIME_TYPE);

                $extension = explode('/', $mime_type)[1];
                $type = explode('/', $mime_type)[0];
                $image = $request->commercial_registration;
                $image_name = $slug."-".time().'.'.$extension;

                $image_data = "data:".$mime_type.";base64,".$request->commercial_registration;
                if(strtolower($type) == "image")
                {
                    if(strtolower($extension) == 'gif')
                    {
                        $original_image_file = $thumb_image_file = file_get_contents($request->commercial_registration);
                    }
                    else
                    {
                        $original_image = Image::make($image);
                        $thumb_image = Image::make($image);
                        $original_image_file = $original_image->stream()->__toString();
                        $thumb_image_file = $thumb_image->stream()->__toString();
                    }
                    if(Storage::disk('public')->put('uploads/join_requests/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
                else
                {
                    $original_image_file = $thumb_image_file = file_get_contents($image_data);
                    if(Storage::disk('public')->put('uploads/join_requests/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }

            }
            else
            {

            }

            $join_us = new JoinRequest();
            $join_us->user_type = $request->user_type;
            $join_us->user_name = $request->user_name;
            $join_us->contact_number = $request->contact_number;
            $join_us->email = $request->email;
            $join_us->industry = $request->industry;
            $join_us->company_name = $request->company_name;
            $join_us->country = $request->country;
            $join_us->nationality = $request->nationality;
            $join_us->website = $request->website;
            $join_us->expected_monthly_orders = $request->expected_monthly_orders;
            $join_us->expected_monthly_income = $request->expected_monthly_income;
            $join_us->boutique_description = $request->boutique_description;
            $join_us->commercial_registration = $image_name_in_db;
            $join_us->save();

            if($join_us->user_type == "User")
            {
                $user_type = __('messages.individual');
            }
            elseif ($join_us->user_type == "Vendor")
            {
                $user_type = __('messages.company');
            }

            $join_us = JoinRequest::where('id', $join_us->id)->first();
            $join_array = [
                'id' => $join_us->id,
                'user_type' => (string ) $user_type,
                'user_name' => (string) $join_us->user_name,
                'contact_number' => (string) $join_us->contact_number,
                'email' => (string) $join_us->email,
                'industry' => (string) $join_us->industry,
                'company_name' => (string) $join_us->company_name,
                'country' => (string) $join_us->country,
                'nationality' => (string) $join_us->nationality,
                'website' => (string) $join_us->website,
                'expected_monthly_orders' => (string) $join_us->expected_monthly_orders,
                'expected_monthly_income' => (string) $join_us->expected_monthly_income,
                'boutique_description' => (string) $join_us->boutique_description,
                'commercial_registration' => (string) $join_us->file,
                'requested_on' =>(string)  getLocalTimeFromUtc($join_us->created_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_join_us_success'),
                    'message_code' => 'user_join_us_success',
                    'data' => [
                        'contact_enquiry' => $join_array,
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

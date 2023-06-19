<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery\Exception;
use Illuminate\Support\Facades\Auth;
use Image;

class UserAuthController extends Controller
{
    public function userLogin(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $credentials = $request->only('email', 'password');
            $validator = Validator::make($credentials, [
                'email' => 'required|email',
                'password' => 'required'
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
            $user_count = User::where('email', $credentials['email'])->count();
            if(!$user_count)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.user_does_not_exist'),
                        'message_code' => 'user_does_not_exist',
                    ], 200);
            }
            if(isset($request->remember_me) && $request->remember_me) $remember_me = true; else $remember_me = false;
            if(auth()->attempt($credentials, $remember_me))
            {
                if(isset($request->device_token) && $request->device_token)
                {
                 $user_device = UserDevice::where('user_id', Auth::id())->where('device_token', $request->device_token)->first();
                 if($user_device)
                 {
                     if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                     if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                     if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                     if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                     if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                     $user_device->save();
                 }
                 else
                 {
                     $user_device = new UserDevice();
                     $user_device->user_id = Auth::id();
                     if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                     if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                     if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                     if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                     if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                     $user_device->save();
                 }

                }

                $user = User::where('id', Auth::id())->first();
                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' => __('messages.user_login_success'),
                        'message_code' => 'user_login_success',
                        'data' => [
                            'id' => auth()->id(),
                            'first_name' => (string) auth()->user()->first_name,
                            'last_name' => (string) auth()->user()->last_name,
                            'email' => (string) auth()->user()->email,
                            'mobile_number' => (string) auth()->user()->mobile_number,
                            'gender' => (string) auth()->user()->gender,
                            'date_of_birth' => (string) auth()->user()->date_of_birth,
                            'user_type' => (string) auth()->user()->user_type,
                            'profile_image_thumb' => (string) auth()->user()->thumb_image,
                            'profile_image_original' => (string) auth()->user()->original_image,
                            'user_permissions' =>  auth()->user()->getPackageFeatureAppSide(),
                            'user_package_status' => (string) auth()->user()->getPackageStatus(),
                            'user_package_end_date' => (string) (auth()->user()->getPackageEndDate()) ? getLocalTimeFromUtc(auth()->user()->getPackageEndDate(), $time_zone):"",
                            'user_profile_frame' => userProfileSlab(auth()->id()),
                            'token' => $user->createToken('Beiaat')->accessToken
                        ]
                    ], 200);
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.invalid_credentials'),
                        'message_code' => 'invalid_credentials',
                    ], 200);
            }
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

    public function userRegister(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|confirmed',
                'mobile_number' => 'required|unique:users',
                //'gender' => 'required',
                'date_of_birth' => 'nullable|date',
                //'image' => 'mimes:jpeg,jpg,png,gif',
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

            $slug = Str::slug($request->name, '-');
            $image_name_in_db = null;
//            if($request->hasfile('image'))
//            {
//                $image = $request->file('image');
//                $image_name = $slug."-".time().'.'.$image->extension();
//                if(strtolower($image->extension()) == 'gif')
//                {
//                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
//                }
//                else
//                {
//                    $original_image = Image::make($image);
//                    $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
//                    $original_image_file = $original_image->stream()->__toString();
//                    $thumb_image_file = $thumb_image->stream()->__toString();
//                }
//                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
//                {
//                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
//                    {
//                        $image_name_in_db = $image_name;
//                    }
//                }
//            }


            if($request->hasfile('image'))
            {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }
            elseif(isset($request->image) && $request->image)
            {
                $image = $request->image;
                $image_name = $slug."-".time().'.png';

                //  Image::make(file_get_contents($data->base64_image))->save($path);
                $original_image = Image::make($image);
                $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
                $original_image_file = $original_image->stream()->__toString();
                $thumb_image_file = $thumb_image->stream()->__toString();

                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }
            else
            {

            }

            $user = new User();
            $user->first_name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->mobile_number = $request->mobile_number;
            $user->gender = $request->gender;
            $user->image = $image_name_in_db;
            $user->date_of_birth = $request->date_of_birth;
            $user->save();

            if(isset($request->device_token) && $request->device_token)
            {
                $user_device = UserDevice::where('user_id', $user->id)->where('device_token', $request->device_token)->first();
                if($user_device)
                {
                    if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                    $user_device->save();
                }
                else
                {
                    $user_device = new UserDevice();
                    $user_device->user_id = Auth::id();
                    if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                    $user_device->save();
                }

            }

            $user = User::where('id', $user->id)->first();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.user_register_success'),
                    'message_code' => 'user_register_success',
                    'data' => [
                        'id' => $user->id,
                        'first_name' => (string) $user->first_name,
                        'last_name' => (string) $user->last_name,
                        'email' => (string) $user->email,
                        'mobile_number' => (string) $user->mobile_number,
                        'gender' => (string) $user->gender,
                        'date_of_birth' => (string) $user->date_of_birth,
                        'user_type' => (string) $user->user_type,
                        'profile_image_thumb' => (string) $user->thumb_image,
                        'profile_image_original' => (string) $user->original_image,
                        'user_permissions' =>  $user->getPackageFeatureAppSide(),
                        'user_package_end_date' => (string) ($user->getPackageEndDate()) ? getLocalTimeFromUtc($user->getPackageEndDate(), $time_zone):"",
                        'user_package_status' => (string) $user->getPackageStatus(),
                        'user_profile_frame' => userProfileSlab($user->id),
                        'token' => $user->createToken('Beiaat')->accessToken
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


    public function userInfo(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $user = Auth::user();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_info',
                    'data' => [
                        'id' => auth()->id(),
                        'first_name' =>(string) auth()->user()->first_name,
                        'last_name' =>(string) auth()->user()->last_name,
                        'email' =>(string) auth()->user()->email,
                        'mobile_number' =>(string) auth()->user()->mobile_number,
                        'gender' =>(string) auth()->user()->gender,
                        'date_of_birth' =>(string) auth()->user()->date_of_birth,
                        'user_type' =>(string) auth()->user()->user_type,
                        'profile_image_thumb' => (string) $user->thumb_image,
                        'profile_image_original' => (string) $user->original_image,
                        'user_permissions' =>  auth()->user()->getPackageFeatureAppSide(),
                        'user_package_end_date' => (string) (auth()->user()->getPackageEndDate()) ? getLocalTimeFromUtc(auth()->user()->getPackageEndDate(), $time_zone):"",
                        'user_package_status' => (string) auth()->user()->getPackageStatus(),
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

    public function changePassword(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|confirmed',
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

            if(!Hash::check($request->current_password, auth('api')->user()->password)){
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.current_password_is_wrong'),
                        'message_code' => 'current_password_is_wrong',
                    ], 200);
            }

            if(Hash::check($request->new_password, auth('api')->user()->password)){
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.new_password_is_same_as_old_password'),
                        'message_code' => 'new_password_is_same_as_old_password',
                    ], 200);
            }

            User::whereId(auth('api')->user()->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.user_change_password_success'),
                    'message_code' => 'user_change_password_success',
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

    public function updateProfile(Request $request)
    {
        try
        {

            $time_zone = getLocalTimeZone($request->time_zone);
            $user_id = auth('api')->id();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email'=>'email|unique:users,email,'.$user_id,
                'mobile_number'=>'required|unique:users,mobile_number,'.$user_id,
                //'gender' => 'required',
                'date_of_birth' => 'nullable|date',
               // 'image' => 'mimes:jpeg,jpg,png,gif',
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


            $user = User::findOrFail(auth('api')->id());
            if(isset($request->name) && $request->name)$user->first_name = $request->name;
            if(isset($request->email) && $request->email)$user->email = $request->email;
            if(isset($request->mobile_number) && $request->mobile_number)$user->mobile_number = $request->mobile_number;
            if(isset($request->gender) && $request->gender) $user->gender = $request->gender;
            if(isset($request->date_of_birth) && $request->date_of_birth)$user->date_of_birth = $request->date_of_birth;

            $slug = Str::slug($user->first_name, '-');
            if(isset($request->name) && $request->name)  $slug = Str::slug($request->name, '-');
            $image_name_in_db = null;
//            if($request->hasfile('image'))
//            {
//
//                $image = $request->file('image');
//                $image_name = $slug."-".time().'.'.$image->extension();
//                if(strtolower($image->extension()) == 'gif')
//                {
//                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
//                }
//                else
//                {
//                    $original_image = Image::make($image);
//                    $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
//                    $original_image_file = $original_image->stream()->__toString();
//                    $thumb_image_file = $thumb_image->stream()->__toString();
//                }
//                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
//                {
//                    if(Storage::disk('public')->exists('uploads/users/original/'.$user->image))
//                    {
//                        Storage::disk('public')->delete('uploads/users/original/'.$user->image);
//                    }
//                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
//                    {
//                        if(Storage::disk('public')->exists('uploads/users/thumb/'.$user->image))
//                        {
//                            Storage::disk('public')->delete('uploads/users/thumb/'.$user->image);
//                        }
//                        $image_name_in_db = $image_name;
//                        $user->image = $image_name_in_db;
//                    }
//                }
//            }

            if($request->hasfile('image'))
            {

                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/users/original/'.$user->image))
                    {
                        Storage::disk('public')->delete('uploads/users/original/'.$user->image);
                    }
                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/users/thumb/'.$user->image))
                        {
                            Storage::disk('public')->delete('uploads/users/thumb/'.$user->image);
                        }
                        $image_name_in_db = $image_name;
                        $user->image = $image_name_in_db;
                    }
                }
            }
            elseif(isset($request->image) && $request->image)
            {
                $image = $request->image;
                $image_name = $slug."-".time().'.png';

                $png_url = "product-".time().".png";
                $path = storage_path().'uploads/users/original/' . $image_name;

                  //  Image::make(file_get_contents($data->base64_image))->save($path);
                   $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(150, 150, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();

                if(Storage::disk('public')->put('uploads/users/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/users/original/'.$user->image))
                    {
                        Storage::disk('public')->delete('uploads/users/original/'.$user->image);
                    }
                    if(Storage::disk('public')->put('uploads/users/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/users/thumb/'.$user->image))
                        {
                            Storage::disk('public')->delete('uploads/users/thumb/'.$user->image);
                        }
                        $image_name_in_db = $image_name;
                        $user->image = $image_name_in_db;
                    }
                }
            }
            else
            {

            }

            $user->save();

            if(isset($request->device_token) && $request->device_token)
            {
                $user_device = UserDevice::where('user_id', $user->id)->where('device_token', $request->device_token)->first();
                if($user_device)
                {
                    if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                    $user_device->save();
                }
                else
                {
                    $user_device = new UserDevice();
                    $user_device->user_id = Auth::id();
                    if(isset($request->device_token) && $request->device_token)$user_device->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$user_device->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$user_device->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$user_device->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$user_device->os_version = $request->os_version;
                    $user_device->save();
                }

            }

            $user = User::findOrFail(auth('api')->id());

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.update_profile_success'),
                    'message_code' => 'update_profile_success',
                    'data' => [
                        'id' => $user->id,
                        'first_name' => (string) $user->first_name,
                        'last_name' => (string) $user->last_name,
                        'email' => (string) $user->email,
                        'mobile_number' => (string) $user->mobile_number,
                        'gender' => (string) $user->gender,
                        'date_of_birth' => (string) $user->date_of_birth,
                        'user_type' => (string) $user->user_type,
                        'profile_image_thumb' => (string) $user->thumb_image,
                        'profile_image_original' => (string) $user->original_image,
                        'user_permissions' =>  $user->getPackageFeatureAppSide(),
                        'user_package_end_date' => (string) ($user->getPackageEndDate()) ? getLocalTimeFromUtc($user->getPackageEndDate(), $time_zone):"",
                        'user_package_status' => (string) $user->getPackageStatus(),
                        'token' => $user->createToken('Beiaat')->accessToken
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

    public function userForgotPassword(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
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

            $user = User::where('email', $request->email)->first();
            if(!$user){
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.user_does_not_exist'),
                        'message_code' => 'user_does_not_exist',
                    ], 200);
            }

            $password = Str::random(8);
            User::where('email', $request->email)->update([
                'password' => Hash::make($password)
            ]);
            sendForgotPassword($user->id, $password);
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.user_forgot_password_success'),
                    'message_code' => 'user_forgot_password_success',
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

    public function userLogout(Request $request)
    {
        try
        {
            UserDevice::where('device_token', $request->device_token)->delete();
            Auth::user()->token()->revoke();
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_logged_out_success'),
                    'message_code' => 'user_logged_out_success',
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

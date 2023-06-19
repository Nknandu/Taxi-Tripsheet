<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Area;
use App\Models\Country;
use App\Models\Governorate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserAddressController extends Controller
{
    public function getCountries(Request $request)
    {
        try
        {
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $country_query = Country::query();
            $country_query->where('status', 1);
            $country_query->select('id', $name_array['name']);
            $country_query->with(['governorates' => function ($query) use($name_array) {
                $query->select('id', 'country_id', $name_array['name']);
                $query->with(['areas' => function ($query) use($name_array) {
                    $query->select('id', 'governorate_id', $name_array['name']);
                }]);
            }]);
            $country_query->orderBy('id', 'ASC');
            $country_data_array = $country_query->get();


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_countries_success',
                    'data' => [
                        'countries' => $country_data_array,
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

    public function getGovernorates(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'country_id' => 'required',
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

            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $governorate_query = Governorate::query();
            $governorate_query->where('status', 1);
            $governorate_query->where('country_id', $request->country_id);
            $governorate_query->select('id', 'country_id', $name_array['name']);
            $governorate_query->orderBy('id', 'ASC');
            $governorate_data_array = $governorate_query->get();


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_governorates_success',
                    'data' => [
                        'governorates' => $governorate_data_array,
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

    public function getAreas(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'governorate_id' => 'required',
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

            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $area_query = Area::query();
            $area_query->where('status', 1);
            $area_query->where('governorate_id', $request->governorate_id);
            $area_query->select('id', 'governorate_id', $name_array['name']);
            $area_query->orderBy('id', 'ASC');
            $area_data_array = $area_query->get();


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_areas_success',
                    'data' => [
                        'areas' => $area_data_array,
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

    public function getUserAddresses(Request $request)
    {
        try
        {
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $address_query = Address::query();
            $address_query->where('user_id', auth('api')->id());
            $address_query->orderBy('is_default', 'DESC');
            $address_data_array = $address_query->get();

            $address_array = [];
            foreach ($address_data_array as $address_item)
            {
                $address_array_temp = addressDataSingle($address_item);
                array_push($address_array, $address_array_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_areas_success',
                    'data' => [
                        'addresses' => $address_array,
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

    public function addUserAddress(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                //'last_name' => 'required|string|max:100',
                'contact_number' => 'required',
                'country' => 'required',
                'governorate' => 'required',
                'area' => 'required',
               // 'avenue' => 'required',
                'block' => 'required',
                'street' => 'required',
                'building' => 'required',
                'floor' => 'required',
               // 'apartment' => 'required',
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

            $is_default = false;
            if(isset($request->is_default) && $request->is_default)
            {
                $is_default = true;
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

           $address = new Address();
           $address->user_id = auth('api')->id();
            if(isset($request->first_name) && $request->first_name)$address->first_name = $request->first_name;
            
            if(isset($request->last_name) && $request->last_name){
                $address->last_name = $request->last_name;
            }else{
                $address->last_name = "";
            }
            if(isset($request->contact_number) && $request->contact_number)$address->contact_number = $request->contact_number;
            $address->country_id = $request->country;
            $address->governorate_id = $request->governorate;
            $address->area_id = $request->area;
            if(isset($request->avenue) && $request->avenue)$address->avenue = $request->avenue;
            if(isset($request->block) && $request->block) $address->block = $request->block;
            if(isset($request->street) && $request->street) $address->street = $request->street;
            if(isset($request->building) && $request->building)$address->building = $request->building;
            if(isset($request->floor) && $request->floor)$address->floor = $request->floor;
            if(isset($request->apartment) && $request->apartment)$address->apartment = $request->apartment;
            if(isset($request->pin_code) && $request->pin_code)$address->pin_code = $request->pin_code;
           if(isset($request->notes) && $request->notes)$address->notes = $request->notes;
           $address->is_default = $request->is_default;
           $address->save();

           if($address->is_default)
           {
               Address::where('user_id', auth('api')->id())->where('id', '!=', $address->id)->update(['is_default' => 0]);
           }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_add_address_success'),
                    'message_code' => 'user_add_address_success',
                    'data' => [
                        'address' => addressDataSingle($address),
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

    public function updateUserAddress(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'address_id' => 'required',
                'first_name' => 'required|string|max:100',
                //'last_name' => 'required|string|max:100',
                'contact_number' => 'required',
                'country' => 'required',
                'governorate' => 'required',
                'area' => 'required',
                //'avenue' => 'required',
                'block' => 'required',
                'street' => 'required',
                'building' => 'required',
                'floor' => 'required',
               // 'apartment' => 'required',
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

            $is_default = false;
            if(isset($request->is_default) && $request->is_default)
            {
                $is_default = true;
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $address = Address::where('user_id', auth('api')->id())->findOrFail($request->address_id);
            $address->user_id = auth('api')->id();
            if(isset($request->first_name) && $request->first_name)$address->first_name = $request->first_name;
            
            //if(isset($request->last_name) && $request->last_name)$address->last_name = $request->last_name;
            if(isset($request->last_name) && $request->last_name){
                $address->last_name = $request->last_name;
            }else{
                $address->last_name = "";
            }
            if(isset($request->contact_number) && $request->contact_number)$address->contact_number = $request->contact_number;
            $address->country_id = $request->country;
            $address->governorate_id = $request->governorate;
            $address->area_id = $request->area;
            if(isset($request->avenue) && $request->avenue)$address->avenue = $request->avenue;
            if(isset($request->block) && $request->block) $address->block = $request->block;
            if(isset($request->street) && $request->street) $address->street = $request->street;
            if(isset($request->building) && $request->building)$address->building = $request->building;
            if(isset($request->floor) && $request->floor)$address->floor = $request->floor;
            if(isset($request->apartment) && $request->apartment)$address->apartment = $request->apartment;
            if(isset($request->pin_code) && $request->pin_code)$address->pin_code = $request->pin_code;
            if(isset($request->notes) && $request->notes)$address->notes = $request->notes;
            $address->is_default = $request->is_default;
            $address->save();

            if($address->is_default)
            {
                Address::where('user_id', auth('api')->id())->where('id', '!=', $address->id)->update(['is_default' => 0]);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_update_address_success'),
                    'message_code' => 'user_update_address_success',
                    'data' => [
                        'address' => addressDataSingle($address),
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

    public function deleteUserAddress(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'address_id' => 'required',
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

            $address = Address::where('user_id', auth('api')->id())->findOrFail($request->address_id);
//            if($address->is_default)
//            {
//                return response()->json(
//                    [
//                        'success' => false,
//                        'status' => 400,
//                        'message' => __('messages.user_cannot_delete_default_address'),
//                        'message_code' => 'user_cannot_delete_default_address',
//                    ], 200);
//            }
            $address->delete();

            $address_query = Address::query();
            $address_query->where('user_id', auth('api')->id());
            $address_query->orderBy('is_default', 'DESC');
            $address_data_array = $address_query->get();

            $address_array = [];
            foreach ($address_data_array as $address_item)
            {
                $address_array_temp = addressDataSingle($address_item);
                array_push($address_array, $address_array_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_delete_address_success'),
                    'message_code' => 'user_delete_address_success',
                    'data' => [
                        'addresses' => $address_array,
                        'cart_count' => (string) getCartCount()
                    ],
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

    public function makeUserAddressDefault(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'address_id' => 'required',
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

            $address = Address::where('user_id', auth('api')->id())->findOrFail($request->address_id);
            $address->is_default = true;
            $address->save();

            Address::where('id', '!=', $address->id)->where('user_id', auth('api')->id())->update(['is_default' => false]);

            $address_query = Address::query();
            $address_query->where('user_id', auth('api')->id());
            $address_query->orderBy('is_default', 'DESC');
            $address_data_array = $address_query->get();

            $address_array = [];
            foreach ($address_data_array as $address_item)
            {
                $address_array_temp = addressDataSingle($address_item);
                array_push($address_array, $address_array_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_set_default_address_success'),
                    'message_code' => 'user_set_default_address_success',
                    'data' => [
                        'addresses' => $address_array,
                        'cart_count' => (string) getCartCount()
                    ],
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

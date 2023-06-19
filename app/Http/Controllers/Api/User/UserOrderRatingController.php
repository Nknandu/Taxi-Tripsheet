<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserOrderRatingController extends Controller
{
    public function userGetMyOrderRating(Request $request)
    {
        try
        {
            $name_array = [];

            $validator = Validator::make($request->all(), [
                'order_id' => 'required',
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

            $order = Order::where('order_status', 'Delivered')->where('user_id', auth('api')->id())->where('id', $request->order_id)->firstOrFail();;

            $rating_query = OrderRating::query();
            $rating_query->where('user_id', auth('api')->id());
            $rating_query->where('order_id', $request->order_id);
            $rating_data_array = $rating_query->first();

            if($rating_data_array)
            {
                $rating_array = [
                    'id' => $rating_data_array->id,
                    'ratings' => (string) $rating_data_array->ratings,
                    'updated_at' => (string) getLocalTimeFromUtc($rating_data_array->updated_at, $time_zone)
                ];
            }
            else
            {
                $rating_array = [
                    'id' => 0,
                    'ratings' => (string) "",
                    'updated_at' => (string) ""
                ];
            }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_rating_success',
                    'data' => [
                        'my_rating' => (object) $rating_array,
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

    public function userAddOrderRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'ratings' => 'required',
                'order_id' => 'required|numeric|min:1',
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

            $rating_count = OrderRating::where('user_id', auth('api')->id())->where('order_id', $request->order_id)->count();
            $order = Order::where('order_status', 'Delivered')->where('user_id', auth('api')->id())->where('id', $request->order_id)->firstOrFail();;
            if($rating_count)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.already_rated'),
                        'message_code' => 'already_rated',
                    ], 200);
            }

            $rating = new OrderRating();
            $rating->user_id = auth('api')->id();
            $rating->ratings = $request->ratings;
            $rating->order_id = $order->id;
            $rating->vendor_id = $order->vendor_id;
            $rating->user_boutique_id = $order->user_boutique_id;
            $rating->save();

            $rating_array = [
                'id' => $rating->id,
                'ratings' => (string )$rating->ratings,
                'updated_at' =>(string)  getLocalTimeFromUtc($rating->updated_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_add_rating_success'),
                    'message_code' => 'user_add_rating_success',
                    'data' => [
                        'my_rating' => (object) $rating_array,
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

    public function userUpdateOrderRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'order_rating_id' => 'required|numeric|min:1',
                'ratings' => 'required',
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

            $rating =  OrderRating::where('id', $request->order_rating_id)->where('user_id', auth('api')->id())->firstOrFail();
            $rating->ratings = $request->ratings;
            $rating->save();

            $rating_array = [
                'id' => $rating->id,
                'ratings' => (string )$rating->ratings,
                'updated_at' =>(string)  getLocalTimeFromUtc($rating->updated_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_edit_rating_success'),
                    'message_code' => 'user_edit_rating_success',
                    'data' => [
                        'my_rating' => (object) $rating_array,
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

    public function userDeleteOrderRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'order_rating_id' => 'required|numeric|min:1',
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

            $rating = OrderRating::where('user_id', auth('api')->id())->findOrFail($request->order_rating_id);
            $rating->delete();


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_delete_rating_success'),
                    'message_code' => 'user_delete_rating_success',
                    'data' => [
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

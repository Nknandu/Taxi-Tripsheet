<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\ProductForAuctionOrSale;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserRatingController extends Controller
{
    public function userGetMyRating(Request $request)
    {
        try
        {
            $name_array = [];


            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $rating_query = Rating::query();
            $rating_query->where('user_id', auth('api')->id());
            $rating_query->where('status', 1);
            $rating_data_array = $rating_query->first();

            if($rating_data_array)
            {
                $rating_array = [
                    'id' => $rating_data_array->id,
                    'ratings' => (string) $rating_data_array->ratings,
                    'review' => (string) $rating_data_array->review,
                    'updated_at' => (string) getLocalTimeFromUtc($rating_data_array->updated_at, $time_zone)
                ];
            }
            else
            {
                $rating_array = [];
            }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_rating_success',
                    'data' => [
                        'my_rating' => $rating_array,
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

    public function userAddRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'ratings' => 'required',
                'review' => 'required|string|max:250',
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

            $rating_count = Rating::where('user_id', auth('api')->id())->where('status', 1)->count();

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

            $rating = new Rating();
            $rating->user_id = auth('api')->id();
            $rating->ratings = $request->ratings;
            $rating->review = $request->review;
            $rating->save();

            $rating_array = [
                'id' => $rating->id,
                'ratings' => (string )$rating->ratings,
                'review' => (string) $rating->review,
                'updated_at' =>(string)  getLocalTimeFromUtc($rating->updated_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_add_rating_success'),
                    'message_code' => 'user_add_rating_success',
                    'data' => [
                        'my_rating' => $rating_array,
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

    public function userUpdateRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'rating_id' => 'required|numeric|min:1',
                'ratings' => 'required',
                'review' => 'required|string|max:250',
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

            $rating =  Rating::where('id', $request->rating_id)->where('user_id', auth('api')->id())->firstOrFail();
            $rating->user_id = auth('api')->id();
            $rating->ratings = $request->ratings;
            $rating->review = $request->review;
            $rating->save();

            $rating_array = [
                'id' => $rating->id,
                'ratings' => (string )$rating->ratings,
                'review' => (string) $rating->review,
                'updated_at' =>(string)  getLocalTimeFromUtc($rating->updated_at, $time_zone)
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_edit_rating_success'),
                    'message_code' => 'user_edit_rating_success',
                    'data' => [
                        'my_rating' => $rating_array,
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

    public function userDeleteRating(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'rating_id' => 'required|numeric|min:1',
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

            $rating = Rating::where('user_id', auth('api')->id())->findOrFail($request->rating_id);
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

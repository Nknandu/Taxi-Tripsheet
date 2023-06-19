<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserPromoCodeController extends Controller
{
    public function promoCode(Request $request)
    {
        try
        {
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $time_zone = getLocalTimeZone($request->time_zone);

            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'type' => 'required',
                'sale_type' => 'required',
                'product_variant_id' => 'required_if:type,==,DirectBuy|nullable|numeric|min:1',
                'id' => 'required_if:type,==,DirectBuy',
                'quantity' => 'required_if:type,==,DirectBuy',
                'address_id' => 'nullable|numeric|min:1',
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
            $user_id = auth('api')->id();
            $promo_code = PromoCode::where('code', $request->code)->where('status', 1)
                ->where(function($query) use ($current_date_time){
                    $query->where(function($query2) use ($current_date_time){
                        $query2->where('start_time', '<', $current_date_time);
                    });
                    $query->orWhereNull('start_time');
                })
                ->first();
            if(!$promo_code)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.promo_code_not_found'),
                        'message_code' => 'promo_code_not_found',
                    ], 200);
            }
            $user_promo_code_check = DB::table('user_promo_codes')->where('user_id', $user_id)->where('promo_code_id', $promo_code->id)->first();
            if($user_promo_code_check)
            {
                DB::table('user_promo_codes')->where('user_id', $user_id)->where('promo_code_id', $promo_code->id)->delete();
                $promo_code_data = [
                    "promo_code_id" => $promo_code->id,
                    "promo_code" => (string) $promo_code->code,
                    "promo_code_title" => $promo_code->getTitle(),
                    "free_delivery" => (string) $promo_code->free_delivery,
                    "discount_type" => (string) $promo_code->discount_type,
                    "discount" => (string) $promo_code->discount,
                    "max_discount" => (string) ($promo_code->max_discount)?$promo_code->max_discount:'',
                    "start_time" => (string) ($promo_code->start_time) ? getReadableLocalTimeFromUtc($promo_code->start_time, $time_zone) : '',
                    "end_time" => (string) ($promo_code->end_time) ? getReadableLocalTimeFromUtc($promo_code->end_time, $time_zone) : '',
                ];
                $promo_code_applied = 0;
                $promo_code_removed = 1;
//                return response()->json(
//                    [
//                        'success' => true,
//                        'status' => 200,
//                        'message' =>  __('messages.promo_code_removed'),
//                        'message_code' => "promo_code_removed",
//                        'data' => [
//                            'promo_code' => $promo_code_data,
//                            'cart_count' => (string) getCartCount()
//                        ]
//                    ], 200);
            }
            else
            {
                $user_promo_code_check = DB::table('user_promo_codes')->where('user_id', $user_id)->first();
                if($user_promo_code_check)
                {
                    $user_promo_code = PromoCode::where('id', $user_promo_code_check->promo_code_id)->where('status', 1)
                        ->where(function($query) use ($current_date_time){
                            $query->where(function($query2) use ($current_date_time){
                                $query2->where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time);
                            });
                            $query->orWhereNull('start_time');
                        })->first();
                    if($user_promo_code)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.promo_code_already_applied'),
                                'message_code' => 'promo_code_already_applied',
                            ], 200);
                    }
                    else
                    {
                        DB::table('user_promo_codes')->where('user_id', $user_id)->delete();
                    }
                }
                $order_promo_codes_user_ids = Order::where('promo_code_id', $promo_code->id)->pluck('user_id')->toArray();
                $order_promo_codes_user_ids = array_unique($order_promo_codes_user_ids);
                $order_promo_codes_user_count = count($order_promo_codes_user_ids);
                $order_promo_codes_per_user_count = Order::where('promo_code_id', $promo_code->id)->where('user_id', $user_id)->count();
                if($promo_code->user_limit &&  $order_promo_codes_user_count >= $promo_code->user_limit)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.promo_code_user_limit_exceeded'),
                            'message_code' => 'promo_code_user_limit_exceeded',
                        ], 200);
                }

                if($promo_code->per_user_limit &&  $order_promo_codes_per_user_count >= $promo_code->per_user_limit)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.promo_code_per_user_limit_exceeded'),
                            'message_code' => 'promo_code_per_user_limit_exceeded',
                        ], 200);
                }

                if($promo_code->start_time && $promo_code->end_time)
                {
                    if($promo_code->start_time <= $current_date_time && $promo_code->end_time >= $current_date_time)
                    {
                        $expired = 0;
                    }
                    else
                    {
                       $expired = 1;
                    }
                }
                else
                {
                    $expired = 0;
                }
                if(!$expired)
                {
                    DB::table('user_promo_codes')->insert([
                        'user_id' => $user_id,
                        'promo_code_id' => $promo_code->id,
                        'created_at' => $current_date_time,
                        'updated_at' => $current_date_time,
                    ]);
                    $promo_code_applied = 1;
                    $promo_code_removed = 0;
//                    $promo_code_data = [
//                        "promo_code_id" => $promo_code->id,
//                        "promo_code" => (string) $promo_code->code,
//                        "promo_code_title" => $promo_code->getTitle(),
//                        "free_delivery" => (string) $promo_code->free_delivery,
//                        "discount_type" => (string) $promo_code->discount_type,
//                        "discount" => (string) $promo_code->discount,
//                        "max_discount" => (string) ($promo_code->max_discount)?$promo_code->max_discount:'',
//                        "start_time" => (string) ($promo_code->start_time) ? getReadableLocalTimeFromUtc($promo_code->start_time, $time_zone) : '',
//                        "end_time" => (string) ($promo_code->end_time) ? getReadableLocalTimeFromUtc($promo_code->end_time, $time_zone) : '',
//                    ];
//                    return response()->json(
//                        [
//                            'success' => true,
//                            'status' => 200,
//                            'message' =>  __('messages.promo_code_applied'),
//                            'message_code' => "promo_code_applied",
//                            'data' => [
//                                'promo_code' => $promo_code_data,
//                                'cart_count' => (string) getCartCount()
//                            ]
//                        ], 200);

                }
                else
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.promo_code_expired'),
                            'message_code' => 'promo_code_expired',
                        ], 200);
                }

            }
            return checkStockItems($request, $promo_code_applied, $promo_code_removed);
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

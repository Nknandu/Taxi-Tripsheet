<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BiddingHistory;
use App\Models\BiddingSummary;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserBiddingController extends Controller
{
    public function userMakeBid(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'product_for_auction_or_sale_id' => 'required',
                'product_variant_id' => 'required',
              //  'is_auto_bid' => 'numeric|min:0|max:1',
                'bid_amount' => 'required',
                'max_bid_amount' => 'required_if:is_auto_bid,==,1|min:0',
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

            $product_variant_id = $request->product_variant_id;
            $product_variant = ProductVariant::where('id', $product_variant_id)->first();
            $product = $product_variant->product;

            $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $request->product_for_auction_or_sale_id)->where('type', 'Auction')->firstOrFail();

            if(($product_for_auction_or_sale->bid_start_time >= $current_date_time) || ($product_for_auction_or_sale->bid_end_time <= $current_date_time))
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' =>  __('messages.bidding_time_not_valid'),
                        'message_code' => 'bidding_time_not_valid',
                    ], 200);
            }

            $latest_bid_amount = (BiddingSummary::where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant->id)->max('current_bid_amount'))?BiddingSummary::where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->max('current_bid_amount'):$product_variant->bid_start_price;
            $next_bid_amount = $latest_bid_amount + $product_variant->bid_value;

            if(isset($request->bid_amount) && $request->bid_amount)
            {
                if( ((double) $request->bid_amount < $product_variant->bid_start_price))
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' =>  __('messages.bid_amount_must_be_grater_than_bid_start_price'),
                            'message_code' => 'bid_amount_must_be_grater_than_bid_start_price',
                        ], 200);
                }
                $next_bid_amount = (double) $request->bid_amount;
            }

            if(isset($request->bid_amount) && $request->bid_amount)
            {
                if( ((double) $request->bid_amount % $product_variant->bid_value) !=0)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' =>  __('messages.please_provide_a_bid_amount_which_is_a_multiple_of').' '.__('messages.kwd').' '.$product_variant->bid_value,
                            'message_code' => 'please_provide_a_bid_amount_which_is_a_multiple_of',
                        ], 200);
                }
                $next_bid_amount = (double) $request->bid_amount;
            }

            if(isset($request->bid_amount) && $request->bid_amount)
            {
                if( (double) $request->bid_amount < $next_bid_amount)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' =>  __('messages.bid_amount_must_be_grater_than_current_bid_amount'),
                            'message_code' => 'bid_amount_must_be_grater_than_current_bid_amount',
                        ], 200);
                }
                $next_bid_amount = (double) $request->bid_amount;
            }


            $is_auto_bid = 0;
            if(isset($request->is_auto_bid) && $request->is_auto_bid)
            {
                $is_auto_bid = 1;
                $max_bid_amount = 0;
                if(isset($request->max_bid_amount) && $request->max_bid_amount)
                {
                    if((double) $request->max_bid_amount <= $product_variant->bid_start_price)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' =>  __('messages.max_bid_amount_validation'),
                                'message_code' => 'max_bid_amount_validation',
                            ], 200);
                    }
                    $max_bid_amount = (double) $request->max_bid_amount;
                }
            }

            $bidding_summary = BiddingSummary::where('user_id', auth('api')->id())->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant->id)->first();
            if($bidding_summary)
            {
                $bidding_summary->current_bid_amount = $next_bid_amount;
                if($is_auto_bid)
                {
                    $bidding_summary->is_auto_bid = true;
                    $bidding_summary->max_bid_amount = $max_bid_amount;
                }
                $bidding_summary->save();
            }
            else
            {
                $bidding_summary = new BiddingSummary();
                $bidding_summary->user_id = auth('api')->id();
                $bidding_summary->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
                $bidding_summary->product_variant_id = $product_variant->id;
                $bidding_summary->bid_start_price = $product_variant->bid_start_price;
                $bidding_summary->bid_value = $product_variant->bid_value;
                $bidding_summary->current_bid_amount = $next_bid_amount;
                if($is_auto_bid)
                {
                    $bidding_summary->is_auto_bid = true;
                    $bidding_summary->max_bid_amount = $max_bid_amount;
                }
                $bidding_summary->save();
            }

            $bidding_history = new BiddingHistory();
            $bidding_history->user_id = auth('api')->id();
            $bidding_history->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
            $bidding_history->product_variant_id = $product_variant->id;
            $bidding_history->bid_start_price = $product_variant->bid_start_price;
            $bidding_history->bid_value = $product_variant->bid_value;
            $bidding_history->current_bid_amount = $next_bid_amount;
            $bidding_history->save();

            // for auto bid enabled users
            autoBidUpdate($product_for_auction_or_sale->id, $product_variant->id, auth('api')->id());

            $winner_user_id = 0;
            if($product_for_auction_or_sale->bid_end_time < $current_date_time)
            {
                $winner_bid = BiddingSummary::where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant->id)->orderBy('current_bid_amount', 'DESC')->first();
                if($winner_bid)$winner_user_id = $winner_bid->user_id;
            }

            $bidding_history_query = BiddingSummary::query();
            $bidding_history_query->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id);
            $bidding_history_query->where('product_variant_id', $product_variant->id);
            $bidding_history_query->orderBy('current_bid_amount', 'DESC');
            $bidding_history_count_query = clone $bidding_history_query;
            $bidding_count = $bidding_history_count_query->count();
            if(empty($request->get('page'))) $per_page = $bidding_count;
            $bidding_histories_data_array = $bidding_history_query->limit(10)->get();
            $bidding_histories_data = [];
            foreach ($bidding_histories_data_array as $bidding_history)
            {
                if($bidding_history->user->id == auth('api')->id())
                {
//                    $first_name = __('messages.your_bid');
//                    $last_name = "";
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 1;
                }
                else
                {
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 0;
                }

                $is_winner = 0;
                $is_ready_to_checkout = 0;
                if($bidding_history->user->id == $winner_user_id)
                {
                    $is_winner = 1;
                    if(!$product_for_auction_or_sale->bid_purchase_status)
                    {
                        $is_ready_to_checkout = 1;
                    }
                    $checkout_expiry_time = date('Y-m-d H:i:s', strtotime($product_for_auction_or_sale->bid_end_date.'+1 hours'));
                    if($checkout_expiry_time < $current_date_time)
                    {
                        $is_ready_to_checkout = 0;
                    }
                }

                $bidding_histories_data_temp = [
                    'id' => $bidding_history->id,
                    'user_first_name' => (string) $first_name,
                    'user_last_name' => (string) $last_name,
                    'profile_image_thumb' => (string) $bidding_history->user->thumb_image,
                    'profile_image_original' => (string) $bidding_history->user->original_image,
                    'bid_amount' => (string) $bidding_history->current_bid_amount,
                    'is_own_bid' => (string) $is_own_bid,
                    'is_winner' => (string) $is_winner,
                    'is_ready_to_checkout' => (string) $is_ready_to_checkout,
                ];
                array_push($bidding_histories_data, $bidding_histories_data_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.make_a_bid_success'),
                    'message_code' => 'make_a_bid_success',
                    'data' => [
                        "bid_lists" => $bidding_histories_data,
                        "total_bidding_count" => (string) $bidding_count,
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
    public function userBiddingDetails(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'product_for_auction_or_sale_id' => 'required',
                'product_variant_id' => 'required',
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

            $product_variant_id = $request->product_variant_id;
            $product_variant = ProductVariant::where('id', $product_variant_id)->firstOrFail();
            $product = $product_variant->product;
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $request->product_for_auction_or_sale_id)->where('type', 'Auction')->firstOrFail();

            $products_auction_detail_query = ProductForAuctionOrSale::query();
            $products_auction_detail_query->where('status', 1);
            $products_auction_detail_query->where('id', $product_for_auction_or_sale->id);
            $products_auction_detail_query->where('type', 'Auction');
            $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $product_variant_id) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                //$query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                $query_9->with([
                    'product_inventory' => function($q) use ($product_variant_id) {
                        $q->where('id','=',$product_variant_id);
                        $q->with('default_image', 'default_video');
                    }
                ]);
            }]);

//            $products_auction_detail_query->whereHas('product', function ($query_2) use($current_date_time, $product_variant_id) {
//                $query_2->whereHas('product_inventory', function ($query_10) use($product_variant_id) {
//                    $query_10->where('product_variants.id', $product_variant_id);
//                });
//            });

            $products_auction_detail_query->orderBy('id', 'DESC');
            $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

            $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

            $winner_user_id = 0;
            if($product_for_auction_or_sale->bid_end_time < $current_date_time)
            {
                $winner_bid = BiddingSummary::where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant->id)->orderBy('current_bid_amount', 'DESC')->first();
                if($winner_bid)$winner_user_id = $winner_bid->user_id;
            }

            $bidding_history_query = BiddingSummary::query();
            $bidding_history_query->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id);
            $bidding_history_query->where('product_variant_id', $product_variant->id);
            $bidding_history_query->orderBy('current_bid_amount', 'DESC');
            $bidding_history_count_query = clone $bidding_history_query;
            $bidding_count = $bidding_history_count_query->count();
            if(empty($request->get('page'))) $per_page = $bidding_count;
            $bidding_histories_data_array = $bidding_history_query->limit(10)->get();
            $bidding_histories_data = [];
            foreach ($bidding_histories_data_array as $bidding_history)
            {
                if($bidding_history->user->id == auth('api')->id())
                {
//                    $first_name = __('messages.your_bid');
//                    $last_name = "";
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 1;
                }
                else
                {
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 0;
                }

                $is_winner = 0;
                $is_ready_to_checkout = 0;
                if($bidding_history->user->id == $winner_user_id)
                {
                    $is_winner = 1;
                    if(!$product_for_auction_or_sale->bid_purchase_status)
                    {
                        $is_ready_to_checkout = 1;
                        $checkout_expiry_time_in_hours = getCheckoutExpiryInHr();
                        $checkout_expiry_time = date('Y-m-d H:i:s', strtotime($product_for_auction_or_sale->bid_end_date.'+'.$checkout_expiry_time_in_hours.' hours'));
                        if($checkout_expiry_time < $current_date_time)
                        {
                            $is_ready_to_checkout = 0;
                        }
                    }
                }

                $bidding_histories_data_temp = [
                    'id' => $bidding_history->id,
                    'user_first_name' => (string) $first_name,
                    'user_last_name' => (string) $last_name,
                    'profile_image_thumb' => (string) $bidding_history->user->thumb_image,
                    'profile_image_original' => (string) $bidding_history->user->original_image,
                    'bid_amount' => (string) $bidding_history->current_bid_amount,
                    'is_own_bid' => (string) $is_own_bid,
                    'is_winner' => (string) $is_winner,
                    'is_ready_to_checkout' => (string) $is_ready_to_checkout,
                ];
                array_push($bidding_histories_data, $bidding_histories_data_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.bid_details_success'),
                    'message_code' => 'bid_details_success',
                    'data' => [
                        "bidding_detail" => $products_auction_detail_data[0],
                        "bid_lists" => $bidding_histories_data,
                        "total_bidding_count" => (string) $bidding_count,
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

    public function getUserBidMembers(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'product_for_auction_or_sale_id' => 'required',
                'product_variant_id' => 'required',
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

            $product_variant_id = $request->product_variant_id;
            $product_variant = ProductVariant::where('id', $product_variant_id)->firstOrFail();
            $product = $product_variant->product;
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $request->product_for_auction_or_sale_id)->where('type', 'Auction')->firstOrFail();

            $winner_user_id = 0;
            if($product_for_auction_or_sale->bid_end_time < $current_date_time)
            {
                $winner_bid = BiddingSummary::where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant->id)->orderBy('current_bid_amount', 'DESC')->first();
                if($winner_bid)$winner_user_id = $winner_bid->user_id;
            }

            $bidding_history_query = BiddingSummary::query();
            $bidding_history_query->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id);
            $bidding_history_query->where('product_variant_id', $product_variant->id);
            $bidding_history_query->orderBy('current_bid_amount', 'DESC');
            $bidding_history_count_query = clone $bidding_history_query;
            $bidding_count = $bidding_history_count_query->count();
            if(empty($request->get('page'))) $per_page = $bidding_count;
            $bidding_histories_data_array = $bidding_history_query->paginate($per_page);
            $bidding_histories_data = [];
            foreach ($bidding_histories_data_array->items() as $bidding_history)
            {
                if($bidding_history->user->id == auth('api')->id())
                {
//                    $first_name = __('messages.your_bid');
//                    $last_name = "";
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 1;
                }
                else
                {
                    $first_name = $bidding_history->user->first_name;
                    $last_name = $bidding_history->user->last_name;
                    $is_own_bid = 0;
                }

                $is_winner = 0;
                $is_ready_to_checkout = 0;
                if($bidding_history->user->id == $winner_user_id)
                {
                    $is_winner = 1;
                    if(!$product_for_auction_or_sale->bid_purchase_status)
                    {
                        $is_ready_to_checkout = 1;
                        $checkout_expiry_time = date('Y-m-d H:i:s', strtotime($product_for_auction_or_sale->bid_end_date.'+1 hours'));
                        if($checkout_expiry_time < $current_date_time)
                        {
                            $is_ready_to_checkout = 0;
                        }
                    }
                }

                $bidding_histories_data_temp = [
                    'id' => $bidding_history->id,
                    'user_first_name' => (string) $first_name,
                    'user_last_name' => (string) $last_name,
                    'profile_image_thumb' => (string) $bidding_history->user->thumb_image,
                    'profile_image_original' => (string) $bidding_history->user->original_image,
                    'bid_amount' => (string) $bidding_history->current_bid_amount,
                    'is_own_bid' => (string) $is_own_bid,
                    'is_winner' => (string) $is_winner,
                    'is_ready_to_checkout' => (string) $is_ready_to_checkout,
                ];
                array_push($bidding_histories_data, $bidding_histories_data_temp);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' =>  __('messages.bid_details_success'),
                    'message_code' => 'bid_details_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $bidding_histories_data_array->lastPage(),
                            'current_page' => (string) $bidding_histories_data_array->currentPage(),
                            'total_records' => (string) $bidding_histories_data_array->total(),
                            'records_on_current_page' => (string) $bidding_histories_data_array->count(),
                            'record_from' => (string) $bidding_histories_data_array->firstItem(),
                            'record_to' => (string) $bidding_histories_data_array->lastItem(),
                            'per_page' => (string) $bidding_histories_data_array->perPage(),
                        ],
                        "bid_lists" => $bidding_histories_data,
                        "total_bidding_count" => (string) $bidding_count,
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

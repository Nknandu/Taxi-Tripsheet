<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BiddingSummary;
use App\Models\Cart;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\ReservedStock;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserCheckoutController extends Controller
{
    public function userProceedToCheckout(Request $request)
    {
        try {
            if (app()->getLocale() == 'ar') {
                $name_array['name'] = "name_ar as name";
            } else {
                $name_array['name'] = "name";
            }
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'sale_type' => 'required',
                'address_id' => 'required|numeric|min:1',
                'product_variant_id' => 'required_if:type,==,DirectBuy|nullable|numeric|min:1',
                'id' => 'required_if:type,==,DirectBuy|nullable|numeric|min:1',
                'quantity' => 'required_if:type,==,DirectBuy',
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
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);

            $product_for_auction_or_sale_id = 0;
            if(isset($request->id) && $request->id) $product_for_auction_or_sale_id = $request->id;
            if(isset($request->product_for_auction_or_sale_id) && $request->product_for_auction_or_sale_id) $product_for_auction_or_sale_id = $request->product_for_auction_or_sale_id;

            if (isset($request->type) && ($request->type == "Cart"))
            {
               if ($request->sale_type == "Sale")
                {
                    $cart_item_query = Cart::query();
                    $cart_item_query->where('user_id', auth('api')->id());
                    $cart_item_count_query = clone $cart_item_query;
                    $cart_item_all_query = clone $cart_item_query;
                    $cart_item_count = $cart_item_count_query->count();
                    if (empty($request->get('page'))) $per_page = $cart_item_count;
                    $cart_item_data = $cart_item_query->paginate($per_page);
                    $cart_data = cartData($cart_item_data->items()); // defined in helpers

                    if(!$cart_item_count)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.cart_is_empty'),
                                'message_code' => 'cart_is_empty',
                            ], 200);
                    }

                    $cart_summary = [];
                    $final_price = 0;
                    $regular_price = 0;
                    $discount = 0;
                    $in_sufficient_stock = [];
                    foreach ($cart_item_all_query->get() as $cart_item)
                    {
                        if ($cart_item->sale_type == 'Sale')
                        {
                            $is_available = 1;
                            if ($cart_item->sale_type != $cart_item->product->sale_type)
                            {
                                $is_available = 0;
                                Cart::where('id', $cart_item->id)->delete();
                            }
                            $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                            $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                            $regular_price = $regular_price + $regular_price_temp;
                            $final_price = $final_price + $final_price_temp;
                            $discount_temp = $regular_price_temp - $final_price_temp;
                            $discount = $discount + $discount_temp;
                            if ($cart_item->product_variant->availableStockQuantity() < $cart_item->quantity)
                            {
                                $in_sufficient_stock_temp = [
                                    "id" => $cart_item->product_for_auction_or_sale->id,
                                    "cart_id" => $cart_item->id,
                                    "product_id" => $cart_item->product->id,
                                    "product_variant_id" => $cart_item->product_variant->id,
                                    "name" => (string)$cart_item->product->getProductName(),
                                    "is_in_stock" => "0",
                                    "is_in_stock_text" => __('messages.insufficient_stock'),
                                ];
                                array_push($in_sufficient_stock, $in_sufficient_stock_temp);
                                Cart::where('id', $cart_item->id)->delete();
                            }
                        }
                        elseif($cart_item->sale_type == 'Bulk')
                        {
                            if($cart_item->sale_type != $cart_item->product->sale_type)
                            {
                                $is_available = 0;
                                Cart::where('id', $cart_item->id)->delete();
                            }
                            $actual_quantity = 0;
                            $initial_quantity = 0;
                            $number_of_increments = 0;
                            if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) >= 0)
                            {
                                $initial_quantity =  $cart_item->product_variant->getInitialQuantity();
                                if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) > 0)
                                {
                                    $balance_qty = $cart_item->quantity - $cart_item->product_variant->getInitialQuantity();
                                    if($balance_qty % $cart_item->product_variant->getIncrementalQuantity() == 0)
                                    {
                                        $number_of_increments = $balance_qty / $cart_item->product_variant->getIncrementalQuantity();
                                    }
                                    else
                                    {
                                        return response()->json(
                                            [
                                                'success' => false,
                                                'status' => 400,
                                                'message' =>  __('messages.bulk_quantity_not_valid'),
                                                'message_code' => 'bulk_quantity_not_valid',
                                            ], 200);
                                    }
                                }
                            }
                            else
                            {
                                return response()->json(
                                    [
                                        'success' => false,
                                        'status' => 400,
                                        'message' =>  __('messages.bulk_quantity_not_valid'),
                                        'message_code' => 'bulk_quantity_not_valid',
                                    ], 200);
                            }

                            if($initial_quantity)
                            {
                                $final_price_temp = $cart_item->product_variant->getFinalPrice();
                                $regular_price_temp = $cart_item->product_variant->getRegularPrice();
                                $regular_price = $regular_price + $regular_price_temp;
                                $final_price = $final_price + $final_price_temp;
                                $discount_temp = $regular_price_temp - $final_price_temp;
                                $discount = $discount + $discount_temp;
                            }

                            if($number_of_increments)
                            {
                                for($i=1; $i<=$number_of_increments; $i++)
                                {
                                    $final_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price = $regular_price + $regular_price_temp;
                                    $final_price = $final_price + $final_price_temp;
                                    $discount_temp = $regular_price_temp - $final_price_temp;
                                    $discount = $discount + $discount_temp;
                                }
                            }


                            if($cart_item->product_variant->availableStockQuantity() < $actual_quantity)
                            {
                                $in_sufficient_stock_temp = [
                                    "id" => $cart_item->product_for_auction_or_sale->id,
                                    "cart_id" => $cart_item->id,
                                    "product_id" => $cart_item->product->id,
                                    "product_variant_id" => $cart_item->product_variant->id,
                                    "name" => (string) $cart_item->product->getProductName(),
                                    "is_in_stock" => "0",
                                    "is_in_stock_text" => __('messages.insufficient_stock'),
                                ];
                                array_push($in_sufficient_stock, $in_sufficient_stock_temp);
                                Cart::where('id', $cart_item->id)->delete();
                            }

                        }


                    }
                    $cart_item_query = Cart::query();
                    $cart_item_query->where('user_id', auth('api')->id());
                    $cart_item_count_query = clone $cart_item_query;
                    $cart_item_all_query = clone $cart_item_query;
                    $cart_item_count = $cart_item_count_query->count();
                    if (empty($request->get('page'))) $per_page = $cart_item_count;
                    $cart_item_data = $cart_item_query->paginate($per_page);
                    $cart_data = cartData($cart_item_data->items()); // defined in helpers

                    if(!$cart_item_count)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.cart_is_empty'),
                                'message_code' => 'cart_is_empty',
                            ], 200);
                    }

                    $cart_summary = [];
                    $final_price = 0;
                    $regular_price = 0;
                    $discount = 0;
                    $in_sufficient_stock = [];

                    foreach ($cart_item_all_query->get() as $cart_item)
                    {
                        if ($cart_item->sale_type == 'Sale')
                        {
                            $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                            $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                            $regular_price = $regular_price + $regular_price_temp;
                            $final_price = $final_price + $final_price_temp;
                            $discount_temp = $regular_price_temp - $final_price_temp;
                            $discount = $discount + $discount_temp;
                        }
                        elseif($cart_item->sale_type == 'Bulk')
                        {
                            $actual_quantity = 0;
                            $initial_quantity = 0;
                            $number_of_increments = 0;
                            if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) >= 0)
                            {
                                $initial_quantity =  $cart_item->product_variant->getInitialQuantity();
                                if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) > 0)
                                {
                                    $balance_qty = $cart_item->quantity - $cart_item->product_variant->getInitialQuantity();
                                    if($balance_qty % $cart_item->product_variant->getIncrementalQuantity() == 0)
                                    {
                                        $number_of_increments = $balance_qty / $cart_item->product_variant->getIncrementalQuantity();
                                    }
                                    else
                                    {
                                        return response()->json(
                                            [
                                                'success' => false,
                                                'status' => 400,
                                                'message' =>  __('messages.bulk_quantity_not_valid'),
                                                'message_code' => 'bulk_quantity_not_valid',
                                            ], 200);
                                    }
                                }
                            }
                            else
                            {
                                return response()->json(
                                    [
                                        'success' => false,
                                        'status' => 400,
                                        'message' =>  __('messages.bulk_quantity_not_valid'),
                                        'message_code' => 'bulk_quantity_not_valid',
                                    ], 200);
                            }

                            if($initial_quantity)
                            {
                                $final_price_temp = $cart_item->product_variant->getFinalPrice();
                                $regular_price_temp = $cart_item->product_variant->getRegularPrice();
                                $regular_price = $regular_price + $regular_price_temp;
                                $final_price = $final_price + $final_price_temp;
                                $discount_temp = $regular_price_temp - $final_price_temp;
                                $discount = $discount + $discount_temp;
                                $actual_quantity = $actual_quantity + $cart_item->product_variant->getInitialQuantity();
                            }

                            if($number_of_increments)
                            {
                                for($i=1; $i<=$number_of_increments; $i++)
                                {
                                    $final_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price = $regular_price + $regular_price_temp;
                                    $final_price = $final_price + $final_price_temp;
                                    $discount_temp = $regular_price_temp - $final_price_temp;
                                    $discount = $discount + $discount_temp;
                                    $actual_quantity = $actual_quantity + $cart_item->product_variant->getIncrementalQuantity();
                                }
                            }
                        }
                    }
                    //Process Order


                    $payment_method = PaymentMethod::where('id', $request->payment_method)->firstOrFail();
                    $address = Address::where('user_id', auth('api')->id())->where('id', $request->address_id)->firstOrFail();
                    $user_boutique = $cart_item->product->boutique;
                    $vendor = $cart_item->product->vendor;

                    $delivery_charge = getDeliveryCharge($user_boutique->id, $address->area_id);
                    $convenience_fees = 0;
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    $promo_code_discount = $promo_code_details['promo_code_discount'];
                    $delivery_charge = $promo_code_details['delivery_charge'];
                    $promo_code_data = $promo_code_details['promo_code_data'];
                    $promo_code_id = NULL;
                    $promo_discount_amount = 0;
                    $promo_free_delivery = 0;
                    if($promo_code_details['status'])
                    {
                        $promo_code_id = $promo_code_details['promo_code_data']->promo_code_id;
                        $promo_discount_amount = (double) $promo_code_discount;
                        $promo_free_delivery = (integer) $promo_code_details['promo_code_data']->free_delivery;
                    }
                    $grand_total = ($final_price + $delivery_charge + $convenience_fees) - $promo_code_discount;

                    $commission_percentage = $user_boutique->commission_percentage;
                    $commission_amount = $final_price * ($commission_percentage / 100);
                    DB::beginTransaction();

                    $order = new Order();
                    $order->user_id = auth('api')->id();
                    $order->user_boutique_id = $user_boutique->id;
                    $order->vendor_id = $user_boutique->user_id;
                    $order->sale_type = "Sale";
                    $order->checkout_type = "Cart";
                    $order->product_price = $final_price;
                    $order->delivery_charges = $delivery_charge;
                    $order->promo_code_id = $promo_code_id;
                    $order->promo_discount_amount = $promo_code_discount;
                    $order->discount_amount = 0;
                    $order->promo_free_delivery = $promo_free_delivery;
                    $order->commission_percentage = $commission_percentage;
                    $order->commission_amount = $commission_amount;
                    $order->total_amount = $grand_total;
                    $order->total_items = $cart_item_count;
                    $order->order_status = "Pending";
                    $order->delivery_status = "Pending";
                    $order->payment_status = "INITIATED";
                    $order->payment_method_id = $request->payment_method;
                    $order->delivery_status_updated_on = $current_date_time;
                    $order->payment_status_updated_on = $current_date_time;
                    $order->order_status_updated_on = $current_date_time;
                    if(isset($request->device_token) && $request->device_token)$order->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$order->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$order->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$order->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$order->os_version = $request->os_version;
                    $order->save();
                    $order_id = getOrderId($order->id);
                    Order::where('id', $order->id)->update(['order_id' => $order_id]);

                    $order_address = new OrderAddress();
                    $order_address->user_id = auth('api')->id();
                    $order_address->order_id = $order->id;
                    $order_address->country_id = $address->country_id;
                    $order_address->governorate_id = $address->governorate_id;
                    $order_address->area_id = $address->area_id;
                    $order_address->first_name = $address->first_name;
                    $order_address->last_name = $address->last_name;
                    $order_address->contact_number = $address->contact_number;
                    $order_address->avenue = $address->avenue;
                    $order_address->block = $address->block;
                    $order_address->street = $address->street;
                    $order_address->building = $address->building;
                    $order_address->floor = $address->floor;
                    $order_address->apartment = $address->apartment;
                    $order_address->pin_code = $address->pin_code;
                    $order_address->notes = $address->notes;
                    $order_address->save();

                    $transaction = new Transaction();
                    $transaction->user_id = auth('api')->id();
                    $transaction->order_id = $order->id;
                    $transaction->payment_status = "INITIATED";
                    $transaction->payment_method_id = $payment_method->id;
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->save();
                    $transaction_id = getOrderId($transaction->id);
                    Transaction::where('id', $transaction->id)->update(['transaction_id' => $transaction_id]);


                    foreach ($cart_item_all_query->get() as $cart_item)
                    {
                        $final_price = 0;
                        $regular_price = 0;
                        $discount = 0;
                        if ($cart_item->sale_type == 'Sale')
                        {
                            if ($cart_item->product_variant->availableStockQuantity() < $cart_item->quantity)
                            {
                                return response()->json(
                                    [
                                        'success' => false,
                                        'status' => 400,
                                        'message' => __('messages.order_failed_due_to_in_sufficient_stock'),
                                        'message_code' => 'order_failed_due_to_in_sufficient_stock',
                                    ], 200);
                            }
                            $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                            $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                            $regular_price = $regular_price + $regular_price_temp;
                            $final_price = $final_price + $final_price_temp;
                            $discount_temp = $regular_price_temp - $final_price_temp;
                            $discount = $discount + $discount_temp;

                            $reserved_stock = new ReservedStock();
                            $reserved_stock->stock_id = $cart_item->product_variant->stock->id;
                            $reserved_stock->user_id = auth('api')->id();
                            $reserved_stock->user_boutique_id = $user_boutique->id;
                            $reserved_stock->order_id = $order->id;
                            $reserved_stock->product_variant_id = $cart_item->product_variant->id;
                            $reserved_stock->quantity = $cart_item->quantity;
                            $reserved_stock->status = 1;
                            $reserved_stock->save();

                            $order_detail = new OrderDetail();
                            $order_detail->order_id = $order->id;
                            $order_detail->user_id = auth('api')->id();
                            $order_detail->vendor_id = $vendor->id;
                            $order_detail->user_boutique_id = $user_boutique->id;
                            $order_detail->product_for_auction_or_sale_id = $cart_item->product_for_auction_or_sale_id;
                            $order_detail->product_variant_id = $cart_item->product_variant_id;
                            $order_detail->sale_type = "Sale";
                            $order_detail->final_price = $cart_item->product_variant->getFinalPrice();
                            $order_detail->regular_price = $cart_item->product_variant->getRegularPrice();
                            $order_detail->quantity = $cart_item->quantity;
                            $order_detail->actual_quantity = $cart_item->quantity;
                            $order_detail->discount_amount = $discount;
                            $order_detail->save();

                        }
                        elseif($cart_item->sale_type == 'Bulk')
                        {
                            $actual_quantity = 0;
                            $initial_quantity = 0;
                            $number_of_increments = 0;
                            if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) >= 0)
                            {
                                $initial_quantity =  $cart_item->product_variant->getInitialQuantity();
                                if(($cart_item->quantity - $cart_item->product_variant->getInitialQuantity()) > 0)
                                {
                                    $balance_qty = $cart_item->quantity - $cart_item->product_variant->getInitialQuantity();
                                    if($balance_qty % $cart_item->product_variant->getIncrementalQuantity() == 0)
                                    {
                                        $number_of_increments = $balance_qty / $cart_item->product_variant->getIncrementalQuantity();
                                    }
                                    else
                                    {
                                        return response()->json(
                                            [
                                                'success' => false,
                                                'status' => 400,
                                                'message' =>  __('messages.bulk_quantity_not_valid'),
                                                'message_code' => 'bulk_quantity_not_valid',
                                            ], 200);
                                    }
                                }
                            }
                            else
                            {
                                return response()->json(
                                    [
                                        'success' => false,
                                        'status' => 400,
                                        'message' =>  __('messages.bulk_quantity_not_valid'),
                                        'message_code' => 'bulk_quantity_not_valid',
                                    ], 200);
                            }

                            if($initial_quantity)
                            {
                                $final_price_temp = $cart_item->product_variant->getFinalPrice();
                                $regular_price_temp = $cart_item->product_variant->getRegularPrice();
                                $regular_price = $regular_price + $regular_price_temp;
                                $final_price = $final_price + $final_price_temp;
                                $discount_temp = $regular_price_temp - $final_price_temp;
                                $discount = $discount + $discount_temp;
                                $actual_quantity = $actual_quantity + $cart_item->product_variant->getInitialQuantity();
                            }

                            if($number_of_increments)
                            {
                                for($i=1; $i<=$number_of_increments; $i++)
                                {
                                    $final_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price_temp = $cart_item->product_variant->getIncrementalPrice();
                                    $regular_price = $regular_price + $regular_price_temp;
                                    $final_price = $final_price + $final_price_temp;
                                    $discount_temp = $regular_price_temp - $final_price_temp;
                                    $discount = $discount + $discount_temp;
                                    $actual_quantity = $actual_quantity + $cart_item->product_variant->getIncrementalQuantity();
                                }
                            }

                            if ($cart_item->product_variant->availableStockQuantity() < $actual_quantity)
                            {
                                return response()->json(
                                    [
                                        'success' => false,
                                        'status' => 400,
                                        'message' => __('messages.order_failed_due_to_in_sufficient_stock'),
                                        'message_code' => 'order_failed_due_to_in_sufficient_stock',
                                    ], 200);
                            }

                            $reserved_stock = new ReservedStock();
                            $reserved_stock->stock_id = $cart_item->product_variant->stock->id;
                            $reserved_stock->user_id = auth('api')->id();
                            $reserved_stock->user_boutique_id = $user_boutique->id;
                            $reserved_stock->order_id = $order->id;
                            $reserved_stock->product_variant_id = $cart_item->product_variant->id;
                            $reserved_stock->quantity = $actual_quantity;
                            $reserved_stock->status = 1;
                            $reserved_stock->save();

                            $order_detail = new OrderDetail();
                            $order_detail->order_id = $order->id;
                            $order_detail->user_id = auth('api')->id();
                            $order_detail->vendor_id = $vendor->id;
                            $order_detail->user_boutique_id = $user_boutique->id;
                            $order_detail->product_for_auction_or_sale_id = $cart_item->product_for_auction_or_sale_id;
                            $order_detail->product_variant_id = $cart_item->product_variant_id;
                            $order_detail->sale_type = "Bulk";
                            $order_detail->final_price = $final_price;
                            $order_detail->regular_price = $regular_price;
                            $order_detail->quantity = $cart_item->quantity;
                            $order_detail->actual_quantity = $actual_quantity;
                            $order_detail->discount_amount = $discount;
                            $order_detail->save();

                        }

                    }

                    $payment_gateway_status = 0;
                    $payment_gateway_url = "";
                    if ($request->payment_method == 3 || $request->payment_method == 4) // COD
                    {
                        $current_date_time = Carbon::now('UTC')->toDateTimeString();
                        Order::where('id', $order->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        Transaction::where('id', $transaction->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        $product_for_auction_or_sale_ids = Cart::where('user_id', auth('api')->id())->pluck('product_for_auction_or_sale_id')->toArray();
                        foreach ($product_for_auction_or_sale_ids as $pro_id)
                        {
                            $sales_count = ProductForAuctionOrSale::where('id', $pro_id)->first()->sales_count + 1;
                            ProductForAuctionOrSale::where('id', $pro_id)->update(['sales_count' => $sales_count, 'updated_at' => $current_date_time]);
                        }
                        Cart::where('user_id', auth('api')->id())->delete();
                        DB::table('user_promo_codes')->where('user_id', auth('api')->id())->delete();
                        sendOrderInvoice($order->id);
                        sendOrderInvoiceVendor($order->id);

                        $push_title_en = "You Got A New Order";
                        $push_message_en = "You Got A New Order";
                        $push_title_ar = "You Got A New Order";
                        $push_message_ar = "You Got A New Order";
                        $push_target = "Vendor";
                        $user_ids = User::where('id', $order->vendor_id)->pluck('id')->toArray();
                        $headingData = [
                            "en" => $push_title_en,
                            "ar" => $push_title_ar,
                        ];

                        $contentData = [
                            "en" => $push_message_en,
                            "ar" => $push_message_ar,
                        ];

                        $pushData = [
                            "name_en" => $push_title_en,
                            "name_ar" => $push_title_ar,
                            "message_en" =>$push_message_en,
                            "message_ar" => $push_message_ar,
                            "target" => "",
                            "target_id" => "",
                        ];
                        sendPushNotifications($push_target, $user_ids, $headingData, $contentData, $pushData);
                    }
                    elseif ($request->payment_method == 1 || $request->payment_method == 2) // K Net // master card
                    {

//                        $country = Country::where('id', $order_address->country_id)->select('name', 'country_code')->firstOrFail();
//                        $governorate = Governorate::where('id', $order_address->governorate_id)->select('name')->firstOrFail();
//                        $area = Governorate::where('id', $order_address->area_id)->select('name')->firstOrFail();

                        $redirect_url = env('APP_URL')."/proceed-to-payment";
                        $post_url = env('APP_URL')."/payment-error";
                        if($request->payment_method == 1)
                        {
                            $source = "src_kw.knet";
                        }
                        else
                        {
                            $source = "src_card";
                        }


                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.tap.company/v2/charges',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{"amount":'.$order->total_amount.',"currency":"KWD","threeDSecure":true,"save_card":false,"description":"Product Purchase (Beiaat)","statement_descriptor":"Sample","metadata":{"udf1":"Beiaat Purchase","udf2":"Beiaat Purchase"},"reference":{"transaction":"'.$transaction->transaction_id.'","order":"'.$order->order_id.'"},"receipt":{"email":false,"sms":true},"customer":{"first_name":"'.$order_address->first_name.'","middle_name":" ","last_name":"'.$order_address->last_name.'","email":"'.auth('api')->user()->email.'","phone":{"country_code":"965","number":"'.auth('api')->user()->mobile_number.'"}},"merchant":{"id":""},"source":{"id":"'.$source.'"},"post":{"url":"'.$post_url.'"},"redirect":{"url":"'.$redirect_url.'"}}',
                            CURLOPT_HTTPHEADER => array(
                                'authorization: Bearer '.env('TAP_SECRET_KEY'),
                                'content-type: application/json'
                            ),
                        ));
                        $response = curl_exec($curl);
                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);
                        $response =  json_decode($response, true);
                        if($httpcode == 200)
                        {
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => $response['status'], 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => $response['status'],
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = $response['transaction']['url'];
                            $payment_gateway_status =  1;

                        }
                        else
                        {
                            ReservedStock::where('order_id', $order->id)->delete();
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => 'FAILED', 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => 'FAILED',
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = "";
                            $payment_gateway_status =  0;
                        }

                    }
                    else
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'payment_method_not_matching',
                            ], 200);
                    }


                    $last_order = Order::where('id', $order->id)->first();
                    $last_order_details = OrderDetail::where('order_id', $order->id)->get();
                    $last_order_address = OrderAddress::where('order_id', $order->id)->first();
                    $ordered_items = [];
                    $ordered_items = orderData($last_order_details, $time_zone);
                    $order_summary = orderSummary($last_order, $time_zone);
                    $payment_details = paymentDetails($last_order);
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    DB::commit();
                    return response()->json(
                        [
                            'success' => true,
                            'status' => 200,
                            'message' => __('messages.user_checkout_successfully'),
                            'message_code' => 'user_checkout_successfully',
                            'data' => [
                                'delivery_address' => addressDataSingle($last_order_address),
                                'ordered_items' => $ordered_items,
                                'order_summary' => $order_summary,
                                'payment_details' => $payment_details,
                                "order_status" => (string)$order->order_status,
                                "payment_gateway_status" => (string) $payment_gateway_status,
                                "payment_gateway_url" => $payment_gateway_url,
                                'cart_count' => (string) getCartCount()
                            ]

                        ], 200);
                }
                elseif ($request->sale_type == "Auction")
                {

                }
                else
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.something_went_wrong'),
                            'message_code' => 'sale_type_not_matching',
                        ], 200);
                }
            }
            elseif (isset($request->type) && ($request->type == "DirectBuy"))
            {
                if ($request->sale_type == "Sale")
                {
                    $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale_id)->firstOrFail();
                    $product_variant = ProductVariant::where('id', $request->product_variant_id)->firstOrFail();
                    $product = $product_variant->product;
                    $product_check = ProductForAuctionOrSale::where('type', 'Sale')->where('product_id', $product->id)->count();
                    if (!$product_check)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'type_not_matching',
                            ], 200);
                    }
                    $cart_summary = [];
                    $final_price = 0;
                    $regular_price = 0;
                    $discount = 0;


                    $final_price_temp = $product_variant->getFinalPrice() * $request->quantity;
                    $regular_price_temp = $product_variant->getRegularPrice() * $request->quantity;
                    $regular_price = $regular_price + $regular_price_temp;
                    $final_price = $final_price + $final_price_temp;
                    $discount_temp = $regular_price_temp - $final_price_temp;
                    $discount = $discount + $discount_temp;
                    //Process Order


                    $payment_method = PaymentMethod::where('id', $request->payment_method)->firstOrFail();
                    $address = Address::where('user_id', auth('api')->id())->where('id', $request->address_id)->firstOrFail();
                    $user_boutique = $product->boutique;
                    $vendor = $product->vendor;

                    $delivery_charge = getDeliveryCharge($user_boutique->id, $address->area_id);
                    $convenience_fees = 0;
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    $promo_code_discount = $promo_code_details['promo_code_discount'];
                    $delivery_charge = $promo_code_details['delivery_charge'];
                    $promo_code_data = $promo_code_details['promo_code_data'];
                    $promo_code_id = NULL;
                    $promo_discount_amount = 0;
                    $promo_free_delivery = 0;
                    if($promo_code_details['status'])
                    {
                        $promo_code_id = $promo_code_details['promo_code_data']->promo_code_id;
                        $promo_discount_amount = (double) $promo_code_discount;
                        $promo_free_delivery = (integer) $promo_code_details['promo_code_data']->free_delivery;
                    }
                    $grand_total = ($final_price + $delivery_charge + $convenience_fees) - $promo_code_discount;

                    $commission_percentage = $user_boutique->commission_percentage;
                    $commission_amount = $final_price * ($commission_percentage / 100);
                    DB::beginTransaction();
                    $order = new Order();
                    $order->user_id = auth('api')->id();
                    $order->user_boutique_id = $user_boutique->id;
                    $order->sale_type = "Sale";
                    $order->checkout_type = "DirectBuy";
                    $order->product_price = $final_price;
                    $order->delivery_charges = $delivery_charge;
                    $order->discount_amount = 0;
                    $order->promo_code_id = $promo_code_id;
                    $order->promo_discount_amount = $promo_code_discount;
                    $order->promo_free_delivery = $promo_free_delivery;
                    $order->commission_percentage = $commission_percentage;
                    $order->commission_amount = $commission_amount;
                    $order->total_amount = $grand_total;
                    $order->total_items = 1;
                    $order->order_status = "Pending";
                    $order->delivery_status = "Pending";
                    $order->payment_status = "INITIATED";
                    $order->payment_method_id = $request->payment_method;
                    $order->delivery_status_updated_on = $current_date_time;
                    $order->payment_status_updated_on = $current_date_time;
                    $order->order_status_updated_on = $current_date_time;
                    if(isset($request->device_token) && $request->device_token)$order->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$order->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$order->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$order->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$order->os_version = $request->os_version;
                    $order->save();
                    $order_id = getOrderId($order->id);
                    Order::where('id', $order->id)->update(['order_id' => $order_id]);

                    $order_address = new OrderAddress();
                    $order_address->user_id = auth('api')->id();
                    $order_address->order_id = $order->id;
                    $order_address->country_id = $address->country_id;
                    $order_address->governorate_id = $address->governorate_id;
                    $order_address->area_id = $address->area_id;
                    $order_address->first_name = $address->first_name;
                    $order_address->last_name = $address->last_name;
                    $order_address->contact_number = $address->contact_number;
                    $order_address->avenue = $address->avenue;
                    $order_address->block = $address->block;
                    $order_address->street = $address->street;
                    $order_address->building = $address->building;
                    $order_address->floor = $address->floor;
                    $order_address->apartment = $address->apartment;
                    $order_address->pin_code = $address->pin_code;
                    $order_address->notes = $address->notes;
                    $order_address->save();

                    $transaction = new Transaction();
                    $transaction->user_id = auth('api')->id();
                    $transaction->order_id = $order->id;
                    $transaction->payment_status = "INITIATED";
                    $transaction->payment_method_id = $payment_method->id;
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->save();
                    $transaction_id = getOrderId($transaction->id);
                    Transaction::where('id', $transaction->id)->update(['transaction_id' => $transaction_id]);

                    if ($product_variant->availableStockQuantity() < $request->quantity)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.order_failed_due_to_in_sufficient_stock'),
                                'message_code' => 'order_failed_due_to_in_sufficient_stock',
                            ], 200);
                    }


                    $reserved_stock = new ReservedStock();
                    $reserved_stock->stock_id = $product_variant->stock->id;
                    $reserved_stock->user_id = auth('api')->id();
                    $reserved_stock->user_boutique_id = $user_boutique->id;
                    $reserved_stock->order_id = $order->id;
                    $reserved_stock->product_variant_id = $product_variant->id;
                    $reserved_stock->quantity = $request->quantity;
                    $reserved_stock->status = 1;
                    $reserved_stock->save();

                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->user_id = auth('api')->id();
                    $order_detail->vendor_id = $vendor->id;
                    $order_detail->user_boutique_id = $user_boutique->id;
                    $order_detail->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
                    $order_detail->product_variant_id = $product_variant->id;
                    $order_detail->sale_type = "Sale";
                    $order_detail->final_price = $product_variant->getFinalPrice();
                    $order_detail->regular_price = $product_variant->getRegularPrice();
                    $order_detail->quantity = $request->quantity;
                    $order_detail->actual_quantity = $request->quantity;
                    $order_detail->discount_amount = $discount;
                    $order_detail->save();

                    $payment_gateway_status = 0;
                    $payment_gateway_url = "";
                    if ($request->payment_method == 3 || $request->payment_method == 4) // COD
                    {
                        $current_date_time = Carbon::now('UTC')->toDateTimeString();
                        Order::where('id', $order->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        Transaction::where('id', $transaction->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        $sales_count = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->first()->sales_count + 1;
                        ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['sales_count' => $sales_count, 'updated_at' => $current_date_time]);
                        DB::table('user_promo_codes')->where('user_id', auth('api')->id())->delete();
                        sendOrderInvoice($order->id);
                        sendOrderInvoiceVendor($order->id);
                        $push_title_en = "You Got A New Order";
                        $push_message_en = "You Got A New Order";
                        $push_title_ar = "You Got A New Order";
                        $push_message_ar = "You Got A New Order";
                        $push_target = "Vendor";
                        $user_ids = User::where('id', $order->vendor_id)->pluck('id')->toArray();
                        $headingData = [
                            "en" => $push_title_en,
                            "ar" => $push_title_ar,
                        ];

                        $contentData = [
                            "en" => $push_message_en,
                            "ar" => $push_message_ar,
                        ];

                        $pushData = [
                            "name_en" => $push_title_en,
                            "name_ar" => $push_title_ar,
                            "message_en" =>$push_message_en,
                            "message_ar" => $push_message_ar,
                            "target" => "",
                            "target_id" => "",
                        ];
                        sendPushNotifications($push_target, $user_ids, $headingData, $contentData, $pushData);
                    }
                    elseif ($request->payment_method == 1 || $request->payment_method == 2) // K Net // master card
                    {

//                        $country = Country::where('id', $order_address->country_id)->select('name', 'country_code')->firstOrFail();
//                        $governorate = Governorate::where('id', $order_address->governorate_id)->select('name')->firstOrFail();
//                        $area = Governorate::where('id', $order_address->area_id)->select('name')->firstOrFail();

                        $redirect_url = env('APP_URL')."/proceed-to-payment";
                        $post_url = env('APP_URL')."/payment-error";
                        if($request->payment_method == 1)
                        {
                            $source = "src_kw.knet";
                        }
                        else
                        {
                            $source = "src_card";
                        }


                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.tap.company/v2/charges',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{"amount":'.$order->total_amount.',"currency":"KWD","threeDSecure":true,"save_card":false,"description":"Product Purchase (Beiaat)","statement_descriptor":"Sample","metadata":{"udf1":"Beiaat Purchase","udf2":"Beiaat Purchase"},"reference":{"transaction":"'.$transaction->transaction_id.'","order":"'.$order->order_id.'"},"receipt":{"email":false,"sms":true},"customer":{"first_name":"'.$order_address->first_name.'","middle_name":" ","last_name":"'.$order_address->last_name.'","email":"'.auth('api')->user()->email.'","phone":{"country_code":"965","number":"'.auth('api')->user()->mobile_number.'"}},"merchant":{"id":""},"source":{"id":"'.$source.'"},"post":{"url":"'.$post_url.'"},"redirect":{"url":"'.$redirect_url.'"}}',
                            CURLOPT_HTTPHEADER => array(
                                'authorization: Bearer '.env('TAP_SECRET_KEY'),
                                'content-type: application/json'
                            ),
                        ));
                        $response = curl_exec($curl);
                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);

                        if($httpcode == 200)
                        {

                            $response =  json_decode($response, true);
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => $response['status'], 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => $response['status'],
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = $response['transaction']['url'];
                            $payment_gateway_status =  1;

                        }
                        else
                        {
                            ReservedStock::where('order_id', $order->id)->delete();
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => 'FAILED', 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => 'FAILED',
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = "";
                            $payment_gateway_status =  0;
                        }

                    }
                    else
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'payment_method_not_matching',
                            ], 200);
                    }
                    DB::commit();
                    $last_order = Order::where('id', $order->id)->first();
                    $last_order_details = OrderDetail::where('order_id', $order->id)->get();
                    $last_order_address = OrderAddress::where('order_id', $order->id)->first();
                    $ordered_items = orderData($last_order_details, $time_zone);
                    $payment_details = paymentDetails($last_order);
                    $order_summary = orderSummary($last_order, $time_zone);
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    return response()->json(
                        [
                            'success' => true,
                            'status' => 200,
                            'message' => __('messages.user_checkout_successfully'),
                            'message_code' => 'user_checkout_successfully',
                            'data' => [
                                'delivery_address' => addressDataSingle($last_order_address),
                                'ordered_items' => $ordered_items,
                                'order_summary' => $order_summary,
                                'payment_details' => $payment_details,
                                "order_status" => (string)$order->order_status,
                                "payment_gateway_status" => (string) $payment_gateway_status,
                                "payment_gateway_url" => $payment_gateway_url,
                                'cart_count' => (string) getCartCount()
                            ]

                        ], 200);
                }
                elseif ($request->sale_type == "Auction")
                {
                    $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale_id)->firstOrFail();
                    $product_variant = ProductVariant::where('id', $request->product_variant_id)->firstOrFail();
                    $product = $product_variant->product;
                    $product_check = ProductForAuctionOrSale::where('type', 'Auction')->where('product_id', $product->id)->count();
                    if (!$product_check)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'type_not_matching',
                            ], 200);
                    }

                    $user_id = auth('api')->id();
                    if($product_for_auction_or_sale->bid_end_time > $current_date_time)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'bidding_not_completed',
                            ], 200);
                    }

                    if($product_for_auction_or_sale->bid_purchase_status)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'already_purchased',
                            ], 200);
                    }


                    $highest_bid = BiddingSummary::where('product_variant_id', $product_variant->id)->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->orderBy('current_bid_amount', 'DESC')->firstOrFail();
                   if($highest_bid->user_id != $user_id)
                   {
                       return response()->json(
                           [
                               'success' => false,
                               'status' => 400,
                               'message' => __('messages.not_winner'),
                               'message_code' => 'not_winner',
                           ], 200);
                   }
                    $per_unit_amount = $highest_bid->current_bid_amount;
                    $quantity = $request->quantity;
                    $cart_summary = [];
                    $final_price = 0;
                    $regular_price = 0;
                    $discount = 0;

                    $final_price = $per_unit_amount * $quantity;
                    $discount = 0;
                    //Process Order


                    $payment_method = PaymentMethod::where('id', $request->payment_method)->firstOrFail();
                    $address = Address::where('user_id', auth('api')->id())->where('id', $request->address_id)->firstOrFail();
                    $user_boutique = $product->boutique;
                    $vendor = $product->vendor;

                    $delivery_charge = getDeliveryCharge($user_boutique->id, $address->area_id);
                    $convenience_fees = 0;
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    $promo_code_discount = $promo_code_details['promo_code_discount'];
                    $delivery_charge = $promo_code_details['delivery_charge'];
                    $promo_code_data = $promo_code_details['promo_code_data'];
                    $promo_code_id = NULL;
                    $promo_discount_amount = 0;
                    $promo_free_delivery = 0;
                    if($promo_code_details['status'])
                    {
                        $promo_code_id = $promo_code_details['promo_code_data']->promo_code_id;
                        $promo_discount_amount = (double) $promo_code_discount;
                        $promo_free_delivery = (integer) $promo_code_details['promo_code_data']->free_delivery;
                    }
                    $grand_total = ($final_price + $delivery_charge + $convenience_fees) - $discount - $promo_code_discount;

                    $commission_percentage = $user_boutique->commission_percentage;
                    $commission_amount = $final_price * ($commission_percentage / 100);
                    DB::beginTransaction();
                    $order = new Order();
                    $order->user_id = auth('api')->id();
                    $order->user_boutique_id = $user_boutique->id;
                    $order->sale_type = "Auction";
                    $order->checkout_type = "DirectBuy";
                    $order->product_price = $final_price;
                    $order->delivery_charges = $delivery_charge;
                    $order->discount_amount = 0;
                    $order->promo_code_id = $promo_code_id;
                    $order->promo_discount_amount = $promo_code_discount;
                    $order->promo_free_delivery = $promo_free_delivery;
                    $order->commission_percentage = $commission_percentage;
                    $order->commission_amount = $commission_amount;
                    $order->total_amount = $grand_total;
                    $order->total_items = 1;
                    $order->order_status = "Pending";
                    $order->delivery_status = "Pending";
                    $order->payment_status = "INITIATED";
                    $order->payment_method_id = $request->payment_method;
                    $order->delivery_status_updated_on = $current_date_time;
                    $order->payment_status_updated_on = $current_date_time;
                    $order->order_status_updated_on = $current_date_time;
                    if(isset($request->device_token) && $request->device_token)$order->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$order->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$order->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$order->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$order->os_version = $request->os_version;
                    $order->save();
                    $order_id = getOrderId($order->id);
                    Order::where('id', $order->id)->update(['order_id' => $order_id]);

                    $order_address = new OrderAddress();
                    $order_address->user_id = auth('api')->id();
                    $order_address->order_id = $order->id;
                    $order_address->country_id = $address->country_id;
                    $order_address->governorate_id = $address->governorate_id;
                    $order_address->area_id = $address->area_id;
                    $order_address->first_name = $address->first_name;
                    $order_address->last_name = $address->last_name;
                    $order_address->contact_number = $address->contact_number;
                    $order_address->avenue = $address->avenue;
                    $order_address->block = $address->block;
                    $order_address->street = $address->street;
                    $order_address->building = $address->building;
                    $order_address->floor = $address->floor;
                    $order_address->apartment = $address->apartment;
                    $order_address->pin_code = $address->pin_code;
                    $order_address->notes = $address->notes;
                    $order_address->save();

                    $transaction = new Transaction();
                    $transaction->user_id = auth('api')->id();
                    $transaction->order_id = $order->id;
                    $transaction->payment_status = "INITIATED";
                    $transaction->payment_method_id = $payment_method->id;
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->save();
                    $transaction_id = getOrderId($transaction->id);
                    Transaction::where('id', $transaction->id)->update(['transaction_id' => $transaction_id]);

                    if ($product_variant->availableStockQuantity() < $quantity)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.order_failed_due_to_in_sufficient_stock'),
                                'message_code' => 'order_failed_due_to_in_sufficient_stock',
                            ], 200);
                    }


                    $reserved_stock = new ReservedStock();
                    $reserved_stock->stock_id = $product_variant->stock->id;
                    $reserved_stock->user_id = auth('api')->id();
                    $reserved_stock->user_boutique_id = $user_boutique->id;
                    $reserved_stock->order_id = $order->id;
                    $reserved_stock->product_variant_id = $product_variant->id;
                    $reserved_stock->quantity = $quantity;
                    $reserved_stock->status = 1;
                    $reserved_stock->save();

                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->user_id = auth('api')->id();
                    $order_detail->vendor_id = $vendor->id;
                    $order_detail->user_boutique_id = $user_boutique->id;
                    $order_detail->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
                    $order_detail->product_variant_id = $product_variant->id;
                    $order_detail->sale_type = "Auction";
                    $order_detail->final_price = $per_unit_amount;
                    $order_detail->regular_price = 0;
                    $order_detail->bid_start_price = $product_variant->getBidStartPrice();
                    $order_detail->bid_start_time = $product_variant->bid_start_time;
                    $order_detail->bid_end_time = $product_variant->bid_end_time;
                    $order_detail->bid_value = $product_variant->getBidValue();
                    $order_detail->quantity = $quantity;
                    $order_detail->actual_quantity = $quantity;
                    $order_detail->discount_amount = $discount;
                    $order_detail->save();

                    $payment_gateway_status = 0;
                    $payment_gateway_url = "";
                    if ($request->payment_method == 3 || $request->payment_method == 4) // COD
                    {
                        $current_date_time = Carbon::now('UTC')->toDateTimeString();
                        Order::where('id', $order->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        Transaction::where('id', $transaction->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        $sales_count = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->first()->sales_count + 1;
                        ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['sales_count' => $sales_count, 'bid_purchase_status' => true, 'purchased_user_id' => auth('api')->id(), 'updated_at' => $current_date_time]);
                        DB::table('user_promo_codes')->where('user_id', auth('api')->id())->delete();
                        sendOrderInvoice($order->id);
                        sendOrderInvoiceVendor($order->id);
                        $push_title_en = "You Got A New Order";
                        $push_message_en = "You Got A New Order";
                        $push_title_ar = "You Got A New Order";
                        $push_message_ar = "You Got A New Order";
                        $push_target = "Vendor";
                        $user_ids = User::where('id', $order->vendor_id)->pluck('id')->toArray();
                        $headingData = [
                            "en" => $push_title_en,
                            "ar" => $push_title_ar,
                        ];

                        $contentData = [
                            "en" => $push_message_en,
                            "ar" => $push_message_ar,
                        ];

                        $pushData = [
                            "name_en" => $push_title_en,
                            "name_ar" => $push_title_ar,
                            "message_en" =>$push_message_en,
                            "message_ar" => $push_message_ar,
                            "target" => "",
                            "target_id" => "",
                        ];
                        sendPushNotifications($push_target, $user_ids, $headingData, $contentData, $pushData);
                    }
                    elseif ($request->payment_method == 1 || $request->payment_method == 2) // K Net // master card
                    {

//                        $country = Country::where('id', $order_address->country_id)->select('name', 'country_code')->firstOrFail();
//                        $governorate = Governorate::where('id', $order_address->governorate_id)->select('name')->firstOrFail();
//                        $area = Governorate::where('id', $order_address->area_id)->select('name')->firstOrFail();

                        $redirect_url = env('APP_URL')."/proceed-to-payment";
                        $post_url = env('APP_URL')."/payment-error";
                        if($request->payment_method == 1)
                        {
                            $source = "src_kw.knet";
                        }
                        else
                        {
                            $source = "src_card";
                        }

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.tap.company/v2/charges',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{"amount":'.$order->total_amount.',"currency":"KWD","threeDSecure":true,"save_card":false,"description":"Product Purchase (Beiaat)","statement_descriptor":"Sample","metadata":{"udf1":"Beiaat Purchase","udf2":"Beiaat Purchase"},"reference":{"transaction":"'.$transaction->transaction_id.'","order":"'.$order->order_id.'"},"receipt":{"email":false,"sms":true},"customer":{"first_name":"'.$order_address->first_name.'","middle_name":" ","last_name":"'.$order_address->last_name.'","email":"'.auth('api')->user()->email.'","phone":{"country_code":"965","number":"'.auth('api')->user()->mobile_number.'"}},"merchant":{"id":""},"source":{"id":"'.$source.'"},"post":{"url":"'.$post_url.'"},"redirect":{"url":"'.$redirect_url.'"}}',
                            CURLOPT_HTTPHEADER => array(
                                'authorization: Bearer '.env('TAP_SECRET_KEY'),
                                'content-type: application/json'
                            ),
                        ));
                        $response = curl_exec($curl);
                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);

                        if($httpcode == 200)
                        {
                            $response =  json_decode($response, true);
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => $response['status'], 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => $response['status'],
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = $response['transaction']['url'];
                            $payment_gateway_status =  1;

                        }
                        else
                        {
                            ReservedStock::where('order_id', $order->id)->delete();
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => 'FAILED', 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => 'FAILED',
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = "";
                            $payment_gateway_status =  0;

                        }

                    }
                    else
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'payment_method_not_matching',
                            ], 200);
                    }
                    DB::commit();
                    $last_order = Order::where('id', $order->id)->first();
                    $last_order_details = OrderDetail::where('order_id', $order->id)->get();
                    $last_order_address = OrderAddress::where('order_id', $order->id)->first();
                    $ordered_items = orderData($last_order_details, $time_zone);
                    $payment_details = paymentDetails($last_order);
                    $order_summary = orderSummary($last_order, $time_zone);
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    return response()->json(
                        [
                            'success' => true,
                            'status' => 200,
                            'message' => __('messages.user_checkout_successfully'),
                            'message_code' => 'user_checkout_successfully',
                            'data' => [
                                'delivery_address' => addressDataSingle($last_order_address),
                                'ordered_items' => $ordered_items,
                                'order_summary' => $order_summary,
                                'payment_details' => $payment_details,
                                "order_status" => (string)$order->order_status,
                                "payment_gateway_status" => (string) $payment_gateway_status,
                                "payment_gateway_url" => $payment_gateway_url,
                                'cart_count' => (string) getCartCount()
                            ]

                        ], 200);
                }
                elseif ($request->sale_type == "Bulk")
                {
                    $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale_id)->firstOrFail();
                    $product_variant = ProductVariant::where('id', $request->product_variant_id)->firstOrFail();
                    $product = $product_variant->product;
                    $product_check = ProductForAuctionOrSale::where('type', 'Bulk')->where('product_id', $product->id)->count();
                    if (!$product_check)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'type_not_matching',
                            ], 200);
                    }
                    $cart_summary = [];
                    $final_price = 0;
                    $regular_price = 0;
                    $discount = 0;
                    $actual_quantity = 0;
                    for($i=1; $i<=$request->quantity; $i++)
                    {
                        if($i ==1)
                        {
                            $final_price_temp = $product_variant->getFinalPrice();
                            $regular_price_temp = $product_variant->getRegularPrice();
                            $regular_price = $regular_price + $regular_price_temp;
                            $final_price = $final_price + $final_price_temp;
                            $discount_temp = $regular_price_temp - $final_price_temp;
                            $discount = $discount + $discount_temp;
                            $actual_quantity = $actual_quantity + $product_variant->getInitialQuantity();
                        }
                        else
                        {
                            $final_price_temp = $product_variant->getIncrementalPrice();
                            $regular_price_temp = $product_variant->getIncrementalPrice();
                            $regular_price = $regular_price + $regular_price_temp;
                            $final_price = $final_price + $final_price_temp;
                            $discount_temp = $regular_price_temp - $final_price_temp;
                            $discount = $discount + $discount_temp;
                            $actual_quantity = $actual_quantity + $product_variant->getIncrementalQuantity();
                        }
                    }

                    //Process Order


                    $payment_method = PaymentMethod::where('id', $request->payment_method)->firstOrFail();
                    $address = Address::where('user_id', auth('api')->id())->where('id', $request->address_id)->firstOrFail();
                    $user_boutique = $product->boutique;
                    $vendor = $product->vendor;

                    $delivery_charge = getDeliveryCharge($user_boutique->id, $address->area_id);
                    $convenience_fees = 0;
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);
                    $promo_code_discount = $promo_code_details['promo_code_discount'];
                    $delivery_charge = $promo_code_details['delivery_charge'];
                    $promo_code_data = $promo_code_details['promo_code_data'];
                    $promo_code_id = NULL;
                    $promo_discount_amount = 0;
                    $promo_free_delivery = 0;
                    if($promo_code_details['status'])
                    {
                        $promo_code_id = $promo_code_details['promo_code_data']->promo_code_id;
                        $promo_discount_amount = (double) $promo_code_discount;
                        $promo_free_delivery = (integer) $promo_code_details['promo_code_data']->free_delivery;
                    }
                    $grand_total = ($final_price + $delivery_charge + $convenience_fees) - $promo_code_discount;

                    $commission_percentage = $user_boutique->commission_percentage;
                    $commission_amount = $final_price * ($commission_percentage / 100);
                    DB::beginTransaction();
                    $order = new Order();
                    $order->user_id = auth('api')->id();
                    $order->user_boutique_id = $user_boutique->id;
                    $order->sale_type = "Sale";
                    $order->checkout_type = "DirectBuy";
                    $order->product_price = $final_price;
                    $order->delivery_charges = $delivery_charge;
                    $order->discount_amount = 0;
                    $order->promo_code_id = $promo_code_id;
                    $order->promo_discount_amount = $promo_code_discount;
                    $order->promo_free_delivery = $promo_free_delivery;
                    $order->commission_percentage = $commission_percentage;
                    $order->commission_amount = $commission_amount;
                    $order->total_amount = $grand_total;
                    $order->total_items = 1;
                    $order->order_status = "Pending";
                    $order->delivery_status = "Pending";
                    $order->payment_status = "INITIATED";
                    $order->payment_method_id = $request->payment_method;
                    $order->delivery_status_updated_on = $current_date_time;
                    $order->payment_status_updated_on = $current_date_time;
                    $order->order_status_updated_on = $current_date_time;
                    if(isset($request->device_token) && $request->device_token)$order->device_token = $request->device_token;
                    if(isset($request->device_model) && $request->device_model)$order->device_model = $request->device_model;
                    if(isset($request->device_type) && $request->device_type)$order->device_type = $request->device_type;
                    if(isset($request->app_version) && $request->app_version)$order->app_version = $request->app_version;
                    if(isset($request->os_version) && $request->os_version)$order->os_version = $request->os_version;
                    $order->save();
                    $order_id = getOrderId($order->id);
                    Order::where('id', $order->id)->update(['order_id' => $order_id]);

                    $order_address = new OrderAddress();
                    $order_address->user_id = auth('api')->id();
                    $order_address->order_id = $order->id;
                    $order_address->country_id = $address->country_id;
                    $order_address->governorate_id = $address->governorate_id;
                    $order_address->area_id = $address->area_id;
                    $order_address->first_name = $address->first_name;
                    $order_address->last_name = $address->last_name;
                    $order_address->contact_number = $address->contact_number;
                    $order_address->avenue = $address->avenue;
                    $order_address->block = $address->block;
                    $order_address->street = $address->street;
                    $order_address->building = $address->building;
                    $order_address->floor = $address->floor;
                    $order_address->apartment = $address->apartment;
                    $order_address->pin_code = $address->pin_code;
                    $order_address->notes = $address->notes;
                    $order_address->save();

                    $transaction = new Transaction();
                    $transaction->user_id = auth('api')->id();
                    $transaction->order_id = $order->id;
                    $transaction->payment_status = "INITIATED";
                    $transaction->payment_method_id = $payment_method->id;
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->save();
                    $transaction_id = getOrderId($transaction->id);
                    Transaction::where('id', $transaction->id)->update(['transaction_id' => $transaction_id]);


                    if ($product_variant->availableStockQuantity() < $actual_quantity)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.order_failed_due_to_in_sufficient_stock'),
                                'message_code' => 'order_failed_due_to_in_sufficient_stock',
                            ], 200);
                    }


                    $reserved_stock = new ReservedStock();
                    $reserved_stock->stock_id = $product_variant->stock->id;
                    $reserved_stock->user_id = auth('api')->id();
                    $reserved_stock->user_boutique_id = $user_boutique->id;
                    $reserved_stock->order_id = $order->id;
                    $reserved_stock->product_variant_id = $product_variant->id;
                    $reserved_stock->quantity = $actual_quantity;
                    $reserved_stock->status = 1;
                    $reserved_stock->save();

                    $order_detail = new OrderDetail();
                    $order_detail->order_id = $order->id;
                    $order_detail->user_id = auth('api')->id();
                    $order_detail->vendor_id = $vendor->id;
                    $order_detail->user_boutique_id = $user_boutique->id;
                    $order_detail->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
                    $order_detail->product_variant_id = $product_variant->id;
                    $order_detail->sale_type = "Bulk";
                    $order_detail->final_price = $final_price;
                    $order_detail->regular_price = $regular_price;
                    $order_detail->quantity = $request->quantity;
                    $order_detail->actual_quantity = $actual_quantity;
                    $order_detail->discount_amount = $discount;
                    $order_detail->save();
                    $payment_gateway_url = "";
                    $payment_gateway_status =  0;
                    if ($request->payment_method == 3 || $request->payment_method == 4) // COD
                    {
                        $current_date_time = Carbon::now('UTC')->toDateTimeString();
                        Order::where('id', $order->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        Transaction::where('id', $transaction->id)->update(['payment_status' => 'CAPTURED', 'payment_status_updated_on' => $current_date_time]);
                        $sales_count = ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->first()->sales_count + 1;
                        ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['sales_count' => $sales_count, 'updated_at' => $current_date_time]);
                        DB::table('user_promo_codes')->where('user_id', auth('api')->id())->delete();
                        sendOrderInvoice($order->id);
                        sendOrderInvoiceVendor($order->id);
                        $push_title_en = "You Got A New Order";
                        $push_message_en = "You Got A New Order";
                        $push_title_ar = "You Got A New Order";
                        $push_message_ar = "You Got A New Order";
                        $push_target = "Vendor";
                        $user_ids = User::where('id', $order->vendor_id)->pluck('id')->toArray();
                        $headingData = [
                            "en" => $push_title_en,
                            "ar" => $push_title_ar,
                        ];

                        $contentData = [
                            "en" => $push_message_en,
                            "ar" => $push_message_ar,
                        ];

                        $pushData = [
                            "name_en" => $push_title_en,
                            "name_ar" => $push_title_ar,
                            "message_en" =>$push_message_en,
                            "message_ar" => $push_message_ar,
                            "target" => "",
                            "target_id" => "",
                        ];
                        sendPushNotifications($push_target, $user_ids, $headingData, $contentData, $pushData);
                    }
                    elseif ($request->payment_method == 1 || $request->payment_method == 2) // K Net // master card
                    {

//                        $country = Country::where('id', $order_address->country_id)->select('name', 'country_code')->firstOrFail();
//                        $governorate = Governorate::where('id', $order_address->governorate_id)->select('name')->firstOrFail();
//                        $area = Governorate::where('id', $order_address->area_id)->select('name')->firstOrFail();

                        $redirect_url = env('APP_URL')."/proceed-to-payment";
                        $post_url = env('APP_URL')."/payment-error";
                        if($request->payment_method == 1)
                        {
                            $source = "src_kw.knet";
                        }
                        else
                        {
                            $source = "src_card";
                        }

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api.tap.company/v2/charges',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{"amount":'.$order->total_amount.',"currency":"KWD","threeDSecure":true,"save_card":false,"description":"Product Purchase (Beiaat)","statement_descriptor":"Sample","metadata":{"udf1":"Beiaat Purchase","udf2":"Beiaat Purchase"},"reference":{"transaction":"'.$transaction->transaction_id.'","order":"'.$order->order_id.'"},"receipt":{"email":false,"sms":true},"customer":{"first_name":"'.$order_address->first_name.'","middle_name":" ","last_name":"'.$order_address->last_name.'","email":"'.auth('api')->user()->email.'","phone":{"country_code":"965","number":"'.auth('api')->user()->mobile_number.'"}},"merchant":{"id":""},"source":{"id":"'.$source.'"},"post":{"url":"'.$post_url.'"},"redirect":{"url":"'.$redirect_url.'"}}',
                            CURLOPT_HTTPHEADER => array(
                                'authorization: Bearer '.env('TAP_SECRET_KEY'),
                                'content-type: application/json'
                            ),
                        ));
                        $response = curl_exec($curl);
                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        curl_close($curl);

                        if($httpcode == 200)
                        {
                            $response =  json_decode($response, true);
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => $response['status'], 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => $response['status'],
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = $response['transaction']['url'];
                            $payment_gateway_status =  1;

                        }
                        else
                        {
                            ReservedStock::where('order_id', $order->id)->delete();
                            $current_date_time = Carbon::now('UTC')->toDateTimeString();
                            Order::where('id', $order->id)->update(['payment_status' => 'FAILED', 'payment_status_updated_on' => $current_date_time]);
                            Transaction::where('id', $transaction->id)
                                ->update(
                                    [
                                        'payment_status' => 'FAILED',
                                        'payment_status_updated_on' => $current_date_time,
                                        'charge_request_id' => $response['id'],
                                    ]);
                            $payment_gateway_url = "";
                            $payment_gateway_status =  0;
                        }

                    }
                    else
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.something_went_wrong'),
                                'message_code' => 'payment_method_not_matching',
                            ], 200);
                    }
                    DB::commit();
                    $last_order = Order::where('id', $order->id)->first();
                    $last_order_details = OrderDetail::where('order_id', $order->id)->get();
                    $last_order_address = OrderAddress::where('order_id', $order->id)->first();
                    $ordered_items = orderData($last_order_details, $time_zone);
                    $payment_details = paymentDetails($last_order);
                    $order_summary = orderSummary($last_order, $time_zone);
                    $promo_code_details  = getPromoCodeDetails($user_boutique->id, auth('api')->id(), $final_price, $delivery_charge, $current_date_time, $time_zone);

                    return response()->json(
                        [
                            'success' => true,
                            'status' => 200,
                            'message' => __('messages.user_checkout_successfully'),
                            'message_code' => 'user_checkout_successfully',
                            'data' => [
                                'delivery_address' => addressDataSingle($last_order_address),
                                'ordered_items' => $ordered_items,
                                'order_summary' => $order_summary,
                                'payment_details' => $payment_details,
                                "order_status" => (string)$order->order_status,
                                "payment_gateway_status" => (string) $payment_gateway_status,
                                "payment_gateway_url" => $payment_gateway_url,
                                'cart_count' => (string) getCartCount()
                            ]

                        ], 200);
                }
                else
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.something_went_wrong'),
                            'message_code' => 'sale_type_not_matching',
                        ], 200);
                }
            }
        }
        catch (\Exception $exception)
        {

            DB::rollback();
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

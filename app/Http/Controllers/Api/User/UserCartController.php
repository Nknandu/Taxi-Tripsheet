<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Cart;
use App\Models\Category;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserCartController extends Controller
{
    public function getUserCartItems(Request $request)
    {
        try
        {
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

            if(auth('api')->check())
            {
                $cart_item_query = Cart::query();
                $cart_item_query->where('user_id', auth('api')->id());

                $cart_item_count_query = clone $cart_item_query;
                $cart_item_all_query = clone $cart_item_query;
                $cart_item_count = $cart_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $cart_item_count;
                $cart_item_data = $cart_item_query->paginate($per_page);
                $cart_data = cartData($cart_item_data->items()); // defined in helpers

                $cart_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                foreach ($cart_item_all_query->get() as $cart_item)
                {
                    $is_available = 1;
                    if($cart_item->sale_type == 'Sale')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }
                        $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                        $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                        $regular_price = $regular_price + $regular_price_temp;
                        $final_price = $final_price + $final_price_temp;
                        $discount_temp = $regular_price_temp - $final_price_temp;
                        $discount = $discount + $discount_temp;
                    }
                    elseif($cart_item->sale_type == 'Bulk')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }

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
                    }

                }

                $convenience_fees = 0;
                $delivery_charge = 0;
                $grand_total = ($final_price + $delivery_charge + $convenience_fees);
                $cart_summary = [
                    "total_items" => (string) $cart_item_count,
                    "sub_total" => (string) $final_price,
                    "discount_amount" => (string) 0,
                    "delivery_charge" => (string) $delivery_charge,
                    "total_amount" => (string) $grand_total,
                    ];
                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' => __('messages.success'),
                        'message_code' => 'user_cart_success',
                        'data' => [
                            'meta' =>[
                                'total_pages' => (string) $cart_item_data->lastPage(),
                                'current_page' => (string) $cart_item_data->currentPage(),
                                'total_records' => (string) $cart_item_data->total(),
                                'records_on_current_page' => (string) $cart_item_data->count(),
                                'record_from' => (string) $cart_item_data->firstItem(),
                                'record_to' => (string) $cart_item_data->lastItem(),
                                'per_page' => (string) $cart_item_data->perPage(),
                            ],
                            "cart_items" => $cart_data,
                            "cart_summary" => $cart_summary,
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
                        'message' =>  __('messages.un_authorized'),
                        'message_code' => 'un_authorized',
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

    public function addUserCartItem(Request $request)
    {
        try
        {
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
                'product_variant_id' => 'required',
                'product_for_auction_or_sale_id' => 'required',
                'quantity' => 'required',
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

            if(auth('api')->check())
            {
                $product_variant_id = $request->product_variant_id;
                $product_variant = ProductVariant::where('id', $product_variant_id)->first();
                $product = $product_variant->product;
                $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $request->product_for_auction_or_sale_id)->whereIn('type', ['Sale', 'Bulk'])->firstOrFail();

                $cart_vendor_check = Cart::where('user_id', auth('api')->id())->first();
                if($cart_vendor_check)
                {
                    $cart_vendor_id = $cart_vendor_check->product->vendor->id;
                    $product_vendor_id = $product_variant->product->vendor->id;
                    if($cart_vendor_id != $product_vendor_id)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' =>  __('messages.choose_same_vendor_product'),
                                'message_code' => 'choose_same_vendor_product',
                            ], 200);
                    }
                }

                $cart_product = Cart::where('user_id', auth('api')->id())->where('product_for_auction_or_sale_id', $product_for_auction_or_sale->id)->where('product_variant_id', $product_variant_id)->first();
                if($cart_product)
                {
                    $cart = Cart::findOrFail($cart_product->id);
                    $cart->quantity = $cart->quantity + $request->quantity;
                    $cart->save();
                }
                else
                {
                    if($product->sale_type == "Auction" || $product->sale_type == "Live")
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' =>  __('messages.select_product_type_sale_bulk'),
                                'message_code' => 'select_product_type_sale_bulk',
                            ], 200);
                    }
                    $actual_quantity = 0;
                    if($product->sale_type == "Bulk")
                    {
                        $initial_quantity = 0;
                        $number_of_increments = 0;
                        if(($request->quantity - $product_variant->getInitialQuantity()) >= 0)
                        {
                            $initial_quantity =  $product_variant->getInitialQuantity();
                            if(($request->quantity - $product_variant->getInitialQuantity()) > 0)
                            {
                                $balance_qty = $request->quantity - $product_variant->getInitialQuantity();
                                if($balance_qty % $product_variant->getIncrementalQuantity() == 0)
                                {
                                    $number_of_increments = $balance_qty / $product_variant->getIncrementalQuantity();
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

                        $actual_quantity = $initial_quantity + ($product_variant->getIncrementalQuantity() * $number_of_increments);

                    }
                    elseif ($product->sale_type == "Sale")
                    {
                        $actual_quantity = $request->quantity;
                    }

                    if($product_variant->availableStockQuantity() < $actual_quantity)
                    {
                        return response()->json(
                            [
                                'success' => false,
                                'status' => 400,
                                'message' => __('messages.in_sufficient_stock'),
                                'message_code' => 'in_sufficient_stock',
                            ], 200);
                    }

                    $cart = new Cart();
                    $cart->user_id = auth('api')->id();
                    $cart->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
                    $cart->product_id = $product->id;
                    $cart->product_variant_id = $product_variant_id;
                    $cart->sale_type = $product->sale_type;
                    $cart->quantity = $request->quantity;
                    $cart->save();
                }

                $product_cart_count = $product_for_auction_or_sale->cart_count;
                ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['cart_count' => $product_cart_count + 1]);

                $cart_item_query = Cart::query();
                $cart_item_query->where('user_id', auth('api')->id());

                $cart_item_count_query = clone $cart_item_query;
                $cart_item_all_query = clone $cart_item_query;
                $cart_item_count = $cart_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $cart_item_count;
                $cart_item_data = $cart_item_query->paginate($per_page);
                $cart_data = cartData($cart_item_data->items()); // defined in helpers

                $cart_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                foreach ($cart_item_all_query->get() as $cart_item)
                {
                    $is_available = 1;
                    if($cart_item->sale_type == 'Sale')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }
                        $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                        $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                        $regular_price = $regular_price + $regular_price_temp;
                        $final_price = $final_price + $final_price_temp;
                        $discount_temp = $regular_price_temp - $final_price_temp;
                        $discount = $discount + $discount_temp;
                    }
                    elseif($cart_item->sale_type == 'Bulk')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }

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

                    }

                }
                $convenience_fees = 0;
                $delivery_charge = 0;
                $grand_total = ($final_price + $delivery_charge + $convenience_fees);
                $cart_summary = [
                    "total_items" => (string) $cart_item_count,
                    "sub_total" => (string) $final_price,
                    "discount_amount" => (string) 0,
                    "delivery_charge" => (string) $delivery_charge,
                    "total_amount" => (string) $grand_total,
                ];


                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  __('messages.cart_added_success'),
                        'message_code' => 'cart_added_success',
                        'data' => [
//                            'meta' =>[
//                                'total_pages' => (string) $cart_item_data->lastPage(),
//                                'current_page' => (string) $cart_item_data->currentPage(),
//                                'total_records' => (string) $cart_item_data->total(),
//                                'records_on_current_page' => (string) $cart_item_data->count(),
//                                'record_from' => (string) $cart_item_data->firstItem(),
//                                'record_to' => (string) $cart_item_data->lastItem(),
//                                'per_page' => (string) $cart_item_data->perPage(),
//                            ],
                            "cart_items" => $cart_data,
                            "cart_summary" => $cart_summary,
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
                        'message' =>  __('messages.un_authorized'),
                        'message_code' => 'un_authorized',
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
    public function updateUserCartItem(Request $request)
    {
        try
        {
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
                'cart_id' => 'required',
                'quantity' => 'required',
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

            if(auth('api')->check())
            {
                $cart = Cart::findOrFail($request->cart_id);
                $actual_quantity = 0;
                if($cart->product->sale_type == "Bulk")
                {
                    $initial_quantity = 0;
                    $number_of_increments = 0;
                    if(($request->quantity - $cart->product_variant->getInitialQuantity()) >= 0)
                    {
                        $initial_quantity =  $cart->product_variant->getInitialQuantity();
                        if(($request->quantity - $cart->product_variant->getInitialQuantity()) > 0)
                        {
                            $balance_qty = $request->quantity - $cart->product_variant->getInitialQuantity();
                            if($balance_qty % $cart->product_variant->getIncrementalQuantity() == 0)
                            {
                                $number_of_increments = $balance_qty / $cart->product_variant->getIncrementalQuantity();
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

                    $actual_quantity = $initial_quantity + ($cart->product_variant->getIncrementalQuantity() * $number_of_increments);

                }
                elseif ($cart->product->sale_type == "Sale")
                {
                    $actual_quantity = $request->quantity;
                }

                if($cart->product_variant->availableStockQuantity() < $actual_quantity)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.in_sufficient_stock'),
                            'message_code' => 'in_sufficient_stock',
                        ], 200);
                }


                $cart->quantity = $request->quantity;
                $cart->save();

                $cart_item_query = Cart::query();
                $cart_item_query->where('user_id', auth('api')->id());

                $cart_item_count_query = clone $cart_item_query;
                $cart_item_all_query = clone $cart_item_query;
                $cart_item_count = $cart_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $cart_item_count;
                $cart_item_data = $cart_item_query->paginate($per_page);
                $cart_data = cartData($cart_item_data->items()); // defined in helpers

                $cart_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                foreach ($cart_item_all_query->get() as $cart_item)
                {
                    $is_available = 1;
                    if($cart_item->sale_type == 'Sale')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }
                        $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                        $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                        $regular_price = $regular_price + $regular_price_temp;
                        $final_price = $final_price + $final_price_temp;
                        $discount_temp = $regular_price_temp - $final_price_temp;
                        $discount = $discount + $discount_temp;
                    }
                    elseif($cart_item->sale_type == 'Bulk')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }

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

                    }

                }
                $convenience_fees = 0;
                $delivery_charge = 0;
                $grand_total = ($final_price + $delivery_charge + $convenience_fees);
                $cart_summary = [
                    "total_items" => (string) $cart_item_count,
                    "sub_total" => (string) $final_price,
                    "discount_amount" => (string) 0,
                    "delivery_charge" => (string) $delivery_charge,
                    "total_amount" => (string) $grand_total,
                ];

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  __('messages.cart_updated_success'),
                        'message_code' => 'cart_updated_success',
                        'data' => [
//                            'meta' =>[
//                                'total_pages' => (string) $cart_item_data->lastPage(),
//                                'current_page' => (string) $cart_item_data->currentPage(),
//                                'total_records' => (string) $cart_item_data->total(),
//                                'records_on_current_page' => (string) $cart_item_data->count(),
//                                'record_from' => (string) $cart_item_data->firstItem(),
//                                'record_to' => (string) $cart_item_data->lastItem(),
//                                'per_page' => (string) $cart_item_data->perPage(),
//                            ],
                            "cart_items" => $cart_data,
                            "cart_summary" => $cart_summary,
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
                        'message' =>  __('messages.un_authorized'),
                        'message_code' => 'un_authorized',
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

    public function deleteUserCartItem(Request $request)
    {
        try
        {
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
                'cart_id' => 'required',
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

            if(auth('api')->check())
            {
                $cart = Cart::findOrFail($request->cart_id);
                $product_for_auction_or_sale = ProductForAuctionOrSale::findOrFail($cart->product_for_auction_or_sale_id);
                $product_cart_count = $product_for_auction_or_sale->cart_count;
                if($product_cart_count)ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['cart_count' => $product_cart_count - 1]);
                $cart->delete();

                $cart_item_query = Cart::query();
                $cart_item_query->where('user_id', auth('api')->id());

                $cart_item_count_query = clone $cart_item_query;
                $cart_item_all_query = clone $cart_item_query;
                $cart_item_count = $cart_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $cart_item_count;
                $cart_item_data = $cart_item_query->paginate($per_page);
                $cart_data = cartData($cart_item_data->items()); // defined in helpers

                $cart_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                foreach ($cart_item_all_query->get() as $cart_item)
                {
                    $is_available = 1;
                    if($cart_item->sale_type == 'Sale')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }
                        $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                        $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                        $regular_price = $regular_price + $regular_price_temp;
                        $final_price = $final_price + $final_price_temp;
                        $discount_temp = $regular_price_temp - $final_price_temp;
                        $discount = $discount + $discount_temp;
                    }
                    elseif($cart_item->sale_type == 'Bulk')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }

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

                    }

                }
                $convenience_fees = 0;
                $delivery_charge = 0;
                $grand_total = ($final_price + $delivery_charge + $convenience_fees);
                $cart_summary = [
                    "total_items" => (string) $cart_item_count,
                    "sub_total" => (string) $final_price,
                    "discount_amount" => (string) 0,
                    "delivery_charge" => (string) $delivery_charge,
                    "total_amount" => (string) $grand_total,
                ];

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  __('messages.cart_deleted_success'),
                        'message_code' => 'cart_deleted_success',
                        'data' => [
//                            'meta' =>[
//                                'total_pages' => (string) $cart_item_data->lastPage(),
//                                'current_page' => (string) $cart_item_data->currentPage(),
//                                'total_records' => (string) $cart_item_data->total(),
//                                'records_on_current_page' => (string) $cart_item_data->count(),
//                                'record_from' => (string) $cart_item_data->firstItem(),
//                                'record_to' => (string) $cart_item_data->lastItem(),
//                                'per_page' => (string) $cart_item_data->perPage(),
//                            ],
                            "cart_items" => $cart_data,
                            "cart_summary" => $cart_summary,
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
                        'message' =>  __('messages.un_authorized'),
                        'message_code' => 'un_authorized',
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

    public function clearUserCartItems(Request $request)
    {
        try
        {
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

            if(auth('api')->check())
            {
                $carts = Cart::where('user_id', auth('api')->id())->get();
                foreach ($carts as $cart)
                {
                    $product_for_auction_or_sale = ProductForAuctionOrSale::findOrFail($cart->product_for_auction_or_sale_id);
                    $product_cart_count = $product_for_auction_or_sale->cart_count;
                    if($product_cart_count)ProductForAuctionOrSale::where('id', $product_for_auction_or_sale->id)->update(['cart_count' => $product_cart_count - 1]);
                    $cart->delete();
                }


                $cart_item_query = Cart::query();
                $cart_item_query->where('user_id', auth('api')->id());

                $cart_item_count_query = clone $cart_item_query;
                $cart_item_all_query = clone $cart_item_query;
                $cart_item_count = $cart_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $cart_item_count;
                $cart_item_data = $cart_item_query->paginate($per_page);
                $cart_data = cartData($cart_item_data->items()); // defined in helpers

                $cart_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                foreach ($cart_item_all_query->get() as $cart_item)
                {
                    $is_available = 1;
                    if($cart_item->sale_type == 'Sale')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }
                        $final_price_temp = $cart_item->product_variant->getFinalPrice() * $cart_item->quantity;
                        $regular_price_temp = $cart_item->product_variant->getRegularPrice() * $cart_item->quantity;
                        $regular_price = $regular_price + $regular_price_temp;
                        $final_price = $final_price + $final_price_temp;
                        $discount_temp = $regular_price_temp - $final_price_temp;
                        $discount = $discount + $discount_temp;
                    }
                    elseif($cart_item->sale_type == 'Bulk')
                    {
                        if($cart_item->sale_type != $cart_item->product->sale_type)
                        {
                            $is_available = 0;
                        }

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

                    }

                }
                $convenience_fees = 0;
                $delivery_charge = 0;
                $grand_total = ($final_price + $delivery_charge + $convenience_fees);
                $cart_summary = [
                    "total_items" => (string) $cart_item_count,
                    "sub_total" => (string) $final_price,
                    "discount_amount" => (string) 0,
                    "delivery_charge" => (string) $delivery_charge,
                    "total_amount" => (string) $grand_total,
                ];

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' =>  __('messages.cart_cleared_success'),
                        'message_code' => 'cart_deleted_success',
                        'data' => [
//                            'meta' =>[
//                                'total_pages' => (string) $cart_item_data->lastPage(),
//                                'current_page' => (string) $cart_item_data->currentPage(),
//                                'total_records' => (string) $cart_item_data->total(),
//                                'records_on_current_page' => (string) $cart_item_data->count(),
//                                'record_from' => (string) $cart_item_data->firstItem(),
//                                'record_to' => (string) $cart_item_data->lastItem(),
//                                'per_page' => (string) $cart_item_data->perPage(),
//                            ],
                            "cart_items" => $cart_data,
                            "cart_summary" => $cart_summary,
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
                        'message' =>  __('messages.un_authorized'),
                        'message_code' => 'un_authorized',
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
}

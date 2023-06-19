<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserOrderController extends Controller
{
    public function getUserOrderedItems(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
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

                $ordered_item_query = OrderDetail::query();
                $ordered_item_query->where('user_id', auth('api')->id());

                $ordered_item_count_query = clone $ordered_item_query;
                $ordered_item_all_query = clone $ordered_item_query;
                $ordered_item_count = $ordered_item_count_query->count();
                if(empty($request->get('page'))) $per_page = $ordered_item_count;
                $ordered_item_data = $ordered_item_query->orderBy('created_at', 'DESC')->paginate($per_page);
                $ordered_data = $ordered_item_data->items();

                $ordered_summary = [];
                $final_price = 0;
                $regular_price = 0;
                $discount = 0;
                $ordered_items = orderData($ordered_data, $time_zone);

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' => __('messages.success'),
                        'message_code' => 'user_ordered_success',
                        'data' => [
                            'meta' =>[
                                'total_pages' => (string) $ordered_item_data->lastPage(),
                                'current_page' => (string) $ordered_item_data->currentPage(),
                                'total_records' => (string) $ordered_item_data->total(),
                                'records_on_current_page' => (string) $ordered_item_data->count(),
                                'record_from' => (string) $ordered_item_data->firstItem(),
                                'record_to' => (string) $ordered_item_data->lastItem(),
                                'per_page' => (string) $ordered_item_data->perPage(),
                            ],
                            "ordered_items" => $ordered_items,

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

    public function getUserOrderDetail(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
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


            $order = Order::findOrFail($request->order_id);
            $ordered_item_query = OrderDetail::query();
            $ordered_item_query->where('user_id', auth('api')->id());
            $ordered_item_query->where('order_id', $order->id);
            $ordered_item_count_query = clone $ordered_item_query;
            $ordered_item_all_query = clone $ordered_item_query;
            $ordered_item_count = $ordered_item_count_query->count();
            if(empty($request->get('page'))) $per_page = $ordered_item_count;
            $ordered_item_data = $ordered_item_query->paginate($per_page);
            $ordered_data = $ordered_item_data->items();

            $ordered_items = orderData($ordered_data, $time_zone);
            $order_summary = orderSummary($order, $time_zone);
            $payment_details = paymentDetails($order);
            $last_order_address = OrderAddress::where('order_id', $order->id)->first();

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_order_detail_success',
                    'data' => [
                        'delivery_address' => addressDataSingle($last_order_address),
                        "order_summary" => $order_summary,
                        "payment_details" => $payment_details,
                        "ordered_items" => $ordered_items,
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

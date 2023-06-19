<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\ReservedStock;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class VendorSaleOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data_query = Order::query();
            $data_query->select('id','order_id','total_amount', 'order_status', 'created_at', 'user_id', 'total_amount', 'sale_type');
            $data_query->where('sale_type', 'Sale')->whereHas('order_details')->where('payment_status', 'CAPTURED');
            $data_query->where('vendor_id', auth('web')->id());
            $data_query->orderBy('created_at', 'DESC');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.sale_orders.show', $row->id).'">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-eye-fill text-info fs-1"></i>
		                                </span>
                                    </a>';
//                    if($row->attribute_sets()->count())
//                    {
//                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px">
//                                        <span class="svg-icon svg-icon-3">
//                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
//		                                </span>
//                                    </button>';
//                    }
//                    else
//                    {
//                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteAttribute('.$row->id.')" >
//                                        <span class="svg-icon svg-icon-3">
//                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
//		                                </span>
//                                    </button>';
//                    }

                    return $action_button;
                })
                ->addColumn('customer_name', function($row){
                    return $row->user->first_name." ".$row->user->last_name;
                })
                ->addColumn('total_amount', function($row){
                    return $row->total_amount." ".__('messages.kwd');
                })
                ->addColumn('status', function($row){
                    if($row->order_status == 'Accepted')
                    {
                        $status = '<span class="badge badge-primary fw-bold px-4 py-3">'.__('messages.Accepted').'</span>';
                    }
                    elseif($row->order_status == 'InProgress')
                    {
                        $status = '<span class="badge badge-info fw-bold px-4 py-3">'.__('messages.InProgress').'</span>';
                    }
                    elseif($row->order_status == 'ReadyForDelivery')
                    {
                        $status = '<span class="badge badge-primary fw-bold px-4 py-3">'.__('messages.ReadyForDelivery').'</span>';
                    }
                    elseif($row->order_status == 'OutForDelivery')
                    {
                        $status = '<span class="badge badge-light-primary fw-bold px-4 py-3">'.__('messages.OutForDelivery').'</span>';
                    }
                    elseif($row->order_status == 'Delivered')
                    {
                        $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.Delivered').'</span>';
                    }
                    elseif($row->order_status == 'Rescheduled')
                    {
                        $status = '<span class="badge badge-light-danger fw-bold px-4 py-3">'.__('messages.Rescheduled').'</span>';
                    }
                    elseif($row->order_status == 'Cancelled')
                    {
                        $status = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.Cancelled').'</span>';
                    }
                    elseif($row->order_status == 'Completed')
                    {
                        $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.Completed').'</span>';
                    }
                    elseif($row->order_status == 'Pending')
                    {
                        $status = '<span class="badge badge-warning fw-bold px-4 py-3">'.__('messages.Pending').'</span>';
                    }
                    else
                    {
                        $status = "";
                    }

                    return $status;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status', 'customer_name', 'total_amount'])
                ->make(true);
        }

        return view('pages.user.sale_orders.index');
    }

    public function show($id)
    {
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $order = Order::where('id', $id)->where('vendor_id', auth('web')->id())->first();
        $delivery_address_data = OrderAddress::where('order_id', $order->id)->first();
        $delivery_address = addressDataSingle($delivery_address_data);
        $order_details = OrderDetail::where('order_id', $order->id)->get();
        $ordered_items = [];
        $ordered_items = orderData($order_details, $time_zone);
        return view('pages.user.sale_orders.show', compact('order', 'delivery_address', 'ordered_items'));
    }

    function changeStatus(Request $request, $id)
    {
      try
        {
            $order =  Order::where('id', $id)->first();
            $order_current_status = $order->order_status;
            Order::where('id', $id)->where('vendor_id', auth('web')->id())->update(['order_status' => $request->order_status, ]);

            //maintain order history for stock
            if($request->order_status == 'Delivered' || $request->order_status == 'Cancelled'){
                $order_details = OrderDetail::where('order_id', $order->id)->get();
                $order_quantity_change_flag = false;
                if($order_details){
                    foreach($order_details as $order_detail){
                        $order_quantity = $order_detail->quantity;

                        $stock =  Stock::where('product_variant_id', $order_detail->product_variant_id)->first();
                        $available_stock_quantity = $stock->product_variant->availableStockQuantity();

                        if($request->order_status == 'Delivered'){
                            if($stock->quantity < $order_quantity)
                            {
                                return response()->json([
                                    'success' => false,
                                    'status_code' => 200,
                                    'message' => __('messages.stock_must_be_less_than_or_equal_to_available_stock'),
                                    'message_code' => 'stock_must_be_less_than_or_equal_to_available_stock',
                                ]);
                            }
                            $stock_type1 = "OrderDelivered";
                            $new_stock_quantity = ($stock->quantity - $order_quantity);
                            $order_quantity_change_flag = true;
                        }else if($request->order_status == 'Cancelled'){
                            $stock_type1 = "OrderCancelled";
                            $new_stock_quantity = ($stock->quantity + $order_quantity);
                            if($order_current_status == 'Delivered'){
                                $order_quantity_change_flag = true;
                            }
                        }
                        if($order_quantity_change_flag){
                            Stock::where('product_variant_id', $order_detail->product_variant_id)->update(['quantity' => $new_stock_quantity]);
                        }

                        $stock_history = new StockHistory();
                        $stock_history->product_variant_id = $stock->product_variant_id;
                        $stock_history->user_boutique_id = $stock->user_boutique_id;
                        $stock_history->stock_id = $stock->id;
                        $stock_history->order_id = $order->id;
                        $stock_history->quantity = $order_quantity;
                        $stock_history->add_by = "AddByVendor";
                        $stock_history->stock_type = $stock_type1;
                        $stock_history->created_by_user_id = auth('web')->id();
                        $stock_history->updated_by_user_id = auth('web')->id();
                        $stock_history->save();

                    }
                }

                //Removed entry from reserved stock table for order delivered or cancelled
                $reserved_stocks = ReservedStock::where(['order_id' => $id])->delete();
            }

            sendOrderUpdates($id);
            $order =  Order::where('id', $id)->first();
            $push_title_en = "Order Status Changed To ".__('messages.'.$order->order_status);
            $push_message_en = "Order Status Changed To ".__('messages.'.$order->order_status);
            $push_title_ar = "Order Status Changed To ".__('messages.'.$order->order_status);
            $push_message_ar = "Order Status Changed To ".__('messages.'.$order->order_status);
            $push_target = "User";
            $user_ids = User::where('id', $order->user_id)->pluck('id')->toArray();
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
           return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.update_success'),
                    'message_code' => 'success',
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
     }
}

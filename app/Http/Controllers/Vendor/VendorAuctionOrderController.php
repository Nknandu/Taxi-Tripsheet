<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class VendorAuctionOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data_query = Order::query();
            $data_query->select('id','order_id','total_amount', 'order_status', 'created_at', 'user_id', 'total_amount', 'sale_type');
            $data_query->where('sale_type', 'Auction')->whereHas('order_details')->where('payment_status', 'CAPTURED');
            $data_query->where('vendor_id', auth('web')->id());
            $data_query->orderBy('created_at', 'DESC');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.auction_orders.show', $row->id).'">
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

        return view('pages.user.auction_orders.index');
    }

    public function show($id)
    {
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $order = Order::where('id', $id)->first();
        $delivery_address_data = OrderAddress::where('order_id', $order->id)->first();
        $delivery_address = addressDataSingle($delivery_address_data);
        $order_details = OrderDetail::where('order_id', $order->id)->get();
        $ordered_items = [];
        $ordered_items = orderData($order_details, $time_zone);
        return view('pages.user.auction_orders.show', compact('order', 'delivery_address', 'ordered_items'));
    }
}

<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VendorStockController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user_boutique_ids = UserBoutique::where('user_id', auth('web')->id())->pluck('id')->toArray();
            $data_query =   Stock::query();
            $data_query->whereHas('product_variant', function ($query_1){
                $query_1->whereNull('product_variants.deleted_at');
            });
            $data = $data_query->whereIn('user_boutique_id', $user_boutique_ids)->orderBy('id', 'DESC')->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="addOrRemoveStock('.$row->product_variant->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-plus-slash-minus text-primary fs-1"></i>
		                                </span>
                                    </a>';
                    $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.stocks.show', $row->id).'" >
                                    <span class="svg-icon svg-icon-3">
                                     <i class="bi bi-eye-fill text-info fs-1"></i>
                                    </span>
                                </a>';

                    return $action_button;
                })
                ->addColumn('status', function($row){
                    if($row->product_variant->availableStockQuantity() > 10)
                    {
                        $status = '<span class="badge py-3 px-4 fs-7 badge-light-primary">'.__('messages.in_stock').'</span>';
                    }
                    elseif($row->product_variant->availableStockQuantity() <= 10 && $row->product_variant->availableStockQuantity() > 0)
                    {
                        $status = '<span class="badge py-3 px-4 fs-7 badge-light-warning">'.__('messages.less_stock').'</span>';
                    }
                    elseif($row->product_variant->availableStockQuantity() == 0)
                    {
                        $status = '<span class="badge py-3 px-4 fs-7 badge-light-danger">'.__('messages.out_of_stock').'</span>';
                    }
                    else
                    {
                        $status = '';
                    }
                    return $status;
                })
                ->addColumn('sku', function($row){
                    return $row->product_variant->sku;
                })
                ->addColumn('quantity', function($row){
                    return $row->quantity;
                })
                ->addColumn('available_quantity', function($row){
                    return $row->product_variant->availableStockQuantity();
                })
                ->addColumn('product', function($row){
                    return $row->product_variant->product->getProductName();
                })
                ->addColumn('updated_on', function($row){
                    if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else { $time_zone = getLocalTimeZone(); }
                    return getReadableLocalTimeFromUtc($row->updated_at, $time_zone);
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status', 'sku', 'product', 'quantity', 'available_quantity'])
                ->make(true);
        }

        return view('pages.user.stocks.index');
    }
    public function create(Request $request)
    {
        try
        {
            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $add_form = view('pages.user.products.stock_add_or_remove', compact('product_variant'))->render();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.something_went_wrong'),
                        'message_code' => 'product_id_required',
                        'errors' => []
                    ], 200);
            }

            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'add_form' => $add_form,
                    'add_title' => __('messages.update_stock'),
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

    public function store(Request $request)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                'stock_type'=>'required',
                'stock'=>'required|numeric|min:1',
                'stock_product_variant_id'=>'required',
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.validation_error'),
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            $product_variant = ProductVariant::where('id', $request->stock_product_variant_id)->first();

            $stock = Stock::where('product_variant_id', $product_variant->id)->first();

            if($request->stock_type == "Add")
            {
                $quantity = $request->stock;
            }
            elseif($request->stock_type == "Remove")
            {
                if($product_variant->availableStockQuantity() < $request->stock)
                {
                    return response()->json([
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.stock_must_be_less_than_or_equal_to_available_stock'),
                        'message_code' => 'stock_must_be_less_than_or_equal_to_available_stock',
                    ]);
                }
                $quantity = $request->stock * -1;
            }
            else
            {
                $quantity = 0 ;
            }
            if($stock)
            {
                $stock->quantity = $stock->quantity + $quantity;
                $stock->updated_by_user_id = auth('web')->id();
                $stock->save();
            }
            else
            {
                $stock = new Stock();
                $stock->product_variant_id = $product_variant->id;
                $stock->user_boutique_id = $product_variant->product->boutique->id;
                $stock->quantity = $request->stock;
                $stock->updated_by_user_id = auth('web')->id();
                $stock->save();
            }

            $stock_history = new StockHistory();
            $stock_history->product_variant_id = $stock->product_variant_id;
            $stock_history->user_boutique_id = $stock->user_boutique_id;
            $stock_history->stock_id = $stock->id;
            $stock_history->quantity = $request->stock;
            $stock_history->add_by = "AddByVendor";
            $stock_history->stock_type = $request->stock_type;
            $stock_history->created_by_user_id = auth('web')->id();
            $stock_history->updated_by_user_id = auth('web')->id();
            $stock_history->save();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'updated_success',
            ]);
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

    public function show(Request $request, $id)
    {
        $user_boutique_ids = UserBoutique::where('user_id', auth('web')->id())->pluck('id')->toArray();
        $stock = Stock::where('id', $id)->first();
        $order = Order::where('id', $id)->first();

        $data_query = StockHistory::where('stock_id', $stock->id);
        $stock_history = $data_query->whereIn('user_boutique_id', $user_boutique_ids)->orderBy('id', 'DESC')->get();

        $product_variant = ProductVariant::where('id', $stock->product_variant_id)->first();

        $product = $product_variant->product;
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else { $time_zone = getLocalTimeZone(); }
        $history = [];
        $order_history = [];
        if($stock_history){
            foreach($stock_history as $key => $stock_1){
                $stock_type = '';
                if($stock_1->stock_type == 'Add'){
                    $stock_type = __('messages.added_quantity');
                }else if($stock_1->stock_type == 'Remove'){
                    $stock_type = __('messages.removed_quantity');
                }else if($stock_1->stock_type == 'OrderCancelled' || $stock_1->stock_type == 'OrderDelivered'){
                    $order = Order::where('id', $stock_1->order_id)->first();
                    if($stock_1->stock_type == 'OrderCancelled'){
                        $stock_type = __('messages.retured_quantity_for_order') . ' #'.$order->order_id;;
                    }else if($stock_1->stock_type == 'OrderDelivered'){
                        $stock_type = __('messages.removed_quantity_for_order') . ' #'.$order->order_id;;
                    }
                }
                $history[strtotime($stock_1->updated_at)]['stock_type'] = $stock_type;
                $history[strtotime($stock_1->updated_at)]['quantity'] = $stock_1->quantity;
                $history[strtotime($stock_1->updated_at)]['updated_at'] = getReadableLocalTimeFromUtc($stock_1->updated_at, $time_zone);
            }
        }

        $all_stock_history = $history;
        return view('pages.user.stocks.show', compact('product_variant','product', 'stock','stock_history','all_stock_history'));
    }
}

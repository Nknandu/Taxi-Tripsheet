<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VendorAuctionTimingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $product = $product_variant->product;
                $product_for_auction_or_sale = $product->product_for_auction_or_sale;
                $data_query =   ProductForAuctionOrSale::query();
                $data_query->select('id','product_id','bid_start_time', 'bid_end_time', 'status');
                $data_query->where('product_id', $product->id);
                $data_query->whereNotNull('bid_start_time');
                $data_query->whereNotNull('bid_end_time');
                $data_query->where('type', 'Auction');
                $data_query->orderBy('id', 'ASC');
                $data = $data_query->get();
                return DataTables::of($data)->addIndexColumn()
                    ->addColumn('action', function($row){
                        $bid_start_time = $row->bid_start_time;
                        $bid_end_time = $row->bid_end_time;
                        $current_time = Carbon::now('UTC')->toDateTimeString();
                        if($current_time > $bid_end_time)
                        {
                            $action_button = '';
                        }
                        else
                        {
                            $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="auctionTimingEditForm('.$row->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';
                        }

                        if($bid_start_time < $current_time)
                        {
                            $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                        }
                        else
                        {
                            $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteAuctionTiming('.$row->id.')" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                        }

                        return $action_button;
                    })
                    ->addColumn('auction_status', function($row){
                        $bid_start_time = $row->bid_start_time;
                        $bid_end_time = $row->bid_end_time;
                        $current_time = Carbon::now('UTC')->toDateTimeString();

                        if($current_time < $bid_start_time)
                        {
                            $status = '<span class="badge badge-warning fw-bold px-4 py-3">'.__('messages.upcoming').'</span>';
                        }
                        elseif($current_time >= $bid_start_time && $current_time <= $bid_end_time)
                        {
                            $status = '<span class="badge badge-info fw-bold px-4 py-3">'.__('messages.ongoing').'</span>';
                        }
                        elseif($current_time > $bid_end_time)
                        {
                            $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.completed').'</span>';
                        }
                        else
                        {
                            $status = '';
                        }

                        return $status;
                    })

                    ->addIndexColumn()
                    ->rawColumns(['action', 'auction_status'])
                    ->make(true);
            }

        }
    }

    public function create(Request $request)
    {
        try
        {
            if($request->product_id)
            {
                $product = Product::where('id', $request->product_id)->first();
                $add_form = view('pages.user.products.auction_timing_create', compact('product'))->render();
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
                    'add_title' => __('messages.add_auction_timing'),
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
                'bid_start_time_end_time'=>'required',
                'auction_timing_product_id'=>'required'
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

            $bid_start_time = null;
            $bid_end_time = null;
            if(isset($request->bid_start_time_end_time) && $request->bid_start_time_end_time)
            {
                $bid_start_time_end_time = explode(' ~ ', $request->bid_start_time_end_time);
                $bid_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[0]), $time_zone);
                $bid_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[1]), $time_zone);
            }

            $product = Product::where('id', $request->auction_timing_product_id)->first();
            $product_for_auction_or_sale_id = $product->product_for_auction_or_sale->id;
            if(!$product_for_auction_or_sale_id)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'product_for_auction_or_sale_id_missing',
                ]);
            }
            $auction_timing = new ProductForAuctionOrSale();
            $auction_timing->parent_id = $product_for_auction_or_sale_id;
            $auction_timing->user_id = $product->product_for_auction_or_sale->user_id;
            $auction_timing->product_id = $product->id;
            $auction_timing->type = "Auction";
            $auction_timing->bid_start_time = $bid_start_time;
            $auction_timing->bid_end_time = $bid_end_time;
            $auction_timing->created_by_user_id = auth('web')->id();
            $auction_timing->updated_by_user_id = auth('web')->id();
            $auction_timing->save();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.create_success'),
                'message_code' => 'created_success',
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
    public function edit(Request $request, $id)
    {
        try
        {
            if($id)
            {
                $auction_timing = ProductForAuctionOrSale::where('id', $id)->first();
                $edit_form = view('pages.user.products.auction_timing_edit', compact('auction_timing'))->render();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.something_went_wrong'),
                        'message_code' => 'id_required',
                        'errors' => []
                    ], 200);
            }

            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'edit_form' => $edit_form,
                    'edit_title' => __('messages.edit_auction_timing'),
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
    public function update(Request $request, $id)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $product_variant = ProductVariant::where('id', $id)->first();
            $validator = Validator::make($request->all(), [
                'bid_start_time_end_time'=>'required'
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

            $bid_start_time = null;
            $bid_end_time = null;
            if(isset($request->bid_start_time_end_time) && $request->bid_start_time_end_time)
            {
                $bid_start_time_end_time = explode(' ~ ', $request->bid_start_time_end_time);
                $bid_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[0]), $time_zone);
                $bid_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[1]), $time_zone);
            }

            $auction_timing = ProductForAuctionOrSale::where('id', $id)->first();
            $auction_timing->bid_start_time = $bid_start_time;
            $auction_timing->bid_end_time = $bid_end_time;
            $auction_timing->updated_by_user_id = auth('web')->id();
            $auction_timing->save();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'updated_success',
            ]);

        }
        catch (\Exception $exception)
        {
            DB::rollBack();
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

    public function destroy($id)
    {
        try
        {
            $auction_timing = ProductForAuctionOrSale::where('id', $id)->where('parent_id', '!=', NULL)->first();
            if($auction_timing)
            {
                $auction_timing->delete();
                return response()->json(
                    [
                        'success' => true,
                        'status_code' => 200,
                        'message' => __('messages.delete_success'),
                        'message_code' => 'success',
                        'errors' => []
                    ], 200);
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.locked'),
                        'message_code' => 'locked',
                        'errors' => []
                    ], 200);
            }

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

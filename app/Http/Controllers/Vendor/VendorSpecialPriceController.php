<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\SpecialPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VendorSpecialPriceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $product = $product_variant->product;
                $product_for_auction_or_sale = $product->product_for_auction_or_sale;
                $data_query =   SpecialPrice::query();
                $data_query->where('product_variant_id', $request->product_variant_id);
                $data_query->orderBy('id', 'DESC');
                $data = $data_query->get();
                return DataTables::of($data)->addIndexColumn()
                    ->addColumn('action', function($row){

                        $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="specialPriceEditForm('.$row->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';
                        if(0)
                        {
                            $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                        }
                        else
                        {
                            $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteSpecialPrice('.$row->id.')" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                        }

                        return $action_button;
                    })
                    ->addColumn('final_price', function($row){
                        return $row->final_price." ".__('messages.kwd');
                    })
                    ->addColumn('regular_price', function($row){
                        return $row->regular_price." ".__('messages.kwd');
                    })
                    ->addColumn('cost', function($row){
                        return $row->cost." ".__('messages.kwd');
                    })
                    ->addColumn('bid_value', function($row){
                        return $row->bid_value." ".__('messages.kwd');
                    })
                    ->addColumn('bid_start_price', function($row){
                        return $row->bid_start_price." ".__('messages.kwd');
                    })
                    ->addColumn('estimate_start_price', function($row){
                        return $row->estimate_start_price." ".__('messages.kwd');
                    })
                    ->addColumn('estimate_end_price', function($row){
                        return $row->estimate_end_price." ".__('messages.kwd');
                    })
                    ->addIndexColumn()
                    ->rawColumns(['action', 'final_price', 'cost', 'bid_value', 'bid_start_price', 'estimate_start_price', 'estimate_end_price', 'regular_price'])
                    ->make(true);
            }

        }
    }

    public function create(Request $request)
    {
        try
        {
            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $sale_type = $product_variant->product->sale_type;
                $add_form = view('pages.user.products.special_price_create', compact('product_variant', 'sale_type'))->render();
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
                    'add_title' => __('messages.add_special_price'),
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
                'price_start_time_end_time'=>'required',
                'special_price_sale_type'=>'required',
                'special_price_product_variant_id'=>'required',
                'regular_price' => 'sometimes|required_if:special_price_sale_type,!=,Auction|numeric|min:0',
                'final_price' => 'sometimes|required_if:special_price_sale_type,!=,Auction|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required_if:special_price_sale_type,==,Bulk|numeric|min:0',
                'bid_value' => 'sometimes|required_if:special_price_sale_type,==,Auction|numeric|min:0',
                'bid_start_price' => 'sometimes|required_if:special_price_sale_type,==,Auction|numeric|min:0',
                'estimate_start_price' => 'sometimes|required_if:special_price_sale_type,==,Sale|numeric|min:0',
                'estimate_end_price' => 'sometimes|required_if:special_price_sale_type,==,Sale|numeric|min:0:gt:estimate_start_price',
                'cost' => 'sometimes|required|numeric|min:0',
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

            $price_start_time = null;
            $price_end_time = null;
            if(isset($request->price_start_time_end_time) && $request->price_start_time_end_time)
            {
                $price_start_time_end_time = explode(' ~ ', $request->price_start_time_end_time);
                $price_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($price_start_time_end_time[0]), $time_zone);
                $price_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($price_start_time_end_time[1]), $time_zone);
            }

            $product_variant = ProductVariant::where('id', $request->special_price_product_variant_id)->first();

            $special_price = new SpecialPrice();
            $special_price->product_variant_id = $product_variant->id;
            $special_price->start_time = $price_start_time;
            $special_price->end_time = $price_end_time;
            if(isset($request->final_price) && $request->final_price)$special_price->final_price = $request->final_price;
            if(isset($request->regular_price) && $request->regular_price)$special_price->regular_price = $request->regular_price;
            if(isset($request->incremental_price) && $request->incremental_price)$special_price->incremental_price = $request->incremental_price;
            if(isset($request->cost) && $request->cost)$special_price->cost = $request->cost;
            if(isset($request->bid_start_price) && $request->bid_start_price)$special_price->bid_start_price = $request->bid_start_price;
            if(isset($request->bid_value) && $request->bid_value)$special_price->bid_value = $request->bid_value;
            if(isset($request->estimate_start_price) && $request->estimate_start_price)$special_price->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_prce) && $request->estimate_end_prce)$special_price->estimate_end_prce = $request->estimate_end_prce;
            $special_price->created_by_user_id = auth('web')->id();
            $special_price->updated_by_user_id = auth('web')->id();
            $special_price->save();

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
                $special_price = SpecialPrice::where('id', $id)->first();
                $product_variant = ProductVariant::where('id', $special_price->product_variant_id)->first();
                $sale_type = $product_variant->product->sale_type;
                $edit_form = view('pages.user.products.special_price_edit', compact('special_price', 'product_variant', 'sale_type'))->render();
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
                    'edit_title' => __('messages.edit_special_price'),
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
                'price_start_time_end_time'=>'required',
                'special_price_sale_type'=>'required',
                'special_price_product_variant_id'=>'required',
                'regular_price' => 'sometimes|required_if:special_price_sale_type,!=,Auction|numeric|min:0',
                'final_price' => 'sometimes|required_if:special_price_sale_type,!=,Auction|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required_if:special_price_sale_type,==,Bulk|numeric|min:0',
                'bid_value' => 'sometimes|required_if:special_price_sale_type,==,Auction|numeric|min:0',
                'bid_start_price' => 'sometimes|required_if:special_price_sale_type,==,Auction|numeric|min:0',
                'estimate_start_price' => 'sometimes|required_if:special_price_sale_type,==,Sale|numeric|min:0',
                'estimate_end_price' => 'sometimes|required_if:special_price_sale_type,==,Sale|numeric|min:0:gt:estimate_start_price',
                'cost' => 'sometimes|required|numeric|min:0',
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

            $price_start_time = null;
            $price_end_time = null;
            if(isset($request->price_start_time_end_time) && $request->price_start_time_end_time)
            {
                $price_start_time_end_time = explode(' ~ ', $request->price_start_time_end_time);
                $price_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($price_start_time_end_time[0]), $time_zone);
                $price_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($price_start_time_end_time[1]), $time_zone);
            }

            $product_variant = ProductVariant::where('id', $request->special_price_product_variant_id)->first();

            $special_price = SpecialPrice::where('id', $id)->first();
            $special_price->start_time = $price_start_time;
            $special_price->end_time = $price_end_time;
            if(isset($request->final_price) && $request->final_price)$special_price->final_price = $request->final_price;
            if(isset($request->regular_price) && $request->regular_price)$special_price->regular_price = $request->regular_price;
            if(isset($request->incremental_price) && $request->incremental_price)$special_price->incremental_price = $request->incremental_price;
            if(isset($request->cost) && $request->cost)$special_price->cost = $request->cost;
            if(isset($request->bid_start_price) && $request->bid_start_price)$special_price->bid_start_price = $request->bid_start_price;
            if(isset($request->bid_value) && $request->bid_value)$special_price->bid_value = $request->bid_value;
            if(isset($request->estimate_start_price) && $request->estimate_start_price)$special_price->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_prce) && $request->estimate_end_prce)$special_price->estimate_end_prce = $request->estimate_end_prce;
            $special_price->updated_by_user_id = auth('web')->id();
            $special_price->save();


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
            $special_price = SpecialPrice::where('id', $id)->first();
            if($special_price)
            {
                $special_price->delete();
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

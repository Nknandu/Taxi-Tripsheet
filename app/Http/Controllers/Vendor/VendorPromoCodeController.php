<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VendorPromoCodeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user_boutiques_ids = UserBoutique::where('user_id', auth('web')->id())->distinct()->pluck('id')->toArray();
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $data_query =   PromoCode::query();
            $data_query->whereHas('user_boutiques', function ($query_10) use ($user_boutiques_ids) {
                $query_10->whereIn("user_boutiques.id", $user_boutiques_ids);
            });
            $data = $data_query->latest()->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
//                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.promo_codes.show', $row->id).'">
//                                        <span class="svg-icon svg-icon-3">
//                                         <i class="bi bi-eye-fill text-info fs-1"></i>
//		                                </span>
//                                    </a>';

                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.promo_codes.edit', $row->id).'">
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
                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deletePromoCode('.$row->id.')" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                    }

                    return $action_button;
                })
                ->addColumn('status', function($row) use ($current_date_time){
                    if($row->status)
                    {
                        $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.active').'</span>';
                        if($row->start_time && $row->end_time)
                        {
                            if($current_date_time < $row->start_time)
                            {
                                $status = '<span class="badge badge-info fw-bold px-4 py-3">'.__('messages.upcoming').'</span>';
                            }
                            elseif($current_date_time > $row->end_time)
                            {
                                $status = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.expired').'</span>';
                            }
                            elseif($current_date_time >= $row->start_time && $current_date_time <= $row->end_time)
                            {
                                $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.active').'</span>';
                            }
                        }

                    }
                    else
                    {
                        $status = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.inactive').'</span>';
                    }

                    return $status;
                })
                ->addColumn('free_delivery', function($row){
                    if($row->free_delivery)
                    {
                        $free_delivery = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.yes').'</span>';
                    }
                    else
                    {
                        $free_delivery = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.no').'</span>';
                    }

                    return $free_delivery;
                })

                ->addColumn('discount_type', function($row){
                    if($row->discount_type == "Flat")
                    {
                        $discount_type = '<span class="badge badge-info fw-bold px-4 py-3">'.__('messages.Flat').'</span>';
                    }
                    elseif($row->discount_type == "Percentage")
                    {
                        $discount_type = '<span class="badge badge-primary fw-bold px-4 py-3">'.__('messages.Percentage').'</span>';
                    }
                    else
                    {
                        $discount_type ="";
                    }

                    return $discount_type;
                })

                ->addColumn('discount', function($row){
                    if($row->discount_type == "Flat")
                    {
                        $discount_type = __('messages.kwd');
                    }
                    elseif($row->discount_type == "Percentage")
                    {
                        $discount_type = "%";
                    }
                    else
                    {
                        $discount_type = "";
                    }

                    return $row->discount." ".$discount_type;
                })
                ->addColumn('max_discount', function($row){
                    $max_discount = "";
                    if($row->max_discount)
                    {
                        $max_discount = $row->max_discount." ".__('messages.kwd');
                    }

                    return $max_discount;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status', 'free_delivery', 'discount_type', 'discount', 'max_discount'])
                ->make(true);
        }

        return view('pages.user.promo_codes.index');
    }


    public function create(Request $request)
    {
        $user_boutiques = UserBoutique::where('user_id', auth('web')->id())->latest()->get();
        return view('pages.user.promo_codes.create', compact('user_boutiques'));
    }


    public function store(Request $request)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                'title' => 'required|max:100',
                'title_ar' => 'required|max:100',
                'code' => 'required|max:30|unique:promo_codes',
                'user_limit' => 'nullable|numeric|min:1',
                'per_user_limit' => 'nullable|numeric|min:1',
                'discount_type' => 'required',
                'discount' => 'required|numeric|min:1',
                'max_discount' => 'nullable|numeric|min:1',
                'boutique' => 'required|min:1',
                "boutique.*"  => "required|min:1",
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

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            $free_delivery = false;
            if(isset($request->free_delivery) && $request->free_delivery == 1)
            {
                $free_delivery = true;
            }

            $user_limit = 0;
            if(isset($request->user_limit) && $request->user_limit)
            {
                $user_limit = $request->user_limit;
            }

            $per_user_limit = 0;
            if(isset($request->per_user_limit) && $request->per_user_limit)
            {
                $per_user_limit = $request->per_user_limit;
            }

            $max_discount = 0;
            if(isset($request->max_discount) && $request->max_discount)
            {
                $max_discount = $request->max_discount;
            }

            $start_time = null;
            $end_time = null;
            if(isset($request->start_time_end_time) && $request->start_time_end_time)
            {
                $start_time_end_time = explode(' ~ ', $request->start_time_end_time);
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[0]), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[1]), $time_zone);
            }

            $promo_code = new PromoCode();
            $promo_code->title = $request->title;
            $promo_code->title_ar = $request->title_ar;
            $promo_code->discount_type = $request->discount_type;
            $promo_code->discount = $request->discount;
            $promo_code->max_discount = $max_discount;
            $promo_code->user_limit = $user_limit;
            $promo_code->per_user_limit = $per_user_limit;
            $promo_code->code = $request->code;;
            $promo_code->start_time = $start_time;
            $promo_code->end_time = $end_time;
            $promo_code->free_delivery = $free_delivery;
            $promo_code->status = $status;
            $promo_code->created_by_user_id = auth('web')->id();
            $promo_code->updated_by_user_id = auth('web')->id();
            $promo_code->save();

            $promo_code->user_boutiques()->attach($request->boutique);

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


    public function show($id)
    {
        $user_boutiques_ids = UserBoutique::where('user_id', auth('web')->id())->distinct()->pluck('id')->toArray();
        $promo_code = PromoCode::where('id', $id)->whereHas('user_boutiques', function ($query_10) use ($user_boutiques_ids) {
        $query_10->whereIn("user_boutiques.id", $user_boutiques_ids);
         })->first();
        return view('pages.user.promo_codes.show', compact('promo_code'));
    }


    public function edit(Request $request, $id)
    {
        $user_boutiques_ids = UserBoutique::where('user_id', auth('web')->id())->distinct()->pluck('id')->toArray();
        $user_boutiques = UserBoutique::where('user_id', auth('web')->id())->latest()->get();
        $promo_code = PromoCode::where('id', $id)->whereHas('user_boutiques', function ($query_10) use ($user_boutiques_ids) {
            $query_10->whereIn("user_boutiques.id", $user_boutiques_ids);
        })->first();
        $assigned_boutiques  = $promo_code->user_boutiques->pluck('id')->toArray();
        return view('pages.user.promo_codes.edit', compact( 'promo_code', 'user_boutiques', 'assigned_boutiques'));
    }


    public function update(Request $request, $id)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $validator = Validator::make($request->all(), [
                'title' => 'required|max:100',
                'title_ar' => 'required|max:100',
                'code' => 'required|max:30|unique:promo_codes,code,'.$id,
                'user_limit' => 'nullable|numeric|min:1',
                'per_user_limit' => 'nullable|numeric|min:1',
                'discount_type' => 'required',
                'discount' => 'required|numeric|min:1',
                'max_discount' => 'nullable|numeric|min:1',
                'boutique' => 'required|min:1',
                "boutique.*"  => "required|min:1",
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

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            $free_delivery = false;
            if(isset($request->free_delivery) && $request->free_delivery == 1)
            {
                $free_delivery = true;
            }

            $start_time = null;
            $end_time = null;
            if(isset($request->start_time_end_time) && $request->start_time_end_time)
            {
                $start_time_end_time = explode(' ~ ', $request->start_time_end_time);
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[0]), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[1]), $time_zone);
            }

            $user_limit = 0;
            if(isset($request->user_limit) && $request->user_limit)
            {
                $user_limit = $request->user_limit;
            }

            $per_user_limit = 0;
            if(isset($request->per_user_limit) && $request->per_user_limit)
            {
                $per_user_limit = $request->per_user_limit;
            }

            $max_discount = 0;
            if(isset($request->max_discount) && $request->max_discount)
            {
                $max_discount = $request->max_discount;
            }

            $promo_code = PromoCode::where('id', $id)->first();
            $promo_code->title = $request->title;
            $promo_code->title_ar = $request->title_ar;
            $promo_code->discount_type = $request->discount_type;
            $promo_code->discount = $request->discount;
            $promo_code->max_discount = $max_discount;
            $promo_code->user_limit = $user_limit;
            $promo_code->per_user_limit = $per_user_limit;
            $promo_code->code = $request->code;;
            $promo_code->start_time = $start_time;
            $promo_code->end_time = $end_time;
            $promo_code->status = $status;
            $promo_code->free_delivery = $free_delivery;
            $promo_code->updated_by_user_id = auth('web')->id();
            $promo_code->save();

            $promo_code->user_boutiques()->sync($request->boutique);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'update_success',
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


    public function destroy($id)
    {
        try
        {
            $promo_code = PromoCode::where('id', $id)->first();
            $promo_code->delete();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.delete_success'),
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

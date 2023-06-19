<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueCategory;
use App\Models\User;
use App\Models\UserBoutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Image;

class VendorUserBoutiqueController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data_query =   UserBoutique::query();
            $data_query->where('user_id', auth('web')->id());
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
//                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.user_boutiques.show', $row->id).'">
//                                        <span class="svg-icon svg-icon-3">
//                                         <i class="bi bi-eye-fill text-info fs-1"></i>
//		                                </span>
//                                    </a>';

                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('user.user_boutiques.edit', $row->id).'">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';

                    return $action_button;
                })
                ->addColumn('name', function($row){
                    return $row->getName();
                })
                ->addColumn('unique_id', function($row){
                    return "BTQ".$row->id;
                })
                ->addColumn('user', function($row){
                    return $row->user->first_name." ".$row->user->last_name;
                })
                ->addColumn('commission_percentage', function($row){
                    return $row->commission_percentage;
                })
                ->addColumn('delivery_handle', function($row){
                    if($row->delivery_handle == "Beiaat")
                    {
                        return __('messages.beiaat');
                    }
                    elseif ($row->delivery_handle == "SelfDelivery")
                    {
                        return __('messages.self_delivery');
                    }
                    else
                    {
                        return "";
                    }
                })
                ->addColumn('status', function($row){
                    if($row->status)
                    {
                        $status = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.active').'</span>';
                    }
                    else
                    {
                        $status = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.inactive').'</span>';
                    }

                    return $status;
                })
                ->addColumn('is_featured', function($row){
                    if($row->is_featured)
                    {
                        $is_featured = '<span class="badge badge-success fw-bold px-4 py-3">'.__('messages.yes').'</span>';
                    }
                    else
                    {
                        $is_featured = '<span class="badge badge-danger fw-bold px-4 py-3">'.__('messages.no').'</span>';
                    }

                    return $is_featured;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'name', 'user', 'commission_percentage', 'delivery_handle','unique_id', 'status', 'is_featured'])
                ->make(true);
        }

        return view('pages.user.user_boutiques.index');
    }


    public function create(Request $request)
    {
        $vendors = User::get();
        $boutique_categories = BoutiqueCategory::where('status', 1)->get();
        return view('pages.user.user_boutiques.create', compact('vendors', 'boutique_categories'));
    }


    public function store(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
//                'vendor_or_user' => 'required',
                'name' => 'required|max:100',
                'name_ar' => 'required|max:100',
                'image' => 'required|mimes:jpeg,jpg,png,gif',
                'cover_image' => 'required|mimes:jpeg,jpg,png,gif',
                'description' => 'required',
                'description_ar' => 'required',
                'delivery_text' => 'required',
                'delivery_text_ar' => 'required',
                'commission_percentage' => 'required|numeric|min:0',
                'delivery_handle' => 'required',
                'delivery_charge' => 'required_if:delivery_handle,==,SelfDelivery|numeric|min:0',
                'boutique_categories' => 'required|min:1',
                "boutique_categories.*"  => "required|min:1",
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

            $user_boutique_count = UserBoutique::where('user_id', auth('web')->id())->count();
            if($user_boutique_count)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.boutique_limit'),
                    'message_code' => 'boutique_limit',
                ]);
            }

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            $is_featured = false;
            if(isset($request->is_featured) && $request->is_featured == 1)
            {
                $is_featured = true;
            }

            $slug = Str::slug($request->name, '-');
            $image_name_in_db = null;
            if($request->hasfile('image'))
            {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(200, 200, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/user_boutiques/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/user_boutiques/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }

            $slug = Str::slug($request->name, '-');
            $cover_image_name_in_db = null;
            if($request->hasfile('cover_image'))
            {
                $cover_image = $request->file('cover_image');
                $cover_image_name = $slug."-cover-".time().'.'.$cover_image->extension();
                if(strtolower($cover_image->extension()) == 'gif')
                {
                    $cover_original_image_file = $cover_thumb_image_file = file_get_contents($request->cover_image);
                }
                else
                {
                    $cover_original_image = Image::make($cover_image);
                    $cover_thumb_image = Image::make($cover_image)->fit(600, 400, function ($constraint) { });
                    $cover_original_image_file = $cover_original_image->stream()->__toString();
                    $cover_thumb_image_file = $cover_thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/user_boutiques/original/'.$cover_image_name, $cover_original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/user_boutiques/thumb/'.$cover_image_name, $cover_thumb_image_file, ['visibility' => 'public']))
                    {
                        $cover_image_name_in_db = $cover_image_name;
                    }
                }
            }

            $user_boutique = new UserBoutique();
            $user_boutique->user_id = auth('web')->id();
            $user_boutique->name = $request->name;
            $user_boutique->name_ar = $request->name_ar;
            $user_boutique->description = $request->description;
            $user_boutique->description_ar = $request->description_ar;
            $user_boutique->delivery_handle = $request->delivery_handle;
            $user_boutique->delivery_text = $request->delivery_text;
            $user_boutique->delivery_text_ar = $request->delivery_text_ar;
            $user_boutique->commission_percentage = $request->commission_percentage;
            $user_boutique->cover_image = $cover_image_name_in_db;
            if($request->delivery_handle == 'SelfDelivery') if(isset($request->delivery_charge) && $request->delivery_charge)$user_boutique->delivery_charge = $request->delivery_charge;
            $user_boutique->status = $status;
            $user_boutique->is_featured = $is_featured;
            $user_boutique->image = $image_name_in_db;
            $user_boutique->created_by_user_id = auth('web')->id();
            $user_boutique->updated_by_user_id = auth('web')->id();
            $user_boutique->save();

            $user_boutique->boutique_categories()->attach($request->boutique_categories);

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
//        $user_boutique = UserBoutique::where('id', $id)->first();
//        return view('pages.user.user_boutiques.show', compact('user_boutique'));
    }


    public function edit(Request $request, $id)
    {
        $user_boutique = UserBoutique::where('id', $id)->where('user_id', auth('web')->id())->first();
        $vendors = User::get();
        $boutique_categories = BoutiqueCategory::where('status', 1)->get();
        $assigned_categories  = ($user_boutique->boutique_categories) ? $user_boutique->boutique_categories->pluck('id')->toArray() : [];
        return view('pages.user.user_boutiques.edit', compact( 'user_boutique', 'vendors', 'boutique_categories', 'assigned_categories'));
    }


    public function update(Request $request, $id)
    {
        try
        {
            $validator = Validator::make($request->all(), [
//                'vendor_or_user' => 'required',
                'name' => 'required|max:100',
                'name_ar' => 'required|max:100',
                'image' => 'mimes:jpeg,jpg,png,gif',
                'cover_image' => 'mimes:jpeg,jpg,png,gif',
                'description' => 'required',
                'description_ar' => 'required',
                'delivery_text' => 'required',
                'delivery_text_ar' => 'required',
//                'commission_percentage' => 'required|numeric|min:0',
                'delivery_handle' => 'required',
                'delivery_charge' => 'required_if:delivery_handle,==,SelfDelivery|numeric|min:0',
                'boutique_categories' => 'required|min:1',
                "boutique_categories.*"  => "required|min:1",
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

            $is_featured = false;
            if(isset($request->is_featured) && $request->is_featured == 1)
            {
                $is_featured = true;
            }

            $user_boutique = UserBoutique::where('id', $id)->where('user_id', auth('web')->id())->first();

            $slug = Str::slug($request->name, '-');
            $image_name_in_db = null;
            if($request->hasfile('image'))
            {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(200, 200, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/user_boutiques/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/user_boutiques/original/'.$user_boutique->image))
                    {
                        Storage::disk('public')->delete('uploads/user_boutiques/original/'.$user_boutique->image);
                    }
                    if(Storage::disk('public')->put('uploads/user_boutiques/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/user_boutiques/thumb/'.$user_boutique->image))
                        {
                            Storage::disk('public')->delete('uploads/user_boutiques/thumb/'.$user_boutique->image);
                        }
                        $image_name_in_db = $image_name;
                        $user_boutique->image = $image_name_in_db;
                    }
                }
            }

            $cover_image_name_in_db = null;
            if($request->hasfile('cover_image'))
            {
                $cover_image = $request->file('cover_image');
                $cover_image_name = $slug."-cover-".time().'.'.$cover_image->extension();
                if(strtolower($cover_image->extension()) == 'gif')
                {
                    $cover_original_image_file = $cover_thumb_image_file = file_get_contents($request->cover_image);
                }
                else
                {
                    $cover_original_image = Image::make($cover_image);
                    $cover_thumb_image = Image::make($cover_image)->fit(600, 400, function ($constraint) { });
                    $cover_original_image_file = $cover_original_image->stream()->__toString();
                    $cover_thumb_image_file = $cover_thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/user_boutiques/original/'.$cover_image_name, $cover_original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/user_boutiques/original/'.$user_boutique->cover_image))
                    {
                        Storage::disk('public')->delete('uploads/user_boutiques/original/'.$user_boutique->cover_image);
                    }
                    if(Storage::disk('public')->put('uploads/user_boutiques/thumb/'.$cover_image_name, $cover_thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/user_boutiques/thumb/'.$user_boutique->cover_image))
                        {
                            Storage::disk('public')->delete('uploads/user_boutiques/thumb/'.$user_boutique->cover_image);
                        }
                        $cover_image_name_in_db = $cover_image_name;
                        $user_boutique->cover_image = $cover_image_name_in_db;
                    }
                }
            }

//            $user_boutique->user_id = $request->vendor_or_user;
            $user_boutique->name = $request->name;
            $user_boutique->name_ar = $request->name_ar;
            $user_boutique->description = $request->description;
            $user_boutique->description_ar = $request->description_ar;
            $user_boutique->delivery_handle = $request->delivery_handle;
            $user_boutique->delivery_text = $request->delivery_text;
            $user_boutique->delivery_text_ar = $request->delivery_text_ar;
            $user_boutique->status = $status;
            $user_boutique->is_featured = $is_featured;
            if($request->delivery_handle == 'SelfDelivery') if(isset($request->delivery_charge) && $request->delivery_charge)$user_boutique->delivery_charge = $request->delivery_charge;
//            $user_boutique->commission_percentage = $request->commission_percentage;
            $user_boutique->updated_by_user_id = auth('web')->id();
            $user_boutique->save();

            $user_boutique->boutique_categories()->sync($request->boutique_categories);

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
            $user_boutique = UserBoutique::where('id', $id)->first();
            $user_boutique->delete();
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

    public function getDeliveryHandle(Request $request)
    {
        try
        {
            if(isset($request->vendor_or_user) && $request->vendor_or_user)
            {
                $user_type = User::where('id', $request->vendor_or_user)->first()->user_type;
                if(isset($request->user_boutique_id) && $request->user_boutique_id)
                {
                    $delivery_handle = UserBoutique::where('id', $request->user_boutique_id)->first()->delivery_handle;
                    $delivery_section = view('pages.user.user_boutiques.delivery_handle', compact('user_type','delivery_handle'))->render();
                }
                else
                {
                    $delivery_section =  view('pages.user.user_boutiques.delivery_handle', compact('user_type'))->render();
                }

            }
            else
            {
                $delivery_section = '<option></option>';
            }

            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'delivery_handle' => $delivery_section,
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

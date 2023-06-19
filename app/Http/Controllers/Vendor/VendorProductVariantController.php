<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\AttributeSet;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Models\ProductVariantVideo;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Image;

class VendorProductVariantController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $data_query =   ProductVariant::query();
                $data_query->select('id','product_id','quantity', 'final_price', 'regular_price', 'status', 'sku', 'bid_value', 'bid_start_price');
                $data_query->where('product_id', $product_variant->product_id);
                $data_query->where('id', '!=', $product_variant->id);
                $data_query->orderBy('id', 'DESC');
                $data = $data_query->get();
                return DataTables::of($data)->addIndexColumn()
                    ->addColumn('action', function($row){
                        $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="addOrRemoveStock('.$row->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-plus-slash-minus text-primary fs-1"></i>
		                                </span>
                                    </a>';

                        $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px"  href="'.route('admin.products.show', $row->id).'" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-eye-fill text-info fs-1"></i>
		                                </span>
                                    </a>';

                        $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="productVariantEditForm('.$row->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';
                        if(!$row->isDeletable())
                        {
                            $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
		                                </span>
                                    </a>';
                        }
                        else
                        {
                            $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteProductVariant('.$row->id.')" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                        }

                        return $action_button;
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
                    ->addColumn('sale_type', function($row){
                        if($row->product->sale_type == 'Sale')
                        {
                            $sale_type = '<span class="badge badge-info fw-bold px-4 py-3">'.__('messages.sale').'</span>';
                        }
                        elseif($row->product->sale_type == 'Auction')
                        {
                            $sale_type = '<span class="badge badge-primary fw-bold px-4 py-3">'.__('messages.auction').'</span>';
                        }
                        elseif($row->product->sale_type == 'Bulk')
                        {
                            $sale_type = '<span class="badge badge-warning fw-bold px-4 py-3">'.__('messages.bulk').'</span>';
                        }
                        else
                        {
                            $sale_type = '';
                        }
                        return $sale_type;
                    })
                    ->addColumn('boutique', function($row){
                        return $row->product->boutique->getName()." || ".$row->product->vendor->first_name." ".$row->product->vendor->last_name;
                    })
                    ->addColumn('name', function($row){
                        return $row->product->getProductName();
                    })
                    ->addColumn('attribute_value', function($row){
                        return $row->getAttributeValuePair();
                    })
                    ->addColumn('image', function($row){
                        if($row->default_image)
                        {
                            $img = '<img src="'.$row->default_image->thumb_image.'" alt="user" class="w-50px h-50px">';
                        }
                        else
                        {
                            $img = '<img src="'.asset('assets/media/dummy/no_image.jpeg').'" alt="user" class="w-50px h-50px">';
                        }

                        return $img;
                    })
                    ->addColumn('category', function($row){
                        return $row->product->category_list;
                    })
                    ->addColumn('regular_price_or_bid_start_price', function($row){
                        if($row->product->sale_type == 'Sale' || $row->product->sale_type == 'Bulk'){ return $row->getRegularPrice(); }elseif($row->product->sale_type == 'Auction'){ return $row->getBidStartPrice(); }else{}
                    })
                    ->addColumn('final_price_or_bid_value', function($row){
                        if($row->product->sale_type == 'Sale' || $row->product->sale_type == 'Bulk'){ return $row->getFinalPrice(); }elseif($row->product->sale_type == 'Auction'){ return $row->getBidValue(); }else{}
                    })
                    ->addColumn('quantity', function($row){
                        return  $row->availableStockQuantity();
                    })
                    ->addIndexColumn()
                    ->rawColumns(['action', 'status', 'attribute_value', 'boutique', 'image', 'category', 'final_price_or_bid_value', 'regular_price_or_bid_start_price', 'sale_type', 'quantity'])
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
                $attributes = AttributeSet::where('id', $product->attribute_set_id)->first()->attributes;
                $product_add_form = view('pages.admin.products.product_variant_create', compact('attributes', 'product'))->render();
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
                    'product_add_form' => $product_add_form,
                    'product_add_title' => __('messages.add_product_variant'),
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
            DB::beginTransaction();
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                'sku' => 'required|max:200|unique:product_variants',
                'regular_price' => 'sometimes|required|numeric|min:0',
                'final_price' => 'sometimes|required|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required|numeric|min:0',
                'incremental_quantity' => 'sometimes|required|numeric|min:0',
                'bid_value' => 'sometimes|required|numeric|min:1',
                'bid_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_end_price' => 'sometimes|required|numeric|min:0|gt:estimate_start_price',
                'quantity' => 'required|numeric|min:0',
                'attribute_value' => 'required',
                "attribute_value.*"  => "required",
                'images' => 'required',
                'images.*' => 'required|mimes:jpeg,jpg,png,gif',
                'videos.*' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',
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

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }
            $product = Product::where('id', $request->product_id)->first();
            $user_boutique = UserBoutique::where('id', $product->user_boutique_id)->first();
            $user_vendor = $user_boutique->user;
            $user_package_features = $user_vendor->getPackageFeatures();

            $sale_permission = false;
            if($product->sale_type == "Sale")
            {
                if (in_array('manage-sale', $user_package_features)) $sale_permission = true;
            }
            elseif($product->sale_type == "Auction")
            {
                if(in_array('manage-auction', $user_package_features)) $sale_permission = true;
            }
            elseif($product->sale_type == "Bulk")
            {
                if(in_array('manage-bulk', $user_package_features)) $sale_permission = true;
            }
            else
            {

            }

            if($product->sale_type == "Auction")
            {
                if($product->type != "Simple")
                {
                    return response()->json([
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.simple_auction_product'),
                        'message_code' => 'simple_auction_product',
                    ]);
                }

            }

            if($user_vendor->getPackageStatus() !='active')
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.no_package_available'),
                    'message_code' => 'no_package_available',
                ]);
            }


            if(!$sale_permission)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.no_permission_to_create_product'),
                    'message_code' => 'permission_check',
                ]);
            }



            $slug = Str::slug($product->name, '-');


            $product_attribute_set = AttributeSet::where('id', $product->attribute_set_id)->first();
            $product_attributes_count = $product_attribute_set->attributes->count();

            $product_attributes = $request->product_attribute;
            $attribute_values = $request->attribute_value;


            $combination_exists = false; // combination  exists
            foreach ($product->product_variants as $product_variant_item)
            {
                $product_attribute_value_query = DB::table('product_attributes');
                $product_attribute_value_query->where('product_id', $product->id);
                $product_attribute_value_query->where('product_variant_id', $product_variant_item->id);
                $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
                $product_attribute_value_query->where(function ($query_1) use($product, $product_variant_item, $product_attributes, $attribute_values){
                    foreach ($product_attributes as $key => $product_attribute)
                    {
                        if($key == 0)
                        {
                            $query_1->where(function ($query_2) use($product_attributes, $attribute_values, $key){
                                $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                            });
                        }
                        else
                        {
                            $query_1->orWhere(function ($query_2) use($product_attributes, $attribute_values, $key){
                                $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                            });
                        }
                    }
                });
                $product_attribute_value_count = $product_attribute_value_query->count();
                if($product_attribute_value_count >= $product_attributes_count)
                {
                    $combination_exists = true; // combination exists
                    break;
                }
            }
            /*
            $attribute_dummy = "";
            $attribute_value_dummy = "";
            $in_content = "";
            foreach ($product_attributes as $key => $product_attribute)
            {
                $attribute_dummy .="".$product_attributes[$key].", ";
                $attribute_value_dummy .="".$attribute_values[$key].", ";
                $in_content .= "(".$product_attributes[$key].", ".$attribute_values[$key]."), ";
            }
            $in_content = "(".rtrim($attribute_dummy,', ')."), (".rtrim($attribute_value_dummy, ', ').")";
            $in_content = rtrim($in_content,', ');
            $product_attribute_value_query = DB::table('product_attributes');
            $product_attribute_value_query->where('product_id', $product->id);
            $product_attribute_value_query->whereRaw(
                "(attribute_id, attribute_value_id) IN (".$in_content.")"
            );
            $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
            //$product_attribute_value_query->groupBy('product_variant_id');
            $product_attribute_value_count = $product_attribute_value_query->count();
            */
            if($combination_exists)
            {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.attribute_combination_already_exists'),
                        'message_code' => 'attribute_combination_already_exists',
                        'errors' => []
                    ], 200);
            }


            $product_variant = new ProductVariant();
            $product_variant->product_id = $product->id;
            $product_variant->sku = $request->sku;
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
            if(isset($request->incremental_price) && $request->incremental_price) $product_variant->incremental_price = $request->incremental_price;
            if(isset($request->initial_quantity) && $request->initial_quantity) $product_variant->initial_quantity = $request->initial_quantity;
            if(isset($request->bid_value) && $request->bid_value) $product_variant->bid_value = $request->bid_value;
            if(isset($request->bid_start_price) && $request->bid_start_price) $product_variant->bid_start_price = $request->bid_start_price;
            if(isset($request->estimate_start_price) && $request->estimate_start_price) $product_variant->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_price) && $request->estimate_end_price) $product_variant->estimate_end_price = $request->estimate_end_price;
            $product_variant->quantity = 0;
            $product_variant->status = $status;
            if(isset($request->offer_text) && $request->offer_text)$product_variant->offer_text = $request->offer_text;
            if(isset($request->offer_text_ar) && $request->offer_text_ar)$product_variant->offer_text_ar = $request->offer_text_ar;
            $product_variant->created_by_user_id = auth('web')->id();
            $product_variant->updated_by_user_id = auth('web')->id();
            if($product->boutique->user->beiaat_handled)
            {
                $product_variant->cost = $request->cost;
            }
            $product_variant->save();

            $user_boutique = $product_variant->product->boutique;
            $stock = new Stock();
            $stock->user_boutique_id = $user_boutique->id;
            $stock->product_variant_id = $product_variant->id;
            $stock->quantity = $request->quantity;
            $stock->created_by_user_id = auth('web')->id();
            $stock->updated_by_user_id = auth('web')->id();
            $stock->save();

            $stock_history = new StockHistory();
            $stock_history->stock_id = $stock->id;
            $stock_history->user_boutique_id = $user_boutique->id;
            $stock_history->product_variant_id = $product_variant->id;
            $stock_history->quantity = $request->quantity;
            $stock_history->add_by = "AddByAdmin";
            $stock_history->stock_type = "Add";
            $stock_history->created_by_user_id = auth('web')->id();
            $stock_history->updated_by_user_id = auth('web')->id();
            $stock_history->stock_type = "Add";
            $stock_history->save();



            $product_attributes = $request->product_attribute;
            $attribute_values = $request->attribute_value;
            foreach ($product_attributes as $key => $product_attribute)
            {
                DB::table('product_attributes')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => $product_variant->id,
                    'attribute_id' => $product_attributes[$key],
                    'attribute_value_id' => $attribute_values[$key],
                    'created_at' => $current_date_time,
                    'updated_at' => $current_date_time,
                ]);
            }
            $images = $request->file('images');
            if(!empty($images))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($images as $key=>$image)
                {

                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $image_name = $slug."-".$key.time().'.'.$image->extension();
                        $image_name_in_db = null;
                        if(strtolower($image->extension()) == 'gif')
                        {
                            $original_image_file = $thumb_image_file = file_get_contents($request->image);
                        }
                        else
                        {
                            $original_image = Image::make($image);
                            $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
                            $original_image_file = $original_image->stream()->__toString();
                            $thumb_image_file = $thumb_image->stream()->__toString();
                        }
                        if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                        {
                            if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                            {
                                $image_name_in_db = $image_name;
                            }
                        }

                        if($image_name_in_db)
                        {
                            $is_default_count = ProductVariantImage::where('is_default', 1)->where('product_variant_id', $product_variant->id)->count();
                            if($is_default_count)
                            {
                                $is_default = false;
                            }
                            else
                            {
                                $is_default = true;
                            }
                            $product_variant_image = new ProductVariantImage();
                            $product_variant_image->product_variant_id = $product_variant->id;
                            $product_variant_image->image = $image_name_in_db;
                            $product_variant_image->is_default = $is_default;
                            $product_variant_image->created_by_user_id = auth('web')->id();
                            $product_variant_image->updated_by_user_id = auth('web')->id();
                            $product_variant_image->save();
                            $success_count = $success_count + 1;
                        }
                    }
                }
            }

            $videos = $request->file('videos');
            if(!empty($videos))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($videos as $key=>$video)
                {
                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $video_name_in_db = null;
                        $video_name = $slug."-video-".$key.time().'.'.$video->extension();
                        if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($video), ['visibility' => 'public']))
                        {
                            $video_name_in_db = $video_name;
                        }

                        if($video_name_in_db)
                        {
                            $is_default_count = ProductVariantVideo::where('is_default', 1)->where('product_variant_id', $product_variant->id)->count();
                            if($is_default_count)
                            {
                                $is_default = false;
                            }
                            else
                            {
                                $is_default = true;
                            }
                            $product_variant_video = new ProductVariantVideo();
                            $product_variant_video->product_variant_id = $product_variant->id;
                            $product_variant_video->video = $video_name_in_db;
                            $product_variant_video->is_default = $is_default;
                            $product_variant_video->created_by_user_id = auth('web')->id();
                            $product_variant_video->updated_by_user_id = auth('web')->id();
                            $product_variant_video->save();
                            $success_count = $success_count + 1;
                        }
                    }

                }
            }

            DB::commit();
            $push_title_en = "New Product Available";
            $push_message_en = "New Product Available";
            $push_title_ar = "New Product Available";
            $push_message_ar = "New Product Available";
            $push_target = "User";
            $user_ids = DB::table('boutique_followers')->where('user_boutique_id', $user_boutique->id)->pluck('user_id')->toArray();
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
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.create_success'),
                'message_code' => 'created_success',
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
    public function edit(Request $request, $id)
    {
        try
        {
            if($id)
            {
                $product_variant = ProductVariant::where('id', $id)->first();
                $product = $product_variant->product;
                $attributes = AttributeSet::where('id', $product->attribute_set_id)->first()->attributes;
                $product_edit_form = view('pages.admin.products.product_variant_edit', compact('attributes', 'product', 'product_variant'))->render();
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
                    'product_edit_form' => $product_edit_form,
                    'product_edit_title' => __('messages.edit_product_variant'),
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
            DB::beginTransaction();
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $product_variant = ProductVariant::where('id', $id)->first();
            $validator = Validator::make($request->all(), [
                'sku'=>'required|max:200|unique:product_variants,sku,'.$id,
                'regular_price' => 'sometimes|required|numeric|min:0',
                'final_price' => 'sometimes|required|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required|numeric|min:0',
                'incremental_quantity' => 'sometimes|required|numeric|min:0',
                'bid_value' => 'sometimes|required|numeric|min:1',
                'bid_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_end_price' => 'sometimes|required|numeric|min:0|gt:estimate_start_price',
                //'quantity' => 'required|numeric|min:0',
                'attribute_value' => 'required',
                "attribute_value.*"  => "required",
                'images.*' => 'mimes:jpeg,jpg,png,gif',
                'videos.*' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',
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

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            $product = Product::where('id', $request->product_id)->first();
            $user_boutique = UserBoutique::where('id', $product->user_boutique_id)->first();
            $user_vendor = $user_boutique->user;
            $user_package_features = $user_vendor->getPackageFeatures();

            $sale_permission = false;
            if($product->sale_type == "Sale")
            {
                if (in_array('manage-sale', $user_package_features)) $sale_permission = true;
            }
            elseif($product->sale_type == "Auction")
            {
                if(in_array('manage-auction', $user_package_features)) $sale_permission = true;
            }
            elseif($product->sale_type == "Bulk")
            {
                if(in_array('manage-bulk', $user_package_features)) $sale_permission = true;
            }
            else
            {

            }

            if($product->sale_type == "Auction")
            {
                if($product->type != "Simple")
                {
                    return response()->json([
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.simple_auction_product'),
                        'message_code' => 'simple_auction_product',
                    ]);
                }

            }

            if($user_vendor->getPackageStatus() !='active')
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.no_package_available'),
                    'message_code' => 'no_package_available',
                ]);
            }


            if(!$sale_permission)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.no_permission_to_create_product'),
                    'message_code' => 'permission_check',
                ]);
            }


            $slug = Str::slug($product->name, '-');

            $product_attribute_set = AttributeSet::where('id', $product->attribute_set_id)->first();
            $product_attributes_count = $product_attribute_set->attributes->count();

            $product_attributes = $request->product_attribute;
            $attribute_values = $request->attribute_value;


            $combination_exists = false; // combination  exists
            foreach ($product->product_variants()->where('product_variants.id', '!=', $product_variant->id)->get() as $product_variant_item)
            {
                $product_attribute_value_query = DB::table('product_attributes');
                $product_attribute_value_query->where('product_id', $product->id);
                $product_attribute_value_query->where('product_variant_id', $product_variant_item->id);
                $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
                $product_attribute_value_query->where(function ($query_1) use($product, $product_variant_item, $product_attributes, $attribute_values){
                    foreach ($product_attributes as $key => $product_attribute)
                    {
                        if($key == 0)
                        {
                            $query_1->where(function ($query_2) use($product_attributes, $attribute_values, $key){
                                $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                            });
                        }
                        else
                        {
                            $query_1->orWhere(function ($query_2) use($product_attributes, $attribute_values, $key){
                                $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                            });
                        }
                    }
                });
                $product_attribute_value_count = $product_attribute_value_query->count();
                if($product_attribute_value_count >= $product_attributes_count)
                {
                    $combination_exists = true; // combination exists
                    break;
                }
            }
            /*
            $attribute_dummy = "";
            $attribute_value_dummy = "";
            $in_content = "";
            foreach ($product_attributes as $key => $product_attribute)
            {
                $attribute_dummy .="".$product_attributes[$key].", ";
                $attribute_value_dummy .="".$attribute_values[$key].", ";
                $in_content .= "(".$product_attributes[$key].", ".$attribute_values[$key]."), ";
            }
            $in_content = "(".rtrim($attribute_dummy,', ')."), (".rtrim($attribute_value_dummy, ', ').")";
            $in_content = rtrim($in_content,', ');
            $product_attribute_value_query = DB::table('product_attributes');
            $product_attribute_value_query->where('product_id', $product->id);
            $product_attribute_value_query->whereRaw(
                "(attribute_id, attribute_value_id) IN (".$in_content.")"
            );
            $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
            //$product_attribute_value_query->groupBy('product_variant_id');
            $product_attribute_value_count = $product_attribute_value_query->count();
            */
            if($combination_exists)
            {
                DB::rollBack();
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.attribute_combination_already_exists'),
                        'message_code' => 'attribute_combination_already_exists',
                        'errors' => []
                    ], 200);
            }

            $product_variant->sku = $request->sku;
            $product_variant->status = $status;
            $product_variant->quantity = 0;
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
            if(isset($request->incremental_price) && $request->incremental_price) $product_variant->incremental_price = $request->incremental_price;
            if(isset($request->initial_quantity) && $request->initial_quantity) $product_variant->initial_quantity = $request->initial_quantity;
            if(isset($request->bid_value) && $request->bid_value) $product_variant->bid_value = $request->bid_value;
            if(isset($request->bid_start_price) && $request->bid_start_price) $product_variant->bid_start_price = $request->bid_start_price;
            if(isset($request->estimate_start_price) && $request->estimate_start_price) $product_variant->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_price) && $request->estimate_end_price) $product_variant->estimate_end_price = $request->estimate_end_price;
            if(isset($request->offer_text) && $request->offer_text)$product_variant->offer_text = $request->offer_text;
            if(isset($request->offer_text_ar) && $request->offer_text_ar)$product_variant->offer_text_ar = $request->offer_text_ar;
            $product_variant->updated_by_user_id = auth('web')->id();
            if($product->boutique->user->beiaat_handled)
            {
                $product_variant->cost = $request->cost;
            }
            $product_variant->save();



            $product_attributes = $request->product_attribute;
            $attribute_values = $request->attribute_value;
            DB::table('product_attributes')->where('product_variant_id', $id)->delete();
            foreach ($product_attributes as $key => $product_attribute)
            {
                DB::table('product_attributes')->insert([
                    'product_id' => $product->id,
                    'product_variant_id' => $product_variant->id,
                    'attribute_id' => $product_attributes[$key],
                    'attribute_value_id' => $attribute_values[$key],
                    'created_at' => $current_date_time,
                    'updated_at' => $current_date_time,
                ]);
            }
            $images = $request->file('images');
            if(!empty($images))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($images as $key=>$image)
                {

                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $image_name = $slug."-".$key.time().'.'.$image->extension();
                        $image_name_in_db = null;
                        if(strtolower($image->extension()) == 'gif')
                        {
                            $original_image_file = $thumb_image_file = file_get_contents($request->image);
                        }
                        else
                        {
                            $original_image = Image::make($image);
                            $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
                            $original_image_file = $original_image->stream()->__toString();
                            $thumb_image_file = $thumb_image->stream()->__toString();
                        }
                        if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                        {
                            if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                            {
                                $image_name_in_db = $image_name;
                            }
                        }

                        if($image_name_in_db)
                        {
                            $product_variant_image = new ProductVariantImage();
                            $product_variant_image->product_variant_id = $product_variant->id;
                            $product_variant_image->image = $image_name_in_db;
                            $product_variant_image->is_default = false;
                            $product_variant_image->created_by_user_id = auth('web')->id();
                            $product_variant_image->updated_by_user_id = auth('web')->id();
                            $product_variant_image->save();
                            $success_count = $success_count + 1;
                        }
                    }
                }
            }

            $videos = $request->file('videos');
            if(!empty($videos))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($videos as $key=>$video)
                {
                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $video_name_in_db = null;
                        $video_name = $slug."-video-".$key.time().'.'.$video->extension();
                        if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($video), ['visibility' => 'public']))
                        {
                            $video_name_in_db = $video_name;
                        }

                        if($video_name_in_db)
                        {
                            $product_variant_video = new ProductVariantVideo();
                            $product_variant_video->product_variant_id = $product_variant->id;
                            $product_variant_video->video = $video_name_in_db;
                            $product_variant_video->is_default = false;
                            $product_variant_video->created_by_user_id = auth('web')->id();
                            $product_variant_video->updated_by_user_id = auth('web')->id();
                            $product_variant_video->save();
                            $success_count = $success_count + 1;
                        }
                    }

                }
            }
            DB::commit();
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
            $product_variant = ProductVariant::where('id', $id)->first();
            Cart::where('product_variant_id', $product_variant->id)->delete();
            DB::table('favorite_products')->where('product_variant_id', $product_variant->id)->delete();
            DB::table('product_attributes')->where('product_variant_id', $product_variant->id)->delete();
            $product = $product_variant->product;
            if($product->product_variants()->count() == 1)
            {
                ProductForAuctionOrSale::where('product_id', $product->id)->delete();
                DB::table('product_categories')->where('product_id', $product->id)->delete();
                Product::where('id', $product->id)->delete();
            }
            $product_variant->delete();

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

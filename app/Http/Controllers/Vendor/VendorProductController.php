<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\AttributeSet;
use App\Models\Brand;
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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Image;
class VendorProductController extends Controller
{
    public function index(Request $request)
    {
        $products = [];
        if ($request->ajax()) {
            if(isset($_COOKIE['selected_product_variant_ids']))
            {
                $selected_variant_ids = explode(',', $_COOKIE['selected_product_variant_ids']);
            }
            else
            {
                $selected_variant_ids = [];
            }
            $data_query =   ProductVariant::query();
            $data_query->select('id','product_id','quantity', 'final_price', 'regular_price','sku', 'status', 'bid_value', 'bid_start_price');
            $data_query->whereHas('product', function ($query_10) {
                $query_10->where("products.user_id", auth('web')->id());
            });
            $data_query->orderBy('id', 'DESC');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="addOrRemoveStock('.$row->id.')">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-plus-slash-minus text-primary fs-1"></i>
		                                </span>
                                    </a>';

                    $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('admin.products.show', $row->id).'" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-eye-fill text-info fs-1"></i>
		                                </span>
                                    </a>';

                    $action_button .= '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('admin.products.edit', $row->id).'">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';
                    if(!$row->isDeletable())
                    {
                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                    }
                    else
                    {
                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteProductVariant('.$row->id.')" >
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
                ->addColumn('category', function($row){
                    return $row->product->category_list;
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
                ->addColumn('regular_price_or_bid_start_price', function($row){
                    if($row->product->sale_type == 'Sale'){ return $row->getRegularPrice(); }elseif($row->product->sale_type == 'Auction'){ return $row->getBidStartPrice(); }else{}
                })
                ->addColumn('final_price_or_bid_value', function($row){
                    if($row->product->sale_type == 'Sale'){ return $row->getFinalPrice(); }elseif($row->product->sale_type == 'Auction'){ return $row->getBidValue(); }else{}
                })
                ->addColumn('quantity', function($row){
                   return $row->availableStockQuantity();
                })
                ->addColumn('checkbox', function ($row) use ($selected_variant_ids){
                    if(in_array($row->id, $selected_variant_ids))
                    {
                        if($row->isDeletable())
                        {
                            $checkbox =  '<input type="checkbox" name="selected_ids[]" class="selected_id" checked value="'.$row->id.'" onclick="handleClick(this)" />';
                        }
                        else
                        {
                            $checkbox =  '<input type="checkbox" class="selected_id" disabled />';
                        }
                    }
                    else
                    {
                        if($row->isDeletable())
                        {
                            $checkbox =  '<input type="checkbox" name="selected_ids[]" class="selected_id" value="'.$row->id.'" onclick="handleClick(this)" />';
                        }
                        else
                        {
                            $checkbox =  '<input type="checkbox" class="selected_id" disabled />';
                        }
                    }
                    return $checkbox;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status', 'attribute_value', 'boutique', 'image', 'category', 'final_price_or_bid_value', 'regular_price_or_bid_start_price', 'sale_type', 'quantity', 'checkbox'])
                ->make(true);
        }
        return view('pages.user.products.index', compact('products'));
    }

    public function create()
    {
        $attribute_sets = AttributeSet::latest()->get();
        $user_boutiques = UserBoutique::where('user_id', auth('web')->id())->latest()->get();
        return view('pages.user.products.create', compact('attribute_sets', 'user_boutiques'));
    }

    public function store(Request $request)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                'boutique' => 'required',
                'brand' => 'required',
                'product_type' => 'required',
                "sale_type"  => "required",
                'attribute_set' => 'required',
                'name' => 'required|max:200',
                'name_ar' => 'required|max:200',
                'sku' => 'required|max:200|unique:product_variants',
                'regular_price' => 'sometimes|required|numeric|min:0',
                'final_price' => 'sometimes|required|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required|numeric|min:0',
                'incremental_quantity' => 'sometimes|required|numeric|min:0',
                'bid_value' => 'sometimes|required|numeric|min:1',
                'bid_start_price' => 'sometimes|required|numeric|min:1',
                'bid_start_time_end_time' => 'sometimes|required',
                'estimate_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_end_price' => 'sometimes|required|numeric|min:0|gt:estimate_start_price',
                'quantity' => 'required|numeric|min:0',
                'categories' => 'required',
                'attribute_value' => 'required',
                "attribute_value.*"  => "required",
                'description' => 'required',
                'description_ar' => 'required',
                'cost' => 'sometimes|required|numeric|min:0',
                'images' => 'required',
                'images.*' => 'required|mimes:jpeg,jpg,png,gif',
                'videos.*' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',

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

            $most_selling_status = false;
            if(isset($request->most_selling_status) && $request->most_selling_status == 1)
            {
                $most_selling_status = true;
            }


            $start_time = null;
            $end_time = null;
            if(isset($request->start_time_end_time) && $request->start_time_end_time)
            {
                $start_time_end_time = explode(' ~ ', $request->start_time_end_time);
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[0]), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[1]), $time_zone);
            }

            $bid_start_time = null;
            $bid_end_time = null;
            if(isset($request->bid_start_time_end_time) && $request->bid_start_time_end_time)
            {
                $bid_start_time_end_time = explode(' ~ ', $request->bid_start_time_end_time);
                $bid_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[0]), $time_zone);
                $bid_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($bid_start_time_end_time[1]), $time_zone);
            }

            $user_boutique = UserBoutique::where('id', $request->boutique)->first();
            $user_vendor = $user_boutique->user;
            $user_package_features = $user_vendor->getPackageFeatures();

            $sale_permission = false;
            if($request->sale_type == "Sale")
            {
                if (in_array('manage-sale', $user_package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Auction")
            {
                if(in_array('manage-auction', $user_package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Bulk")
            {
                if(in_array('manage-bulk', $user_package_features)) $sale_permission = true;
            }
            else
            {

            }

            if($request->sale_type == "Auction")
            {
                if($request->product_type != "Simple")
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



            $slug = Str::slug($request->name, '-');


            $product = new Product();
            $product->name = $request->name;
            $product->name_ar = $request->name_ar;
            $product->type = $request->product_type;
            $product->sale_type = $request->sale_type;
            $product->user_boutique_id = $request->boutique;
            $product->user_id = $user_boutique->user_id;
            $product->brand_id = $request->brand;
            $product->attribute_set_id = $request->attribute_set;
            $product->slug = $slug;
            $product->description = $request->description;
            $product->description_ar = $request->description_ar;
            $product->new_arrival_start_time = $start_time;
            $product->new_arrival_end_time = $end_time;
            $product->status = $status;
            $product->most_selling_status = $most_selling_status;
            $product->created_by_user_id = auth('web')->id();
            $product->updated_by_user_id = auth('web')->id();
            $product->save();

            $product_variant = new ProductVariant();
            $product_variant->product_id = $product->id;
            $product_variant->sku = $request->sku;
            $product_variant->quantity = 0; // add in stocks table
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
            if(isset($request->incremental_price) && $request->incremental_price) $product_variant->incremental_price = $request->incremental_price;
            if(isset($request->initial_quantity) && $request->initial_quantity) $product_variant->initial_quantity = $request->initial_quantity;
            if(isset($request->incremental_quantity) && $request->incremental_quantity) $product_variant->incremental_quantity = $request->incremental_quantity;
            if(isset($request->bid_value) && $request->bid_value) $product_variant->bid_value = $request->bid_value;
            if(isset($request->bid_start_price) && $request->bid_start_price) $product_variant->bid_start_price = $request->bid_start_price;
            if(isset($request->estimate_start_price) && $request->estimate_start_price) $product_variant->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_price) && $request->estimate_end_price) $product_variant->estimate_end_price = $request->estimate_end_price;
//            if(isset($request->offer_text) && $request->offer_text)$product_variant->offer_text = $request->offer_text;
//            if(isset($request->offer_text_ar) && $request->offer_text_ar)$product_variant->offer_text_ar = $request->offer_text_ar;
            if($user_boutique->user->beiaat_handled)
            {
                $product_variant->cost = $request->cost;
            }
            $product_variant->created_by_user_id = auth('web')->id();
            $product_variant->updated_by_user_id = auth('web')->id();
            $product_variant->save();


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
            $stock_history->add_by = "AddByVendor";
            $stock_history->stock_type = "Add";
            $stock_history->created_by_user_id = auth('web')->id();
            $stock_history->updated_by_user_id = auth('web')->id();
            $stock_history->stock_type = "Add";
            $stock_history->save();


            $categories = explode(',', $request->categories);
            $product->categories()->attach($categories);

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


            $product_for_auction_or_sale = new ProductForAuctionOrSale();
            $product_for_auction_or_sale->user_id = $product->user_id;
            $product_for_auction_or_sale->product_id = $product->id;
            $product_for_auction_or_sale->type = $request->sale_type;
            $product_for_auction_or_sale->bid_start_time = $bid_start_time;
            $product_for_auction_or_sale->bid_end_time = $bid_end_time;
            $product_for_auction_or_sale->created_by_user_id = auth('web')->id();
            $product_for_auction_or_sale->updated_by_user_id = auth('web')->id();
            $product_for_auction_or_sale->save();



//            if($product_for_auction_or_sale->type == 'Auction')
//            {
//                $product_auction_timing = new AuctionTiming();
//                $product_auction_timing->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
//                $product_auction_timing->bid_start_time = $bid_start_time;
//                $product_auction_timing->bid_end_time = $bid_end_time;
//                $product_auction_timing->created_by_user_id = auth('web')->id();
//                $product_auction_timing->updated_by_user_id = auth('web')->id();
//                $product_auction_timing->save();
//            }

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

            Session::put('session_success_message', "Successfully Created");
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.create_success'),
                'message_code' => 'created_success',
                'url' => route('user.products.edit', $product_variant->id)
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
        $attribute_sets = AttributeSet::latest()->get();
        $user_boutiques = UserBoutique::where('user_id', auth('web')->id())->latest()->get();
        $product_variant = ProductVariant::where('id', $id)->whereHas('product', function ($query_10) {
                $query_10->where("products.user_id", auth('web')->id());
            })->first();
        $product = $product_variant->product;
        $assigned_category_ids = $product->categories->pluck('id')->toArray();
        return view('pages.user.products.edit', compact('attribute_sets', 'user_boutiques', 'product_variant', 'product', 'assigned_category_ids'));
    }

    public function update(Request $request, $id)
    {
        try
        {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $product_variant = ProductVariant::where('id', $id)->whereHas('product', function ($query_10) {
                    $query_10->where("products.user_id", auth('web')->id());
                })->first();
            $product = $product_variant->product;

            $validator = Validator::make($request->all(), [
                'boutique' => 'required',
                'brand' => 'required',
                'product_type' => 'required',
                'sale_type' => 'required',
                'attribute_set' => 'required',
                'name' => 'required|max:200',
                'name_ar' => 'required|max:200',
                'sku'=>'required|max:200|unique:product_variants,sku,'.$id,
                'regular_price' => 'sometimes|required|numeric|min:0',
                'final_price' => 'sometimes|required|numeric|min:0|lte:regular_price',
                'incremental_price' => 'sometimes|required|numeric|min:0',
                'incremental_quantity' => 'sometimes|required|numeric|min:0',
                'bid_value' => 'sometimes|required|numeric|min:1',
                'bid_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_start_price' => 'sometimes|required|numeric|min:1',
                'estimate_end_price' => 'sometimes|required|numeric|min:0|gt:estimate_start_price',
                'bid_start_time' => 'sometimes|required|date_format:Y-m-d H:i:s',
                'bid_end_time' => 'sometimes|required|date_format:Y-m-d H:i:s|after:bid_start_time',
                //'quantity' => 'required|numeric|min:0',
                'categories' => 'required',
                'attribute_value' => 'required',
                "attribute_value.*"  => "required",
                'description' => 'required',
                'description_ar' => 'required',
                'cost' => 'sometimes|required|numeric|min:0',
                'images.*' => 'mimes:jpeg,jpg,png,gif',
                'videos.*' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',

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

            $most_selling_status = false;
            if(isset($request->most_selling_status) && $request->most_selling_status == 1)
            {
                $most_selling_status = true;
            }


            $start_time = null;
            $end_time = null;
            if(isset($request->start_time_end_time) && $request->start_time_end_time)
            {
                $start_time_end_time = explode(' ~ ', $request->start_time_end_time);
                if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[0]), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($start_time_end_time[1]), $time_zone);
            }

            $user_boutique = UserBoutique::where('id', $request->boutique)->first();
            $user_vendor = $user_boutique->user;
            $user_package_features = $user_vendor->getPackageFeatures();

            $sale_permission = false;
            if($request->sale_type == "Sale")
            {
                if (in_array('manage-sale', $user_package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Auction")
            {
                if(in_array('manage-auction', $user_package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Bulk")
            {
                if(in_array('manage-bulk', $user_package_features)) $sale_permission = true;
            }
            else
            {

            }

            if($request->sale_type == "Auction")
            {
                if($request->product_type != "Simple")
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


            $slug = Str::slug($request->name, '-');


            $user_boutique = UserBoutique::where('id', $request->boutique)->first();


            $product->name = $request->name;
            $product->name_ar = $request->name_ar;
            $product->type = $request->product_type;
            $product->sale_type = $request->sale_type;
            $product->user_boutique_id = $request->boutique;
            $product->brand_id = $request->brand;
            $product->user_id = $user_boutique->user_id;
            $product->attribute_set_id = $request->attribute_set;
            $product->slug = $slug;
            $product->description = $request->description;
            $product->description_ar = $request->description_ar;
            $product->new_arrival_start_time = $start_time;
            $product->new_arrival_end_time = $end_time;
            $product->status = $status;
            $product->most_selling_status = $most_selling_status;
            $product->updated_by_user_id = auth('web')->id();
            $product->save();

            $product_variant->product_id = $product->id;
            $product_variant->sku = $request->sku;
            $product_variant->quantity = 0; // add in stocks table
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
            if(isset($request->incremental_price) && $request->incremental_price) $product_variant->incremental_price = $request->incremental_price;
            if(isset($request->initial_quantity) && $request->initial_quantity) $product_variant->initial_quantity = $request->initial_quantity;
            if(isset($request->incremental_quantity) && $request->incremental_quantity) $product_variant->incremental_quantity = $request->incremental_quantity;
            if(isset($request->bid_value) && $request->bid_value) $product_variant->bid_value = $request->bid_value;
            if(isset($request->bid_start_price) && $request->bid_start_price) $product_variant->bid_start_price = $request->bid_start_price;
            if(isset($request->estimate_start_price) && $request->estimate_start_price) $product_variant->estimate_start_price = $request->estimate_start_price;
            if(isset($request->estimate_end_price) && $request->estimate_end_price) $product_variant->estimate_end_price = $request->estimate_end_price;
//            if(isset($request->offer_text) && $request->offer_text)$product_variant->offer_text = $request->offer_text;
//            if(isset($request->offer_text_ar) && $request->offer_text_ar)$product_variant->offer_text_ar = $request->offer_text_ar;
            $product_variant->updated_by_user_id = auth('web')->id();
            if($user_boutique->user->beiaat_handled)
            {
                $product_variant->cost = $request->cost;
            }
            $product_variant->save();



            $categories = explode(',', $request->categories);
            $product->categories()->sync($categories);

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

            ProductForAuctionOrSale::where('product_id', $product->id)->update(['type' => $request->sale_type, 'updated_by_user_id' => auth('web')->id(), 'updated_at' => $current_date_time]);

            Session::put('session_success_message', "Successfully Updated");
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'update_success',
                'url' => route('user.products.edit', $product_variant->id)
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
        $product_variant = ProductVariant::where('id', $id)->whereHas('product', function ($query_10) {
        $query_10->where("products.user_id", auth('web')->id());
        })->first();
        $product = $product_variant->product;
        return view('pages.user.products.show', compact('product_variant', 'product'));
    }

    public function getProductAddForm(Request $request)
    {
        try
        {
            if($request->attribute_set && $request->sale_type && $request->boutique)
            {
                if(!$request->sale_type || !$request->boutique)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status_code' => 200,
                            'message' => __('messages.sale_type_boutique_required'),
                            'message_code' => 'sale_type_boutique_required',
                            'errors' => []
                        ], 200);
                }
                $attributes = AttributeSet::where('id', $request->attribute_set)->first()->attributes;
                $sale_type = $request->sale_type;
                $boutique = UserBoutique::where('id', $request->boutique)->first();
                $brands = Brand::where('status', 1)->orderBy('sort', 'ASC')->get();
                if($request->product_variant_id)
                {
                    $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                    $product = $product_variant->product;
                    $product_add_form = view('pages.user.products.product_add_form', compact('attributes', 'product', 'product_variant', 'sale_type', 'boutique', 'brands'))->render();
                }
                else
                {
                    $product_add_form = view('pages.user.products.product_add_form', compact('attributes', 'sale_type', 'boutique', 'brands'))->render();
                }

            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.mandatory_fields_required'),
                        'message_code' => 'mandatory_fields_required',
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

    public function getProductVariantImages(Request $request)
    {
        try
        {

            if($request->product_variant_id)
            {
                $product_variant = ProductVariant::where('id', $request->product_variant_id)->first();
                $product_variant_images = $product_variant->images;
                $product_variant_videos = $product_variant->videos;
                $product_variant_images_form = view('pages.user.products.product_variant_images_form', compact('product_variant_images', 'product_variant_videos'))->render();
            }
            else
            {
                $product_variant_images_form = "";
            }

            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'product_variant_images' => $product_variant_images_form,
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

    public function destroyProductVariantImages($id)
    {
        try
        {
            $product_variant_image = ProductVariantImage::where('id', $id)->first();
            if(ProductVariantImage::where('product_variant_id', $product_variant_image->product_variant_id)->count() >= 2)
            {
                if(Storage::disk('public')->exists('uploads/product_variant_images/original/'.$product_variant_image->image))
                {
                    Storage::disk('public')->delete('uploads/product_variant_images/original/'.$product_variant_image->image);
                }
                if(Storage::disk('public')->exists('uploads/product_variant_images/thumb/'.$product_variant_image->image))
                {
                    Storage::disk('public')->delete('uploads/product_variant_images/thumb/'.$product_variant_image->image);
                }
                $product_variant_image->delete();

                if(!ProductVariantImage::where('product_variant_id', $product_variant_image->product_variant_id)->where('is_default', true)->count())
                {
                    $product_variant_image = ProductVariantImage::where('product_variant_id', $product_variant_image->product_variant_id)->first();
                    $product_variant_image->is_default = true;
                    $product_variant_image->save();
                }
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status_code' => 200,
                        'message' => __('messages.min_one_image_required'),
                        'message_code' => 'min_one_required',
                        'errors' => []
                    ], 200);
            }

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

    public function makeDefaultProductVariantImages($id)
    {
        try
        {
            $product_variant_image = ProductVariantImage::where('id', $id)->first();
            ProductVariantImage::where('product_variant_id', $product_variant_image->product_variant_id)->where('id', '!=', $id)->update(['is_default' => false]);
            ProductVariantImage::where('product_variant_id', $product_variant_image->product_variant_id)->where('id', '=', $id)->update(['is_default' => true]);

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

    public function destroyProductVariantVideos($id)
    {
        try
        {
            $product_variant_video = ProductVariantVideo::where('id', $id)->first();
            if(Storage::disk('public')->exists('uploads/product_variant_videos/'.$product_variant_video->video))
            {
                Storage::disk('public')->delete('uploads/product_variant_videos/'.$product_variant_video->video);
            }
            $product_variant_video->delete();

            if(!ProductVariantImage::where('product_variant_id', $product_variant_video->product_variant_id)->where('is_default', true)->count())
            {
                $product_variant_video = ProductVariantImage::where('product_variant_id', $product_variant_video->product_variant_id)->first();
                $product_variant_video->is_default = true;
                $product_variant_video->save();
            }

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

    public function makeDefaultProductVariantVideos($id)
    {
        try
        {
            $product_variant_video = ProductVariantVideo::where('id', $id)->first();
            ProductVariantImage::where('product_variant_id', $product_variant_video->product_variant_id)->where('id', '!=', $id)->update(['is_default' => false]);
            ProductVariantImage::where('product_variant_id', $product_variant_video->product_variant_id)->where('id', '=', $id)->update(['is_default' => true]);

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
    public function destroy($id)
    {
        try
        {
            if($id == 0)
            {
                $selected_variant_ids = explode(',', $_COOKIE['selected_product_variant_ids']);
                foreach ($selected_variant_ids as $selected_variant_id)
                {
                    $product_variant = ProductVariant::where('id', $selected_variant_id)->first();
                    if($product_variant)
                    {
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
                    }
                }

                if(count($selected_variant_ids) == 1) // 0 only
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status_code' => 500,
                            'message' => __('messages.no_products_selected'),
                            'message_code' => 'try_catch',
                            'errors' => []
                        ], 200);

                }

            }
            else
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
            }

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

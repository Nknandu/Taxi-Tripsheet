<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AttributeSet;
use App\Models\Brand;
use App\Models\Category;
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
use Image;

class UserProductCrudController extends Controller
{

    public function getProductCrudDetailsStep1(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }

            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $time_zone = getLocalTimeZone($request->time_zone);
            $user_id = auth('api')->id();

            $user = auth('api')->user();

            if(!$user->boutiques->count())
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutique_available'),
                        'message_code' => 'no_boutique_available',
                    ], 200);
            }

            if($user->package && $user->package->features)
            {
                $package_features =  $user->getPackageFeatures();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_package_available'),
                        'message_code' => 'no_package_available',
                    ], 200);
            }

            $sale_type_array = [];
            $sale_array = [
              'id' => "Sale",
              'name' => __('messages.sale')
            ];

            $auction_array = [
                'id' => "Auction",
                'name' => __('messages.auction')
            ];

            $bulk_array = [
                'id' => "Bulk",
                'name' => __('messages.bulk')
            ];

            $live_array = [
                'id' => "Live",
                'name' => __('messages.live')
            ];

            if(in_array('manage-sale', $package_features)) array_push($sale_type_array, $sale_array);
            if(in_array('manage-auction', $package_features)) array_push($sale_type_array, $auction_array);
            if(in_array('manage-live', $package_features)) array_push($sale_type_array, $live_array);
            if(in_array('manage-bulk', $package_features)) array_push($sale_type_array, $bulk_array);

            $category_query = Category::query();
            $category_query->select('id', 'parent_id', $name_array['name'], 'image', 'image_ar');
            $category_query->orderBy('sort_order', 'ASC');
            $category_count_query = clone $category_query;
            $category_count =  $category_count_query->count();
            if(empty($request->get('page'))) $per_page = $category_count;
            $category_data_array = $category_query->get();
            $category_data = [];
            foreach ($category_data_array as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "parent_id" => (string) $category_data_item->parent_id,
                    "name" => (string) $category_data_item->name,
                ];
                array_push($category_data, $category_temp_data);
            }

            $category_query = Category::query();
            $category_query->select('id', 'parent_id', $name_array['name'], 'image', 'image_ar');
            $category_query->orderBy('sort_order', 'ASC');
            $category_count_query = clone $category_query;
            $category_count =  $category_count_query->count();
            $category_data_array = $category_query->get();
            $category_data = [];
            foreach ($category_data_array as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "parent_id" => (string) $category_data_item->parent_id,
                    "name" => (string) $category_data_item->name,
                ];
                array_push($category_data, $category_temp_data);
            }

            $attribute_set_query = AttributeSet::query();
            $attribute_set_query->select('id', $name_array['name']);
            $attribute_set_query->orderBy('id', 'DESC');
            $attribute_set_count_query = clone $attribute_set_query;
            $attribute_set_count =  $attribute_set_count_query->count();
            $attribute_set_data_array = $attribute_set_query->get();
            $attribute_set_data = [];
            foreach ($attribute_set_data_array as $key => $attribute_set_data_item)
            {
                $attribute_set_temp_data = [
                    "id" => $attribute_set_data_item->id,
                    "name" => (string) $attribute_set_data_item->name,
                ];
                array_push($attribute_set_data, $attribute_set_temp_data);
            }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'product_crud_step_2',
                    'data' => [
                        "sale_types" => $sale_type_array,
                        "categories" => $category_data,
                        "attribute_sets" => $attribute_set_data,
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

    public function getProductCrudDetailsStep2(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['title'] = "title_ar as title";
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as name";
            }
            else
            {
                $name_array['title'] = "title";
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'sale_type' => 'required',
                'attribute_set' => 'required',
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

            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $time_zone = getLocalTimeZone($request->time_zone);
            $user_id = auth('api')->id();

            $user = auth('api')->user();
            $is_beiaat_handled = 0;
            if($user->beiaat_handled)$is_beiaat_handled = 1;



            $attribute_set_query = AttributeSet::query();
            $attribute_set_query->where('id', $request->attribute_set);
            $attribute_set_data_array = $attribute_set_query->first();
            $attribute_data = [];
            foreach ($attribute_set_data_array->attributes as $key => $attribute_item)
            {
                $attribute_value_data = [];

                foreach ($attribute_item->attribute_values as $attribute_value_item)
                {
                    $attribute_value_temp_data = [
                        "id" => $attribute_value_item->id,
                        "name" => (string) $attribute_value_item->name,
                        "attribute_id" => $attribute_item->id,
                    ];
                    array_push($attribute_value_data, $attribute_value_temp_data);
                }
                $attribute_temp_data = [
                    "id" => $attribute_item->id,
                    "name" => (string) $attribute_item->name,
                    "attribute_set_id" =>  $attribute_set_data_array->id,
                    "attribute_values" => $attribute_value_data
                ];
                array_push($attribute_data, $attribute_temp_data);
            }


            $brand_query = Brand::query();
            $brand_query->select('id', $name_array['title']);
            $brand_query->where('status', 1);
            $brand_query->orderBy('id', 'DESC');
            $brand_count_query = clone $brand_query;
            $brand_count =  $brand_count_query->count();
            $brand_data_array = $brand_query->get();
            $brand_data = [];
            foreach ($brand_data_array as $key => $brand_data_item)
            {
                $brand_temp_data = [
                    "id" => $brand_data_item->id,
                    "name" => (string) $brand_data_item->title,
                ];
                array_push($brand_data, $brand_temp_data);
            }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'product_crud_step_2',
                    'data' => [
                        "attributes" => $attribute_data,
                        "brands" => $brand_data,
                        "is_beiaat_handled" => $is_beiaat_handled,
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

    public function createProduct(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                "sale_type"  => "required",
                'attribute_set' => 'required',
                'brand' => 'required',
                'name' => 'required|max:200',
                'name_ar' => 'required|max:200',
                'sku' => 'required|max:200|unique:product_variants',
                'regular_price' => 'required_if:sale_type,==,Sale',
                'final_price' => 'required_if:sale_type,==,Sale|lte:regular_price',
                'bid_value' => 'required_if:sale_type,==,Auction',
                'bid_start_price' => 'required_if:sale_type,==,Auction',
                'bid_start_time' => 'required_if:sale_type,==,Auction',
                'bid_end_time' => 'required_if:sale_type,==,Auction',
                'estimate_start_price' => 'required_if:sale_type,==,Auction',
                'estimate_end_price' => 'required_if:sale_type,==,Auction',
                'quantity' => 'required',
                'categories' => 'required',
                'product_attribute' => 'required',
                "product_attribute.*"  => "required",
                'product_attribute_value' => 'required',
                "product_attribute_value.*"  => "required",
                'description' => 'required',
                'description_ar' => 'required',
                'beiaat_handled' => 'required',
                'cost' => 'required_if:beiaat_handled,==,1',
                //'image' => 'required|mimes:jpeg,jpg,png,gif',
                //'video' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',

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

           $user = auth('api')->user();

            if(!$user->boutiques->count())
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutique_available'),
                        'message_code' => 'no_boutique_available',
                    ], 200);
            }

            if($user->package && $user->package->features)
            {
                $package_features =  $user->getPackageFeatures();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_package_available'),
                        'message_code' => 'no_package_available',
                    ], 200);
            }

            $sale_permission = false;
            if($request->sale_type == "Sale")
            {
                if (in_array('manage-sale', $package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Auction")
            {
                if(in_array('manage-auction', $package_features)) $sale_permission = true;
            }
            else
            {

            }

            if(!$sale_permission)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_permission_to_create_product'),
                        'message_code' => 'no_permission_to_create_product',
                    ], 200);
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
            if((isset($request->new_arrival_start_time) && $request->new_arrival_start_time) && (isset($request->new_arrival_end_time) && $request->new_arrival_end_time))
            {
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->new_arrival_start_time), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->new_arrival_end_time), $time_zone);
            }

            $bid_start_time = null;
            $bid_end_time = null;
            if((isset($request->bid_start_time) && $request->bid_start_time) && (isset($request->bid_end_time) && $request->bid_end_time))
            {
                $bid_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->bid_start_time), $time_zone);
                $bid_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->bid_end_time), $time_zone);
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
            }
            elseif(isset($request->image) && $request->image)
            {
                $image = $request->image;
                $image_name = $slug."-".time().'.png';

                //  Image::make(file_get_contents($data->base64_image))->save($path);
                $original_image = Image::make($image);
                $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
                $original_image_file = $original_image->stream()->__toString();
                $thumb_image_file = $thumb_image->stream()->__toString();

                if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }
            else
            {

            }

            $video_name_in_db = null;

            if($request->hasfile('video'))
            {
                $video = $request->file('video');
                $video_name = $slug."-video-".time().'.'.$video->extension();
                if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($request->video), ['visibility' => 'public']))
                {
                    $video_name_in_db = $video_name;
                }
            }
            elseif(isset($request->video) && $request->video)
            {
                $video_name = $slug."-video-".time().'.mp4';
                if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($request->video), ['visibility' => 'public']))
                {
                    $video_name_in_db = $video_name;
                }
            }
            else
            {

            }

            DB::beginTransaction();
            $user_boutique = $user->boutiques[0];

            $product = new Product();
            $product->brand_id = $request->brand;
            $product->name = $request->name;
            $product->name_ar = $request->name_ar;
            $product->type = "Simple";
            $product->sale_type = $request->sale_type;
            $product->user_boutique_id = $user_boutique->id;
            $product->user_id = $user->id;
            $product->attribute_set_id = $request->attribute_set;
            $product->slug = $slug;
            $product->description = $request->description;
            $product->description_ar = $request->description_ar;
            $product->new_arrival_start_time = $start_time;
            $product->new_arrival_end_time = $end_time;
            $product->status = $status;
            $product->most_selling_status = $most_selling_status;
            $product->save();

            $product_variant = new ProductVariant();
            $product_variant->product_id = $product->id;
            $product_variant->sku = $request->sku;
            $product_variant->quantity = 0; // add in stocks table
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
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
            $product_variant->save();


            $stock = new Stock();
            $stock->user_boutique_id = $user_boutique->id;
            $stock->product_variant_id = $product_variant->id;
            $stock->quantity = $request->quantity;
            $stock->created_by_user_id = auth('api')->id();
            $stock->updated_by_user_id = auth('api')->id();
            $stock->save();

            $stock_history = new StockHistory();
            $stock_history->stock_id = $stock->id;
            $stock_history->user_boutique_id = $user_boutique->id;
            $stock_history->product_variant_id = $product_variant->id;
            $stock_history->quantity = $request->quantity;
            $stock_history->add_by = "AddByVendor";
            $stock_history->stock_type = "Add";
            $stock_history->created_by_user_id = auth('api')->id();
            $stock_history->updated_by_user_id = auth('api')->id();
            $stock_history->stock_type = "Add";
            $stock_history->save();


            $categories = explode(',', $request->categories);
            $category_array = [];
            foreach ($categories as $category)
            {
                $category = Category::where('id', $category)->with('parent')->firstOrFail();
                $category_array =  array_merge($category_array, $category->getParentIds());
            }
            $product->categories()->attach($category_array);

            $product_attributes = [];
            $attribute_values = [];
            $product_attributes = explode(',', $request->product_attribute);
            $attribute_values = explode(',', $request->product_attribute_value);
            if(count($product_attributes) != count($attribute_values))
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.attribute_combination_miss_match'),
                        'message_code' => 'attribute_combination_miss_match',
                    ], 200);
            }

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

            if($image_name_in_db)
            {
                $product_variant_image = new ProductVariantImage();
                $product_variant_image->product_variant_id = $product_variant->id;
                $product_variant_image->image = $image_name_in_db;
                $product_variant_image->is_default = true;
                $product_variant_image->save();
            }

            if($video_name_in_db)
            {
                $product_variant_video = new ProductVariantVideo();
                $product_variant_video->product_variant_id = $product_variant->id;
                $product_variant_video->video = $video_name_in_db;
                $product_variant_video->is_default = true;
                $product_variant_video->save();
            }


            $product_for_auction_or_sale = new ProductForAuctionOrSale();
            $product_for_auction_or_sale->user_id = $product->user_id;
            $product_for_auction_or_sale->product_id = $product->id;
            $product_for_auction_or_sale->type = $request->sale_type;
            $product_for_auction_or_sale->bid_start_time = $bid_start_time;
            $product_for_auction_or_sale->bid_end_time = $bid_end_time;
            $product_for_auction_or_sale->save();

//            if($product_for_auction_or_sale->type == 'Auction')
//            {
//                $product_auction_timing = new AuctionTiming();
//                $product_auction_timing->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
//                $product_auction_timing->bid_start_time = $bid_start_time;
//                $product_auction_timing->bid_end_time = $bid_end_time;
//                $product_auction_timing->created_by_admin_id = Auth::id();
//                $product_auction_timing->updated_by_admin_id = Auth::id();
//                $product_auction_timing->save();
//            }
            DB::commit();
            $products_auction_detail_query = ProductForAuctionOrSale::query();
            $products_auction_detail_query->where('status', 1);
            $products_auction_detail_query->where('id', $product_for_auction_or_sale->id);
            $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $product_variant) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
//                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                $query_9->with([
                    'product_inventory' => function($q) use ($product_variant) {
                        $q->where('id','=',$product_variant->id);
                        $q->with('default_image', 'default_video');
                    }
                ]);
            }]);

//            $products_auction_detail_query->whereHas('product', function ($query_2) use($product_variant) {
//                $query_2->whereHas('product_inventory', function ($query_10) use($product_variant) {
//                    $query_10->where('product_variants.id', $product_variant->id);
//                });
//            });

            $products_auction_detail_query->orderBy('id', 'DESC');
           $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

            $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

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

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_add_product_success'),
                    'message_code' => 'user_add_product_success',
                    'data' => [
                        'product' => $products_auction_detail_data[0],
                        'cart_count' => (string) getCartCount()
                    ]
                ], 200);

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

    public function enableDisableProduct(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|numeric|min:0',
                'product_for_auction_or_sale_id' => 'required|numeric|min:0',
                'product_variant_id' => 'required|numeric|min:0',
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
            $user_id = auth('api')->id();
            $product = Product::where('id', $request->product_id)->where('user_id', auth('api')->id())->firstOrFail();

            if($product->status)
            {
                $product->status = 0;
                $message = __('messages.product_enabled_success');
            }
            else
            {
                $product->status = 1;
                $message = __('messages.product_disabled_success');
            }
            $product->save();

            $products_auction_detail_query = ProductForAuctionOrSale::query();
            $products_auction_detail_query->where('status', 1);
            $products_auction_detail_query->where('id', $request->product_for_auction_or_sale_id);
            $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $request) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                $query_9->with([
                    'product_inventory' => function($q) use ($request) {
                        $q->where('id','=',$request->product_variant_id);
                        $q->with('default_image', 'default_video');
                    }
                ]);
            }]);

//            $products_auction_detail_query->whereHas('product', function ($query_2) use($request) {
//                $query_2->whereHas('product_inventory', function ($query_10) use($request) {
//                    $query_10->where('product_variants.id', $request->product_variant_id);
//                });
//            });

            $products_auction_detail_query->orderBy('id', 'DESC');
            $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

            $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => $message,
                    'message_code' => 'product_enable_disable_success',
                    'data' => [
                        "product" => $products_auction_detail_data[0],
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

    public function updateProduct(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $validator = Validator::make($request->all(), [
                "sale_type"  => "required",
                'attribute_set' => 'required',
                'brand' => 'brand',
                'name' => 'required|max:200',
                'name_ar' => 'required|max:200',
               // 'sku' => 'required|max:200|unique:product_variants',
                'regular_price' => 'required_if:sale_type,==,Sale',
                'final_price' => 'required_if:sale_type,==,Sale|lte:regular_price',
                'bid_value' => 'required_if:sale_type,==,Auction',
                'bid_start_price' => 'required_if:sale_type,==,Auction',
                'bid_start_time' => 'required_if:sale_type,==,Auction',
                'bid_end_time' => 'required_if:sale_type,==,Auction',
                'estimate_start_price' => 'required_if:sale_type,==,Auction',
                'estimate_end_price' => 'required_if:sale_type,==,Auction',
                'quantity' => 'required',
                'categories' => 'required',
                'product_attribute' => 'required',
                "product_attribute.*"  => "required",
                'product_attribute_value' => 'required',
                "product_attribute_value.*"  => "required",
                'description' => 'required',
                'description_ar' => 'required',
                'beiaat_handled' => 'required',
                'cost' => 'required_if:beiaat_handled,==,1',
                "product_id" => 'required|numeric|min:0',
                "product_for_auction_or_sale_id" => 'required|numeric|min:0',
                "product_variant_id" => 'required|numeric|min:0',
                //'image' => 'required|mimes:jpeg,jpg,png,gif',
                //'video' => 'mimes:mp4,ogx,oga,ogv,ogg,webm',

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

            $user = auth('api')->user();

            if(!$user->boutiques->count())
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutique_available'),
                        'message_code' => 'no_boutique_available',
                    ], 200);
            }

            if($user->package && $user->package->features)
            {
                $package_features =  $user->getPackageFeatures();
            }
            else
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_package_available'),
                        'message_code' => 'no_package_available',
                    ], 200);
            }

            $sale_permission = false;
            if($request->sale_type == "Sale")
            {
                if (in_array('manage-sale', $package_features)) $sale_permission = true;
            }
            elseif($request->sale_type == "Auction")
            {
                if(in_array('manage-auction', $package_features)) $sale_permission = true;
            }
            else
            {

            }

            if(!$sale_permission)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_permission_to_create_product'),
                        'message_code' => 'no_permission_to_create_product',
                    ], 200);
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
            if((isset($request->new_arrival_start_time) && $request->new_arrival_start_time) && (isset($request->new_arrival_end_time) && $request->new_arrival_end_time))
            {
                $start_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->new_arrival_start_time), $time_zone);
                $end_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->new_arrival_end_time), $time_zone);
            }

            $bid_start_time = null;
            $bid_end_time = null;
            if((isset($request->bid_start_time) && $request->bid_start_time) && (isset($request->bid_end_time) && $request->bid_end_time))
            {
                $bid_start_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->bid_start_time), $time_zone);
                $bid_end_time = getUtcTimeFromLocal(getDbDateTimeFormat($request->bid_end_time), $time_zone);
            }

            $product_variant = ProductVariant::findOrFail($request->product_variant_id);
            $product_for_auction_or_sale = ProductForAuctionOrSale::findOrFail($request->product_for_auction_or_sale_id);
            $product = Product::where('user_id', auth('api')->id())->findOrFail($request->product_id);

            $slug = Str::slug($request->name, '-');
            $image_name_in_db = null;
//            if($request->hasfile('image'))
//            {
//                $image = $request->file('image');
//                $image_name = $slug."-".time().'.'.$image->extension();
//                if(strtolower($image->extension()) == 'gif')
//                {
//                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
//                }
//                else
//                {
//                    $original_image = Image::make($image);
//                    $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
//                    $original_image_file = $original_image->stream()->__toString();
//                    $thumb_image_file = $thumb_image->stream()->__toString();
//                }
//                if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
//                {
//                    if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
//                    {
//                        $image_name_in_db = $image_name;
//                    }
//                }
//            }


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
            }
            elseif(isset($request->image) && $request->image)
            {
                $image = $request->image;
                $image_name = $slug."-".time().'.png';

                //  Image::make(file_get_contents($data->base64_image))->save($path);
                $original_image = Image::make($image);
                $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
                $original_image_file = $original_image->stream()->__toString();
                $thumb_image_file = $thumb_image->stream()->__toString();

                if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }
            else
            {

            }

            $video_name_in_db = null;
//            if($request->hasfile('video'))
//            {
//                $video = $request->file('video');
//                $video_name = $slug."-video-".time().'.'.$video->extension();
//                if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($request->video), ['visibility' => 'public']))
//                {
//                    $video_name_in_db = $video_name;
//                }
//            }


            if($request->hasfile('video'))
            {
                $video = $request->file('video');
                $video_name = $slug."-video-".time().'.'.$video->extension();
                if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($request->video), ['visibility' => 'public']))
                {
                    $video_name_in_db = $video_name;
                }
            }
            elseif(isset($request->video) && $request->video)
            {
                $video_name = $slug."-video-".time().'.mp4';
                if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($request->video), ['visibility' => 'public']))
                {
                    $video_name_in_db = $video_name;
                }
            }
            else
            {

            }

            DB::beginTransaction();
            $user_boutique = $user->boutiques[0];

            $product->brand = $request->brand;
            $product->name = $request->name;
            $product->name_ar = $request->name_ar;
            $product->type = "Simple";
            $product->sale_type = $request->sale_type;
            $product->user_boutique_id = $user_boutique->id;
            $product->user_id = $user->id;
            $product->attribute_set_id = $request->attribute_set;
            $product->slug = $slug;
            $product->description = $request->description;
            $product->description_ar = $request->description_ar;
            $product->new_arrival_start_time = $start_time;
            $product->new_arrival_end_time = $end_time;
            $product->status = $status;
            $product->most_selling_status = $most_selling_status;
            $product->save();

            $product_variant->product_id = $product->id;
//            $product_variant->sku = $request->sku;
            $product_variant->quantity = 0; // add in stocks table
            if(isset($request->regular_price) && $request->regular_price) $product_variant->regular_price = $request->regular_price;
            if(isset($request->final_price) && $request->final_price) $product_variant->final_price = $request->final_price;
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
            $product_variant->save();


//            $stock = new Stock();
//            $stock->user_boutique_id = $user_boutique->id;
//            $stock->product_variant_id = $product_variant->id;
//            $stock->quantity = $request->quantity;
//            $stock->created_by_user_id = auth('api')->id();
//            $stock->updated_by_user_id = auth('api')->id();
//            $stock->save();
//
//            $stock_history = new StockHistory();
//            $stock_history->stock_id = $stock->id;
//            $stock_history->user_boutique_id = $user_boutique->id;
//            $stock_history->product_variant_id = $product_variant->id;
//            $stock_history->quantity = $request->quantity;
//            $stock_history->add_by = "AddByVendor";
//            $stock_history->stock_type = "Add";
//            $stock_history->created_by_user_id = auth('api')->id();
//            $stock_history->updated_by_user_id = auth('api')->id();
//            $stock_history->stock_type = "Add";
//            $stock_history->save();


            $categories = explode(',', $request->categories);
            $category_array = [];
            foreach ($categories as $category)
            {
                $category = Category::where('id', $category)->with('parent')->firstOrFail();
                $category_array =  array_merge($category_array, $category->getParentIds());
            }
            $product->categories()->sync($category_array);

            $product_attributes = [];
            $attribute_values = [];
            $product_attributes = explode(',', $request->product_attribute);
            $attribute_values = explode(',', $request->product_attribute_value);
            if(count($product_attributes) != count($attribute_values))
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.attribute_combination_miss_match'),
                        'message_code' => 'attribute_combination_miss_match',
                    ], 200);
            }

            DB::table('product_attributes')->where('product_variant_id', $product_variant->id)->delete();
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

            if($image_name_in_db)
            {
                $product_variant_image = new ProductVariantImage();
                $product_variant_image->product_variant_id = $product_variant->id;
                $product_variant_image->image = $image_name_in_db;
                $product_variant_image->is_default = true;
                $product_variant_image->save();
            }

            if($video_name_in_db)
            {
                $product_variant_video = new ProductVariantVideo();
                $product_variant_video->product_variant_id = $product_variant->id;
                $product_variant_video->video = $video_name_in_db;
                $product_variant_video->is_default = true;
                $product_variant_video->save();
            }

            $product_for_auction_or_sale->user_id = $product->user_id;
            $product_for_auction_or_sale->product_id = $product->id;
            $product_for_auction_or_sale->type = $request->sale_type;
            $product_for_auction_or_sale->bid_start_time = $bid_start_time;
            $product_for_auction_or_sale->bid_end_time = $bid_end_time;
            $product_for_auction_or_sale->save();

//            if($product_for_auction_or_sale->type == 'Auction')
//            {
//                $product_auction_timing = new AuctionTiming();
//                $product_auction_timing->product_for_auction_or_sale_id = $product_for_auction_or_sale->id;
//                $product_auction_timing->bid_start_time = $bid_start_time;
//                $product_auction_timing->bid_end_time = $bid_end_time;
//                $product_auction_timing->created_by_admin_id = Auth::id();
//                $product_auction_timing->updated_by_admin_id = Auth::id();
//                $product_auction_timing->save();
//            }
            DB::commit();
            $products_auction_detail_query = ProductForAuctionOrSale::query();
            $products_auction_detail_query->where('status', 1);
            $products_auction_detail_query->where('id', $product_for_auction_or_sale->id);
            $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $product_variant) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                $query_9->with([
                    'product_inventory' => function($q) use ($product_variant) {
                        $q->where('id','=',$product_variant->id);
                        $q->with('default_image', 'default_video');
                    }
                ]);
            }]);

//            $products_auction_detail_query->whereHas('product', function ($query_2) use($product_variant) {
//                $query_2->whereHas('product_inventory', function ($query_10) use($product_variant) {
//                    $query_10->where('product_variants.id', $product_variant->id);
//                });
//            });

            $products_auction_detail_query->orderBy('id', 'DESC');
            $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

            $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_update_product_success'),
                    'message_code' => 'user_update_product_success',
                    'data' => [
                        'product' => $products_auction_detail_data[0],
                        'cart_count' => (string) getCartCount()
                    ]
                ], 200);

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

}

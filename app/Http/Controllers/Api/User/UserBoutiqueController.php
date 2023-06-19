<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\BoutiqueCategory;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\SpecialPrice;
use App\Models\User;
use App\Models\UserBoutique;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\ReservedStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserBoutiqueController extends Controller
{
    public function getUserBoutiques(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as title";
                $name_array['description'] = "description_ar as description";
                $name_array['delivery_text'] = "delivery_text_ar as delivery_text";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
                $name_array['delivery_text'] = "delivery_text";
            }

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $boutique_query = UserBoutique::query();
            $boutique_query->where('status', 1);
            if(boutiqueFeaturedSorting()) $boutique_query->orderBy('is_featured', 'DESC');
            $boutique_query->orderBy('sort_order', 'ASC');
            $boutique_query->select('id', $name_array['name'], $name_array['description'], $name_array['delivery_text'], 'image', 'cover_image', 'is_featured');
            if(isset($request->category_id) && $request->category_id)
            {
                $get_category_ids = explode( ",", $request->category_id);
                $boutique_query->whereHas('boutique_categories', function ($query_3) use ($get_category_ids){
                    $query_3->whereIn("user_boutique_categories.boutique_category_id", $get_category_ids);
                });
            }

            if(isset($request->boutique_type) && $request->boutique_type)
            {
                if($request->boutique_type == "VendorBoutique")
                {
                    $boutique_query->whereHas('user', function ($query_2) use ($request){
                        $query_2->where("users.is_supplier_vendor", 1);
                    });
                }
            }

            //$boutique_query->orderBy('id', 'DESC');
            $boutique_count_query = clone $boutique_query;
            $boutique_count =  $boutique_count_query->count();
            if(empty($request->get('page'))) $per_page = $boutique_count;
            $boutique_data_array = $boutique_query->paginate($per_page);
            $boutique_data = [];
            foreach ($boutique_data_array->items() as $key => $boutique_data_item)
            {
                $pro_ids = Product::where('user_boutique_id', $boutique_data_item->id)->pluck('id')->toArray();
                $category_ids = DB::table('product_categories')->whereIn('product_id', $pro_ids)->pluck('category_id')->toArray();
                $categories = Category::whereIn('id', $category_ids)->where('parent_id', NULL)->pluck($name_array['name'])->implode(', ');
                $boutique_temp_data = [
                    "id" => $boutique_data_item->id,
                    "name" => (string) $boutique_data_item->name,
                    "description" => $boutique_data_item->description,
                    "categories" => (string) $categories,
                    "delivery_text" => (string) $boutique_data_item->delivery_text,
                    "is_live" =>  (string) $boutique_data_item->getIsLiveStatus(),
                    "is_featured" => (string) $boutique_data_item->is_featured,
                    "thumb_image" => (string) $boutique_data_item->thumb_image,
                    "original_image" => (string) $boutique_data_item->original_image,
                    "cover_image_thumb" => (string) $boutique_data_item->cover_image_thumb,
                    "cover_image_original" => (string) $boutique_data_item->cover_image_original,
                ];
                array_push($boutique_data, $boutique_temp_data);
            }

            $category_query = BoutiqueCategory::query();
            $category_query->where('status', 1);
            $category_query->select('id', $name_array['title'], 'image', 'image_ar');
            $category_query->orderBy('id', 'DESC');
            $category_data = $category_query->get();
            $boutique_category_data = [];
            foreach ($category_data as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "name" => (string) $category_data_item->title,
                    "thumb_image" => (string) $category_data_item->thumb_image,
                    "original_image" => (string) $category_data_item->original_image,
                ];
                array_push($boutique_category_data, $category_temp_data);
            }


            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_boutique_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $boutique_data_array->lastPage(),
                            'current_page' => (string) $boutique_data_array->currentPage(),
                            'total_records' => (string) $boutique_data_array->total(),
                            'records_on_current_page' => (string) $boutique_data_array->count(),
                            'record_from' => (string) $boutique_data_array->firstItem(),
                            'record_to' => (string) $boutique_data_array->lastItem(),
                            'per_page' => (string) $boutique_data_array->perPage(),
                        ],
                        "boutiques" => $boutique_data,
                        "boutique_categories" => $boutique_category_data,
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

    public function getUserBoutiqueCategory(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['title'] = "title_ar as title";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $category_query = BoutiqueCategory::query();
            $category_query->where('status', 1);
            $category_query->select('id', $name_array['title'], 'image', 'image_ar');
            $category_query->orderBy('id', 'DESC');
            $category_data = $category_query->paginate($per_page);

            $category_count_query = clone $category_query;
            $category_count =  $category_count_query->count();
            if(empty($request->get('page'))) $per_page = $category_count;
            $category_data_array = $category_query->paginate($per_page);
            $category_data = [];
            foreach ($category_data_array->items() as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "name" => (string) $category_data_item->title,
                    "thumb_image" => (string) $category_data_item->thumb_image,
                    "original_image" => (string) $category_data_item->original_image,
                ];
                array_push($category_data, $category_temp_data);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_boutique_category_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $category_data_array->lastPage(),
                            'current_page' => (string) $category_data_array->currentPage(),
                            'total_records' => (string) $category_data_array->total(),
                            'records_on_current_page' => (string) $category_data_array->count(),
                            'record_from' => (string) $category_data_array->firstItem(),
                            'record_to' => (string) $category_data_array->lastItem(),
                            'per_page' => (string) $category_data_array->perPage(),
                        ],
                        "boutique_categories" => $category_data,
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


    public function myBoutique(Request $request)
    {
        try
        {
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
                $name_array['delivery_text'] = "delivery_text_ar as delivery_text";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
                $name_array['delivery_text'] = "delivery_text";
            }

            ;
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();
            $boutique_query = UserBoutique::query();
            $boutique_query->where('status', 1);
            if(boutiqueFeaturedSorting()) $boutique_query->orderBy('is_featured', 'DESC');
            $boutique_query->orderBy('sort_order', 'ASC');
            $boutique_query->where('user_id', auth('api')->id());
            $boutique_query->select('id', $name_array['name'], $name_array['description'], $name_array['delivery_text'], 'image', 'cover_image', 'user_id', 'views_count', 'is_featured');
            $boutique_data = $boutique_query->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }
            $boutique_data_array = [];
            $pro_ids = Product::where('user_boutique_id', $boutique_data->id)->pluck('id')->toArray();
            $category_ids = DB::table('product_categories')->whereIn('product_id', $pro_ids)->pluck('category_id')->toArray();
            $categories = Category::whereIn('id', $category_ids)->where('parent_id', NULL)->pluck($name_array['name'])->implode(', ');

            // Auction Products Most Selling Products
            $products_ongoing_auction_query = ProductForAuctionOrSale::query();
            $products_ongoing_auction_query->where('status', 1);
            $products_ongoing_auction_query->where('type', 'Auction');
            $products_ongoing_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_ongoing_auction_query->with(['product' => function ($query_9) use($name_array, $boutique_data) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_ongoing_auction_query->whereHas('product', function ($query_2) use ($boutique_data) {
                $query_2->where("products.status", 1);
                $query_2->where("products.user_boutique_id", $boutique_data->id);
            });
            $products_ongoing_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_start_time', '<', $current_date_time)->where('bid_end_time', '>', $current_date_time);
            });


            $products_ongoing_auction_query->orderBy('id', 'DESC');
            $products_ongoing_auction_data_array = $products_ongoing_auction_query->limit(4)->get();
            $ongoing_auction_products = productData($products_ongoing_auction_data_array, $time_zone); // defined in helpers
            // Auction Products Most Selling Products


            $products_for_sale_query = ProductForAuctionOrSale::query();
            $products_for_sale_query->where('status', 1);
            $products_for_sale_query->where('type', 'Sale');
            $products_for_sale_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_sale_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_sale_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_sale_query->whereHas('product', function ($query_10) use ($boutique_data) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
            });

            $products_for_bulk_query = ProductForAuctionOrSale::query();
            $products_for_bulk_query->where('status', 1);
            $products_for_bulk_query->where('type', 'Bulk');
            $products_for_bulk_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_bulk_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_bulk_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_bulk_query->whereHas('product', function ($query_10) use ($boutique_data) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
            });

            $products_for_auction_query = ProductForAuctionOrSale::query();
            $products_for_auction_query->where('status', 1);
            $products_for_auction_query->where('type', 'Auction');
            $products_for_auction_query->where('bid_purchase_status', 0);
            $products_for_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_auction_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_auction_query->addSelect(DB::raw('(SELECT bid_start_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });
            $products_for_auction_query->whereHas('product', function ($query_10) use ($boutique_data) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
            });


            $final_products_query_1 = $products_for_auction_query->union($products_for_sale_query);
            $final_products_query = $final_products_query_1->union($products_for_bulk_query);

            $final_products_query->orderBy('created_at_sort', 'DESC');

            $products_for_auction_or_sale_data = $final_products_query->paginate(4);
            $product_data = productData($products_for_auction_or_sale_data->items(), $time_zone); // defined in helpers


            $new_orders = Order::where('user_boutique_id', $boutique_data->id)->where('order_status', 'Pending')->count();
            $completed_orders = Order::where('user_boutique_id', $boutique_data->id)->where('order_status', 'Delivered')->count();
            $cancelled_orders = Order::where('user_boutique_id', $boutique_data->id)->where('order_status', 'Cancelled')->count();
            $total_sales = Order::where('user_boutique_id', $boutique_data->id)->where('payment_status', 'CAPTURED')->sum('total_amount');
            $total_commission = Order::where('user_boutique_id', $boutique_data->id)->where('payment_status', 'CAPTURED')->sum('commission_amount');
            $total_delivery_charge = Order::where('user_boutique_id', $boutique_data->id)->where('payment_status', 'CAPTURED')->sum('delivery_charges');
            $total_earned = $total_sales - $total_commission;

            $year = date('Y', strtotime($current_date_time_local));
            $month_array = [__('messages.jan'), __('messages.feb'), __('messages.mar'), __('messages.apr'), __('messages.may'), __('messages.jun'), __('messages.jul'), __('messages.aug'), __('messages.sep'), __('messages.oct'), __('messages.nov'), __('messages.dec')];
            $sale_graph_array = [];
            for($i=1; $i<=12; $i++)
            {
                $total_sales_month = Order::where('user_boutique_id', $boutique_data->id)->where('payment_status', 'CAPTURED')->whereMonth('created_at', $i)->WhereYear('created_at', $year)->sum('total_amount');
                $temp_array = [
                    "month" => $month_array[$i-1],
                    "year" => (string) date('Y'),
                    "total_sales" => (string) $total_sales_month
                 ];
                array_push($sale_graph_array, $temp_array);
            }


            $boutique_data_array = [
                "id" => $boutique_data->id,
                "user_id" => (auth('api')->check())?auth('api')->id():0,
                "name" => (string) $boutique_data->name,
                "description" => $boutique_data->description,
                "categories" => $categories,
                "delivery_text" => (string) $boutique_data->delivery_text,
                "followers_count" => (string) $boutique_data->getFollowersCount(),
                "follow_status" => (string) $boutique_data->getFollowStatus(),
                "views_count" => (string) $boutique_data->views_count,
                "package" => ($boutique_data->user->package_id)?$boutique_data->user->package->getTitle():'',
                "package_start_date" => ($boutique_data->user->package_id) ? (string) getDbDateFormat($boutique_data->user->package_start_date) : '',
                "package_end_date" => ($boutique_data->user->package_id) ? (string) getDbDateFormat($boutique_data->user->package_end_date) : '',
                "is_live" =>  (string) $boutique_data->getIsLiveStatus(),
                "is_featured" => (string) $boutique_data->is_featured,
                "new_orders" => (string) $new_orders,
                "completed_orders" => (string) $completed_orders,
                "cancelled_orders" => (string) $cancelled_orders,
                "total_sales" => (string) $total_sales,
                "delivery_earnings" => (string) $total_delivery_charge,
                "total_earnings" => (string) $total_earned,
                "profit" => (string) $total_earned,
                "sales_graph" => $sale_graph_array,
                "thumb_image" => (string) $boutique_data->thumb_image,
                "original_image" => (string) $boutique_data->original_image,
                "cover_image_thumb" => (string) $boutique_data->cover_image_thumb,
                "cover_image_original" => (string) $boutique_data->cover_image_original,
                "ongoing_auctions" => $ongoing_auction_products,
                "products" => $product_data,
                'cart_count' => (string) getCartCount()
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_boutique_success',
                    'data' => $boutique_data_array,
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

    public function myBoutiqueProducts(Request $request)
    {
        try
        {
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();
            $boutique_data = UserBoutique::where('user_id', auth('api')->id())->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }

            $products_for_sale_query = ProductForAuctionOrSale::query();
            $products_for_sale_query->where('status', 1);
            $products_for_sale_query->where('type', 'Sale');
            $products_for_sale_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_sale_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id', 'new_arrival_start_time', 'new_arrival_end_time');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_sale_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_sale_query->whereHas('product', function ($query_10) use ($boutique_data, $current_date_time, $request) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
                if(isset($request->is_new_product) && $request->is_new_product) {
                    $query_10->where('products.new_arrival_start_time', '<=', $current_date_time)->where('products.new_arrival_end_time', '>=', $current_date_time);
                }
            });


            $products_for_bulk_query = ProductForAuctionOrSale::query();
            $products_for_bulk_query->where('status', 1);
            $products_for_bulk_query->where('type', 'Bulk');
            $products_for_bulk_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_bulk_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id', 'new_arrival_start_time', 'new_arrival_end_time');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_bulk_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_bulk_query->whereHas('product', function ($query_10) use ($boutique_data, $current_date_time, $request) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
                if(isset($request->is_new_product) && $request->is_new_product) {
                    $query_10->where('products.new_arrival_start_time', '<=', $current_date_time)->where('products.new_arrival_end_time', '>=', $current_date_time);
                }
            });

            $products_for_auction_query = ProductForAuctionOrSale::query();
            $products_for_auction_query->where('status', 1);
            $products_for_auction_query->where('type', 'Auction');
            $products_for_auction_query->where('bid_purchase_status', 0);
            $products_for_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_auction_query->with(['product' => function ($query) use($name_array) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id', 'new_arrival_start_time', 'new_arrival_end_time');
                $query->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_auction_query->addSelect(DB::raw('(SELECT bid_start_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });
            $products_for_auction_query->whereHas('product', function ($query_10) use ($boutique_data, $current_date_time, $request) {
                $query_10->where("products.status", 1)->where('user_boutique_id', $boutique_data->id);
                if(isset($request->is_new_product) && $request->is_new_product) {
                    $query_10->where('products.new_arrival_start_time', '<=', $current_date_time)->where('products.new_arrival_end_time', '>=', $current_date_time);
                }
            });


            $final_products_query_1 = $products_for_auction_query->union($products_for_sale_query);
            $final_products_query = $final_products_query_1->union($products_for_bulk_query);

            $final_products_query->orderBy('created_at_sort', 'DESC');

            $products_for_auction_or_sale_count_query = clone $final_products_query;
            $products_for_auction_or_sale_count = $products_for_auction_or_sale_count_query->count();
            if(empty($request->get('page'))) $per_page = $products_for_auction_or_sale_count;
            $products_for_auction_or_sale_data = $final_products_query->paginate($per_page);
            $product_data = productData($products_for_auction_or_sale_data->items(), $time_zone); // defined in helpers

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_boutique_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $products_for_auction_or_sale_data->lastPage(),
                            'current_page' => (string) $products_for_auction_or_sale_data->currentPage(),
                            'total_records' => (string) $products_for_auction_or_sale_data->total(),
                            'records_on_current_page' => (string) $products_for_auction_or_sale_data->count(),
                            'record_from' => (string) $products_for_auction_or_sale_data->firstItem(),
                            'record_to' => (string) $products_for_auction_or_sale_data->lastItem(),
                            'per_page' => (string) $products_for_auction_or_sale_data->perPage(),
                        ],
                        "my_products" => $product_data,
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

    public function myBoutiqueOrders(Request $request)
    {
        try
        {
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $time_zone = getLocalTimeZone($request->time_zone);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $boutique_data = UserBoutique::where('user_id', auth('api')->id())->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }

            $ordered_item_query = OrderDetail::query();
            $ordered_item_query->where('user_boutique_id', $boutique_data->id);

            $ordered_item_count_query = clone $ordered_item_query;
            $ordered_item_all_query = clone $ordered_item_query;
            $ordered_item_count = $ordered_item_count_query->count();
            if(empty($request->get('page'))) $per_page = $ordered_item_count;
            $ordered_item_data = $ordered_item_query->paginate($per_page);
            $ordered_data = $ordered_item_data->items();

            $ordered_summary = [];
            $final_price = 0;
            $regular_price = 0;
            $discount = 0;
            $ordered_items = orderData($ordered_data, $time_zone);

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'boutique_ordered_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $ordered_item_data->lastPage(),
                            'current_page' => (string) $ordered_item_data->currentPage(),
                            'total_records' => (string) $ordered_item_data->total(),
                            'records_on_current_page' => (string) $ordered_item_data->count(),
                            'record_from' => (string) $ordered_item_data->firstItem(),
                            'record_to' => (string) $ordered_item_data->lastItem(),
                            'per_page' => (string) $ordered_item_data->perPage(),
                        ],
                        "ordered_items" => $ordered_items,

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

    public function myBoutiqueSales(Request $request)
    {
        try
        {
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $time_zone = getLocalTimeZone($request->time_zone);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'sale_type' => 'required',
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


            $boutique_data = UserBoutique::where('user_id', auth('api')->id())->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }



            $ordered_item_query = OrderDetail::query();
            $ordered_item_query->where('user_boutique_id', $boutique_data->id);
            if(isset($request->sale_type) && $request->sale_type)
            {
                if($request->sale_type == "Sale")
                {
                    $ordered_item_query->whereIn('sale_type', ['Sale', 'Bulk']);
                }
                elseif($request->sale_type == "Auction")
                {
                    $ordered_item_query->where('sale_type', 'Auction');
                }
                else
                {

                }
            }

            $ordered_item_query->whereHas('order', function ($query_10) {
                $query_10->where('orders.order_status', "Delivered");
            });
            $ordered_item_count_query = clone $ordered_item_query;
            $ordered_item_all_query = clone $ordered_item_query;
            $ordered_item_count = $ordered_item_count_query->count();
            if(empty($request->get('page'))) $per_page = $ordered_item_count;
            $ordered_item_data = $ordered_item_query->paginate($per_page);
            $ordered_data = $ordered_item_data->items();

            $ordered_summary = [];
            $final_price = 0;
            $regular_price = 0;
            $discount = 0;
            $ordered_items = orderData($ordered_data, $time_zone);

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'boutique_ordered_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $ordered_item_data->lastPage(),
                            'current_page' => (string) $ordered_item_data->currentPage(),
                            'total_records' => (string) $ordered_item_data->total(),
                            'records_on_current_page' => (string) $ordered_item_data->count(),
                            'record_from' => (string) $ordered_item_data->firstItem(),
                            'record_to' => (string) $ordered_item_data->lastItem(),
                            'per_page' => (string) $ordered_item_data->perPage(),
                        ],
                        "ordered_items" => $ordered_items,

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

    public function myBoutiqueOrderDetails(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|numeric|min:1',
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

            $boutique_data = UserBoutique::where('user_id', auth('api')->id())->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }

            $order = Order::findOrFail($request->order_id);
            $ordered_item_query = OrderDetail::query();
            $ordered_item_query->where('user_boutique_id', $boutique_data->id);
            $ordered_item_query->where('order_id', $order->id);
            $ordered_item_count_query = clone $ordered_item_query;
            $ordered_item_all_query = clone $ordered_item_query;
            $ordered_item_count = $ordered_item_count_query->count();
            if(empty($request->get('page'))) $per_page = $ordered_item_count;
            $ordered_item_data = $ordered_item_query->paginate($per_page);
            $ordered_data = $ordered_item_data->items();

            $ordered_items = orderData($ordered_data, $time_zone);
            $order_summary = orderSummary($order, $time_zone);
            $payment_details = paymentDetails($order);
            $last_order_address = OrderAddress::where('order_id', $order->id)->first();

            $available_status = [
                "Pending",
                "Accepted",
                "InProgress",
                "ReadyForDelivery",
                "OutForDelivery",
                "Delivered",
                "Rescheduled",
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_order_detail_success',
                    'data' => [
                        'delivery_address' => addressDataSingle($last_order_address),
                        "order_summary" => $order_summary,
                        "payment_details" => $payment_details,
                        "ordered_items" => $ordered_items,
                        "available_status" => $available_status,
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

    public function changeOrderStatus(Request $request)
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
                'order_id' => 'required|numeric|min:0',
                'status' => 'required',
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
            $boutique_data = UserBoutique::where('user_id', auth('api')->id())->first();

            if(!$boutique_data)
            {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.no_boutiques_found'),
                        'message_code' => 'no_boutiques_found',
                    ], 200);
            }

            $order = Order::findOrFail($request->order_id);
            $order_current_status = $order->order_status;
            $order->order_status = $request->status;
            $order->save();

            //maintain order history for stock
            if($request->order_id){
                if($request->status == 'Delivered' || $request->status == 'Cancelled'){
                    $stock_type1 = ($request->status == 'Delivered') ? 'OrderDelivered' : (($request->status == 'Cancelled') ? 'OrderCancelled' : "");
                    
                    $order_details = OrderDetail::where('order_id', $order->id)->get();
                    $order_quantity_change_flag = false;
                    if($order_details){
                        foreach($order_details as $order_detail){
                            $order_quantity = $order_detail->quantity;

                            $stock =  Stock::where('product_variant_id', $order_detail->product_variant_id)->first();
                            $available_stock_quantity = $stock->product_variant->availableStockQuantity();
                         
                            if($request->status == 'Delivered'){
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
                            }else if($request->status == 'Cancelled'){
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
                            $stock_history->add_by = "AddByUser";
                            $stock_history->stock_type = $stock_type1;
                            $stock_history->created_by_user_id = $order_detail->user_id;
                            $stock_history->updated_by_user_id = $order_detail->user_id;
                            $stock_history->save();
        
                        }
                    }
                
                    //Removed entry from reserved stock table for order delivered or cancelled
                    $reserved_stocks = ReservedStock::where(['order_id' => $order->id])->delete();
                }
            }
            
            $ordered_item_query = OrderDetail::query();
            $ordered_item_query->where('user_boutique_id', $boutique_data->id);
            $ordered_item_query->where('order_id', $order->id);
            $ordered_item_count_query = clone $ordered_item_query;
            $ordered_item_all_query = clone $ordered_item_query;
            $ordered_item_count = $ordered_item_count_query->count();
            if(empty($request->get('page'))) $per_page = $ordered_item_count;
            $ordered_item_data = $ordered_item_query->paginate($per_page);
            $ordered_data = $ordered_item_data->items();

            $ordered_items = orderData($ordered_data, $time_zone);
            $order_summary = orderSummary($order, $time_zone);
            $payment_details = paymentDetails($order);
            $last_order_address = OrderAddress::where('order_id', $order->id)->first();

            $available_status = [
                "Pending",
                "Accepted",
                "InProgress",
                "ReadyForDelivery",
                "OutForDelivery",
                "Delivered",
                "Rescheduled",
            ];
            sendOrderUpdates($order->id);
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
                    'status' => 200,
                    'message' => __('messages.order_status_changed_success'),
                    'message_code' => 'order_status_changed_success',
                    'data' => [
                        'delivery_address' => addressDataSingle($last_order_address),
                        "order_summary" => $order_summary,
                        "payment_details" => $payment_details,
                        "ordered_items" => $ordered_items,
                        "available_status" => $available_status,
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

    public function getUserBoutiqueDetails(Request $request)
    {
        try
        {
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
            }

            $validator = Validator::make($request->all(), [
                'boutique_id' => 'required',
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

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();
            $boutique_query = UserBoutique::query();
            $boutique_query->where('status', 1);
            $boutique_query->where('id', $request->boutique_id);
            $boutique_query->select('id', $name_array['name'], $name_array['description'], 'image', 'cover_image', 'user_id', 'views_count', 'is_featured');
            $boutique_data = $boutique_query->first();

            $boutique_data_array = [];
            $pro_ids = Product::where('user_boutique_id', $boutique_data->id)->pluck('id')->toArray();
            $category_ids = DB::table('product_categories')->whereIn('product_id', $pro_ids)->pluck('category_id')->toArray();
            $categories = Category::whereIn('id', $category_ids)->where('parent_id', NULL)->pluck($name_array['name'])->implode(', ');

            $special_variant_ids = SpecialPrice::where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time)->distinct()->pluck('product_variant_id')->toArray();
            $is_discounted_variant_ids = ProductVariant::whereRaw('final_price < regular_price')->distinct()->pluck('id')->toArray();
            $is_discounted_variant_ids = array_merge($is_discounted_variant_ids, $special_variant_ids);
            $is_discounted_product_ids = ProductVariant::whereIn('id', $is_discounted_variant_ids)->distinct()->pluck('product_id')->toArray();
            $is_discounted_product_ids = Product::whereIn('id', $is_discounted_product_ids)->whereIn('sale_type', ['Sale', 'Bulk'])->distinct()->pluck('id')->toArray();

            // Sale Products Most Selling Products
            $products_for_sale_most_selling_query = ProductForAuctionOrSale::query();
            $products_for_sale_most_selling_query->where('status', 1);
            $products_for_sale_most_selling_query->where('type', 'Sale');
            if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_most_selling_query->where('type', 'NoType');
            $products_for_sale_most_selling_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_for_sale_most_selling_query->with(['product' => function ($query_1) use($name_array) {
                $query_1->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_sale_most_selling_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids){
                $query_2->where("products.most_selling_status", 1);
                $query_2->where("products.status", 1);
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $products_for_sale_most_selling_query->orderBy('id', 'DESC');
            $products_for_sale_most_selling_data_array = $products_for_sale_most_selling_query->limit(4)->get();
            $most_selling_sale_products = productData($products_for_sale_most_selling_data_array, $time_zone); // defined in helpers
            // Sale Products Most Selling Products

            // Sale Products New Products
            $products_for_sale_new_arrivals_query = ProductForAuctionOrSale::query();
            $products_for_sale_new_arrivals_query->where('status', 1);
            $products_for_sale_new_arrivals_query->where('type', 'Sale');
            if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_new_arrivals_query->where('type', 'NoType');
            $products_for_sale_new_arrivals_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_for_sale_new_arrivals_query->with(['product' => function ($query_3) use($name_array) {
                $query_3->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_3->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_sale_new_arrivals_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids) {
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                $query_2->where("products.status", 1);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $products_for_sale_new_arrivals_query->whereHas('product', function ($query_2) use($current_date_time) {
                $query_2->where(function($query_4) use ($current_date_time){
                    $query_4->where(function($query_5) use ($current_date_time){
                        $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                    });
                    // $query_4->orWhereNull('new_arrival_start_time');
                });;
            });
            $products_for_sale_new_arrivals_query->orderBy('id', 'DESC');
            $products_for_sale_new_arrivals_data_array = $products_for_sale_new_arrivals_query->limit(4)->get();
            $new_sale_products = productData($products_for_sale_new_arrivals_data_array, $time_zone); // defined in helpers
            
            // Sale Products all Products
            $products_for_sale_all_products_query = ProductForAuctionOrSale::query();
            $products_for_sale_all_products_query->where('status', 1);
            $products_for_sale_all_products_query->where('type', 'Sale');
            if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_all_products_query->where('type', 'NoType');
            $products_for_sale_all_products_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_for_sale_all_products_query->with(['product' => function ($query_1) use($name_array) {
                $query_1->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_for_sale_all_products_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids){
                $query_2->where("products.status", 1);
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $products_for_sale_all_products_query->orderBy('id', 'DESC');
            $products_for_sale_all_products_data_array = $products_for_sale_all_products_query->limit(10)->get();
            $all_products_for_sale = productData($products_for_sale_all_products_data_array, $time_zone); // defined in helpers
            // Sale Products all Products
            //end Sale Products sale Products

            // Auction Products New Products
            $products_new_auction_query = ProductForAuctionOrSale::query();
            $products_new_auction_query->where('status', 1);
            $products_new_auction_query->where('type', 'Auction');
            if(!in_array('can-participate-auction', getUserPermissions())) $products_new_auction_query->where('type', 'NoType');
            $products_new_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_new_auction_query->with(['product' => function ($query_9) use($name_array, $boutique_data) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_new_auction_query->whereHas('product', function ($query_2) use($current_date_time) {
                $query_2->where(function($query_4) use ($current_date_time){
                    $query_4->where(function($query_5) use ($current_date_time){
                        $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                    });
                   // $query_4->orWhereNull('new_arrival_start_time');
                });
                $query_2->where('status', 1);
            });
            $products_new_auction_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids) {
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $products_new_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });
            $products_new_auction_query->orderBy('id', 'DESC');
            $products_new_auction_data_array = $products_new_auction_query->limit(4)->get();
            $new_auction_products = productData($products_new_auction_data_array, $time_zone); // defined in helpers
            // Auction Products New Products

            // Auction Products Most Selling Products
            $products_most_auction_query = ProductForAuctionOrSale::query();
            $products_most_auction_query->where('status', 1);
            $products_most_auction_query->where('type', 'Auction');
            if(!in_array('can-participate-auction', getUserPermissions())) $products_most_auction_query->where('type', 'NoType');
            $products_most_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $products_most_auction_query->with(['product' => function ($query_9) use($name_array, $boutique_data) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $products_most_auction_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids) {
                $query_2->where("products.most_selling_status", 1);
                $query_2->where("products.status", 1);
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $products_most_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });


            $products_most_auction_query->orderBy('id', 'DESC');
            $products_most_auction_data_array = $products_most_auction_query->limit(4)->get();
            $most_selling_auction_products = productData($products_most_auction_data_array, $time_zone); // defined in helpers
            // Auction Products Most Selling Products

            // Auction Products All Products
            $all_products_auction_query = ProductForAuctionOrSale::query();
            $all_products_auction_query->where('status', 1);
            $all_products_auction_query->where('type', 'Auction');
            if(!in_array('can-participate-auction', getUserPermissions())) $all_products_auction_query->where('type', 'NoType');
            $all_products_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
            $all_products_auction_query->with(['product' => function ($query_9) use($name_array, $boutique_data) {
                $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $all_products_auction_query->whereHas('product', function ($query_2) use ($boutique_data, $is_discounted_product_ids) {
                $query_2->where("products.status", 1);
                $query_2->where("products.user_boutique_id", $boutique_data->id);
                if(!in_array('can-purchase-discounted', getUserPermissions()))
                {
                    $query_2->whereNotIn("products.id", $is_discounted_product_ids);
                }
            });
            $all_products_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });


            $all_products_auction_query->orderBy('id', 'DESC');
            $all_products_auction_data_array = $all_products_auction_query->limit(10)->get();
            $all_products_for_auction = productData($all_products_auction_data_array, $time_zone); // defined in helpers
            // Auction Products All Products

            $boutique_data_array = [
                    "id" => $boutique_data->id,
                    "user_id" => (auth('api')->check())?auth('api')->id():0,
                    "name" => (string) $boutique_data->name,
                    "description" => $boutique_data->description,
                    "categories" => $categories,
                    "followers_count" => (string) $boutique_data->getFollowersCount(),
                    "follow_status" => (string) $boutique_data->getFollowStatus(),
                    "views_count" => (string) $boutique_data->views_count,
                    "package" => ($boutique_data->user->package_id)?$boutique_data->user->package->getTitle():'',
                    "package_start_date" => ($boutique_data->user->package_id) ? (string) getDbDateFormat($boutique_data->user->package_start_date) : '',
                    "package_end_date" => ($boutique_data->user->package_id) ? (string) getDbDateFormat($boutique_data->user->package_end_date) : '',
                    "is_live" =>  (string) $boutique_data->getIsLiveStatus(),
                    "is_featured" => (string) $boutique_data->is_featured,
                    "thumb_image" => (string) $boutique_data->thumb_image,
                    "original_image" => (string) $boutique_data->original_image,
                    "cover_image_thumb" => (string) $boutique_data->cover_image_thumb,
                    "cover_image_original" => (string) $boutique_data->cover_image_original,
                    "sale_products" => [
                        "all_products" => $all_products_for_sale,
                        "most_selling_products" => $most_selling_sale_products,
                        "new_products" => $new_sale_products,
                    ],
                    "auction_products" => [
                        "all_products" => $all_products_for_auction,
                        "most_selling_products" => $most_selling_auction_products,
                        "new_products" => $new_auction_products,
                    ],
                'cart_count' => (string) getCartCount()
                ];
            $view_count = (UserBoutique::findOrFail($boutique_data->id)->views_count) ? UserBoutique::findOrFail($boutique_data->id)->views_count : 0;
            UserBoutique::where('id', $boutique_data->id)->update(['views_count' => $view_count + 1]);
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_boutique_success',
                    'data' => $boutique_data_array,
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

    public function userFollowBoutique(Request $request)
    {
        try
        {
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $validator = Validator::make($request->all(), [
                'boutique_id' => 'required|numeric|min:0',
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
            $user_boutique = UserBoutique::where('id', $request->boutique_id)->firstOrFail();
            $follow_un_follow = DB::table('boutique_followers')->where('user_id', $user_id)->where('user_boutique_id', $request->boutique_id)->first();
            if($follow_un_follow)
            {
                DB::table('boutique_followers')->where('user_id', $user_id)->where('user_boutique_id', $request->boutique_id)->delete();
                $message = __('messages.boutique_successfully_un_followed');
                $push_title_en = "User Followed Your Boutique";
                $push_message_en = "User Followed Your Boutique";
                $push_title_ar = "User Followed Your Boutique";
                $push_message_ar = "User Followed Your Boutique";
            }
            else
            {
                DB::table('boutique_followers')->insert([
                    'user_id' => $user_id,
                    'user_boutique_id' => $user_boutique->id,
                    'created_at' => $current_date_time,
                    'updated_at' => $current_date_time,
                ]);
                $message = __('messages.boutique_successfully_followed');
                $message = __('messages.boutique_successfully_un_followed');
                $push_title_en = "User Un Followed Your Boutique";
                $push_message_en = "User Un Followed Your Boutique";
                $push_title_ar = "User Un Followed Your Boutique";
                $push_message_ar = "User Un Followed Your Boutique";
            }
            $user_boutique = UserBoutique::where('id', $request->boutique_id)->firstOrFail();
            $push_target = "Vendor";
            $user_ids = User::where('id', $user_boutique->user_id)->pluck('id')->toArray();
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
                    'message' => $message,
                    'message_code' => 'user_follow_un_follow_success',
                    'data' => [
                        "followers_count" => (string) $user_boutique->getFollowersCount(),
                        "follow_status" => (string) $user_boutique->getFollowStatus(),
                    ],


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
}

<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeSet;
use App\Models\AttributeValue;
use App\Models\BiddingHistory;
use App\Models\BiddingSummary;
use App\Models\BoutiqueCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\SpecialPrice;
use App\Models\Stock;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UserProductController extends Controller
{
    public function getUserProducts(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $post_requests = $request->post();
            $get_requests = $request->query();

            $category_ids = [];
            if(isset($post_requests['category_id']) && $post_requests['category_id'])
            {
                $category_ids = explode(",", $post_requests['category_id']);
            }
            if(isset($get_requests['category_id']) && $get_requests['category_id'])
            {
               $get_category_ids = explode( ",", $get_requests['category_id']);
                $category_ids = array_merge($category_ids, $get_category_ids);
            }
            $category_ids = array_unique($category_ids);
            $boutique_category_ids = [];
            if(isset($post_requests['boutique_category_id']) && $post_requests['boutique_category_id'])
            {
                $boutique_category_ids = explode(",", $post_requests['boutique_category_id']);
            }
            if(isset($get_requests['boutique_category_id']) && $get_requests['boutique_category_id'])
            {
                $get_boutique_category_ids = explode( ",", $get_requests['boutique_category_id']);
                $boutique_category_ids = array_merge($boutique_category_ids, $get_boutique_category_ids);
            }
            $boutique_category_ids = array_unique($boutique_category_ids);
            $boutique_ids = [];
            if(isset($post_requests['boutique_id']) && $post_requests['boutique_id'])
            {
                $boutique_ids = explode(",", $post_requests['boutique_id']);
            }
            if(isset($get_requests['boutique_id']) && $get_requests['boutique_id'])
            {
                $get_boutique_ids = explode( ",", $get_requests['boutique_id']);
                $boutique_ids = array_merge($boutique_ids, $get_boutique_ids);
            }
            $boutique_ids = array_unique($boutique_ids);
            $selected_boutique_ids = DB::table('user_boutique_categories')->whereIn('boutique_category_id', $boutique_category_ids)->distinct()->pluck('user_boutique_id')->toArray();
            $boutique_ids = array_merge($selected_boutique_ids, $boutique_ids);
            $boutique_ids = array_unique($boutique_ids);


            $brand_ids = [];
            if(isset($post_requests['brand_id']) && $post_requests['brand_id'])
            {
                $brand_ids = explode(",", $post_requests['brand_id']);
            }
            if(isset($get_requests['brand_id']) && $get_requests['brand_id'])
            {
                $get_brand_ids = explode( ",", $get_requests['brand_id']);
                $brand_ids = array_merge($brand_ids, $get_brand_ids);
            }
            $brand_ids = array_unique($brand_ids);

            $vendor_ids = [];
            if(isset($post_requests['vendor_id']) && $post_requests['vendor_id'])
            {
                $vendor_ids = explode(",", $post_requests['vendor_id']);
            }
            if(isset($get_requests['vendor_id']) && $get_requests['vendor_id'])
            {
                $get_vendor_ids = explode( ",", $get_requests['vendor_id']);
                $vendor_ids = array_merge($vendor_ids, $get_vendor_ids);
            }
            $vendor_ids = array_unique($vendor_ids);

            $sort_by = 'id';
            if(isset($get_requests['sort_by']) && $get_requests['sort_by'])
            {
                $sort_by = $get_requests['sort_by'];
            }
            if(isset($post_requests['sort_by']) && $post_requests['sort_by'])
            {
                $sort_by = $post_requests['sort_by'];
            }

            $is_best_selling = 0;
            if(isset($get_requests['is_best_selling']) && $get_requests['is_best_selling'])
            {
                $is_best_selling = $get_requests['is_best_selling'];
            }
            if(isset($post_requests['is_best_selling']) && $post_requests['is_best_selling'])
            {
                $is_best_selling = $post_requests['is_best_selling'];
            }

            $show_all = 0;
            if(isset($get_requests['show_all']) && $get_requests['show_all'])
            {
                $show_all = 1;
            }


            $is_new_arrival = 0;
            if(isset($get_requests['is_new_arrival']) && $get_requests['is_new_arrival'])
            {
                $is_new_arrival = $get_requests['is_new_arrival'];
            }
            if(isset($post_requests['is_new_arrival']) && $post_requests['is_new_arrival'])
            {
                $is_new_arrival = $post_requests['is_new_arrival'];
            }

            $show_filters = 0;
            if(isset($get_requests['show_filters']) && $get_requests['show_filters'])
            {
                $show_filters = $get_requests['show_filters'];
            }
            if(isset($post_requests['show_filters']) && $post_requests['show_filters'])
            {
                $show_filters = $post_requests['show_filters'];
            }

            $is_feature = 0;
            if(isset($get_requests['is_feature']) && $get_requests['is_feature'])
            {
                $is_feature = $get_requests['is_feature'];
            }
            if(isset($post_requests['is_feature']) && $post_requests['is_feature'])
            {
                $is_feature = $post_requests['is_feature'];
            }

            $is_discounted = 0;
            if(isset($get_requests['is_discounted']) && $get_requests['is_discounted'])
            {
                $is_discounted = $get_requests['is_discounted'];
            }
            if(isset($post_requests['is_discounted']) && $post_requests['is_discounted'])
            {
                $is_discounted = $post_requests['is_discounted'];
            }

            $is_deal = 0;
            if(isset($get_requests['is_deal']) && $get_requests['is_deal'])
            {
                $is_deal = $get_requests['is_deal'];
            }
            if(isset($post_requests['is_deal']) && $post_requests['is_deal'])
            {
                $is_deal = $post_requests['is_deal'];
            }

            $in_stock = 0;
            if(isset($get_requests['in_stock']) && $get_requests['in_stock'])
            {
                $in_stock = $get_requests['in_stock'];
            }
            if(isset($post_requests['in_stock']) && $post_requests['in_stock'])
            {
                $in_stock = $post_requests['in_stock'];
            }

            $search_key = null;
            if(isset($get_requests['q']) && $get_requests['q'])
            {
                $search_key = $get_requests['q'];
            }
            if(isset($post_requests['q']) && $post_requests['q'])
            {
                $search_key = $post_requests['q'];
            }

            $in_stock_product_variant_ids = [];
            $in_stock_product_ids = [];
            $in_stock_product_sale_ids = [];
            if($in_stock)
            {
                $in_stock_product_variant_ids = Stock::where('quantity', '>', 0)->distinct()->pluck('product_variant_id')->toArray();
                $in_stock_product_ids = ProductVariant::whereIn('id', $in_stock_product_variant_ids)->distinct()->pluck('product_id')->toArray();
            }

            $is_deal_product_variant_ids = [];
            $is_deal_product_ids = [];
            $is_deal_product_sale_ids = [];
            if($is_deal)
            {
                $is_deal_product_variant_ids = SpecialPrice::where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time)->distinct()->pluck('product_variant_id')->toArray();
                $is_deal_product_ids = ProductVariant::whereIn('id', $is_deal_product_variant_ids)->distinct()->pluck('product_id')->toArray();
            }

            $is_discounted_variant_ids = [];
            $is_discounted_product_ids = [];

            $special_variant_ids = SpecialPrice::where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time)->distinct()->pluck('product_variant_id')->toArray();
            $is_discounted_variant_ids = ProductVariant::whereRaw('final_price < regular_price')->distinct()->pluck('id')->toArray();
            $is_discounted_variant_ids = array_merge($is_discounted_variant_ids, $special_variant_ids);
            $is_discounted_product_ids = ProductVariant::whereIn('id', $is_discounted_variant_ids)->distinct()->pluck('product_id')->toArray();
            $is_discounted_product_ids = Product::whereIn('id', $is_discounted_product_ids)->whereIn('sale_type', ['Sale', 'Bulk'])->distinct()->pluck('id')->toArray();

            $sale_type_status = 0;
            if(isset($get_requests['sale_type']) && $get_requests['sale_type'])
            {
                $sale_type = $get_requests['sale_type'];
                $sale_type_status = 1;
            }
            if(isset($post_requests['sale_type']) && $post_requests['sale_type'])
            {
                $sale_type= $post_requests['sale_type'];
                $sale_type_status = 1;
            }

            $price_range_status = 0;
            if(isset($get_requests['price_range']) && $get_requests['price_range'])
            {
                $price_range = $get_requests['price_range'];
                $price_range_status = 1;
            }
            if(isset($post_requests['price_range']) && $post_requests['price_range'])
            {
                $price_range = $post_requests['price_range'];
                $price_range_status = 1;
            }

            if($price_range_status)
            {
                $price_range_array = explode('-', $price_range);
                if(count($price_range_array) != 2)
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.something_went_wrong'),
                            'message_code' => 'price_range_not_proper',
                        ], 200);
                }
                $min_price_range = $price_range_array[0];
                $max_price_range = $price_range_array[1];
            }

            $attribute_id_param = 0;
            if(isset($get_requests['attribute_id']) && $get_requests['attribute_id'])
            {
                $attribute_id_param = $get_requests['attribute_id'];
            }
            if(isset($post_requests['attribute_id']) && $post_requests['attribute_id'])
            {
                $attribute_id_param = $post_requests['attribute_id'];
            }

            $attribute_value_param = 0;
            if(isset($get_requests['attribute_value']) && $get_requests['attribute_value'])
            {
                $attribute_value_param = $get_requests['attribute_value'];
            }
            if(isset($post_requests['attribute_value']) && $post_requests['attribute_value'])
            {
                $attribute_value_param = $post_requests['attribute_value'];
            }

            if($attribute_value_param)
            {
                $attribute_id_array = explode(',', $attribute_id_param);
                $attribute_value_array = explode(',', $attribute_value_param);
                if(count($attribute_id_array) != count($attribute_value_array))
                {
                    return response()->json(
                        [
                            'success' => false,
                            'status' => 400,
                            'message' => __('messages.something_went_wrong'),
                            'message_code' => 'attribute_id_value_not_match',
                        ], 200);
                }
                $attribute_value_product_auction_sale_ids = [];
                $attribute_value_product_ids = [];
                foreach ($attribute_id_array as $key_01 => $attribute_id_array_item)
                {
                    $attribute_id_value = explode('||', $attribute_value_array[$key_01]);
                    foreach ($attribute_id_value as $key_02 => $attribute_id_value_item)
                    {
                        $attribute_product_id_array = DB::table('product_attributes')->where('attribute_id', $attribute_id_array_item)->where('attribute_value_id', $attribute_id_value_item)->distinct()->pluck('product_id')->toArray();
                        $attribute_value_product_ids = array_merge($attribute_value_product_ids, $attribute_product_id_array);
                    }
                    $attribute_value_product_ids = array_unique($attribute_value_product_ids);
                    $attribute_value_product_auction_sale_ids_temp = ProductForAuctionOrSale::whereIn('product_id', $attribute_value_product_ids)->pluck('id')->toArray();
                    $attribute_value_product_auction_sale_ids = array_merge($attribute_value_product_auction_sale_ids, $attribute_value_product_auction_sale_ids_temp);
                }
                $attribute_value_product_auction_sale_ids = array_unique($attribute_value_product_auction_sale_ids);
            }
            $filter_product_ids = [];
            $filter_product_variant_ids = [];
            $filter_product_sales_ids = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as title";
                $name_array['description'] = "description_ar as description";
                $name_array['search_name'] = "name_ar";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
                $name_array['search_name'] = "name";
            }

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);

            $products_for_sale_query = ProductForAuctionOrSale::query();
            $products_for_sale_query->where('status', 1);
            $products_for_sale_query->where('type', 'Sale');
            if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_query->where('type', 'NoType');
            $products_for_sale_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_sale_query->with(['product' => function ($query) use($name_array, $in_stock_product_variant_ids, $is_discounted_variant_ids, $in_stock, $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                if($in_stock || $is_discounted || $is_deal)
                {
                    $query->with([
                        'product_inventory' => function($q) use ($is_discounted_variant_ids, $in_stock_product_variant_ids, $in_stock,  $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                           if($in_stock)$q->whereIn('id', $in_stock_product_variant_ids);
                           if($is_discounted)$q->whereIn('id', $is_discounted_variant_ids);
                           if($is_deal)$q->whereIn('id', $is_deal_product_variant_ids);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }
                else
                {
                    $query->with('product_inventory.default_image', 'product_inventory.default_video');
                }

            }]);
            if(!$show_all)
            {
                $products_for_sale_query->whereHas('product', function ($query_10) {
                    $query_10->where("products.status", 1);
                });
            }

            if($search_key)
            {
                $products_for_sale_query->whereHas('product', function ($query_103) use ($search_key, $name_array) {
                    $query_103->where('products.'.$name_array['search_name'], 'LIKE', '%'.$search_key.'%');
                });
            }

            if($in_stock)
            {
                $products_for_sale_query->whereHas('product', function ($query_10) use ($in_stock_product_ids) {
                    $query_10->whereIn("products.id", $in_stock_product_ids);
                });
            }
            if($is_discounted)
            {
                $products_for_sale_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereIn("products.id", $is_discounted_product_ids);
                    if(!in_array('can-purchase-discounted', getUserPermissions())) $query_10->where('products.sale_type', 'NoType');
                });
            }
            if(!in_array('can-purchase-discounted', getUserPermissions()))
            {
                $products_for_sale_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereNotIn("products.id", $is_discounted_product_ids);
                });
            }

            if($is_deal)
            {
                $products_for_sale_query->whereHas('product', function ($query_10) use ($is_deal_product_ids) {
                    $query_10->whereIn("products.id", $is_deal_product_ids);
                });
            }
            $products_for_sale_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            if(!empty($category_ids))
            {
                $products_for_sale_query->whereHas('product.categories', function ($query_1) use ($category_ids) {
                    $query_1->whereIn("categories.id", $category_ids);
                });
            }
            if(!empty($vendor_ids))
            {
                $products_for_sale_query->whereHas('product.user', function ($query_2) use ($vendor_ids) {
                    $query_2->whereIn("users.id", $vendor_ids);
                });
            }
            if(!empty($boutique_ids))
            {
                $products_for_sale_query->whereHas('product.boutique', function ($query_3) use ($boutique_ids) {
                    $query_3->whereIn("user_boutiques.id", $boutique_ids);
                });
            }

            if(!empty($brand_ids))
            {
                $products_for_sale_query->whereHas('product.brand', function ($query_3) use ($brand_ids) {
                    $query_3->whereIn("brands.id", $brand_ids);
                });
            }

            if($is_feature)
            {
                $products_for_sale_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.is_featured", 1);
                });
            }

            if($is_best_selling)
            {
                $products_for_sale_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.most_selling_status", 1);
                });
            }

            if($is_best_selling)
            {
                $products_for_sale_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.most_selling_status", 1);
                });
            }

            if($is_new_arrival)
            {
                $products_for_sale_query->whereHas('product', function ($query_2) use($current_date_time) {
                    $query_2->where(function($query_4) use ($current_date_time){
                        $query_4->where(function($query_5) use ($current_date_time){
                            $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                        });
                        // $query_4->orWhereNull('new_arrival_start_time');
                    });;
                });
            }

            if($price_range_status)
            {
                $products_for_sale_query->whereHas('product.product_inventory', function ($query_2) use($min_price_range, $max_price_range) {
                    $query_2->where("product_variants.final_price", ">=", $min_price_range)->where("product_variants.final_price", "<=", $max_price_range);
                });
            }

            if($attribute_value_param)
            {
                $products_for_sale_query->whereIn('id', $attribute_value_product_auction_sale_ids);
            }


            $products_for_bulk_query = ProductForAuctionOrSale::query();
            $products_for_bulk_query->where('status', 1);
            $products_for_bulk_query->where('type', 'Bulk');
            if(!in_array('can-purchase-bulk', getUserPermissions())) $products_for_bulk_query->where('type', 'NoType');
            $products_for_bulk_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_bulk_query->with(['product' => function ($query) use($name_array, $in_stock_product_variant_ids, $is_discounted_variant_ids, $in_stock, $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                if($in_stock || $is_discounted || $is_deal)
                {
                    $query->with([
                        'product_inventory' => function($q) use ($is_discounted_variant_ids, $in_stock_product_variant_ids, $in_stock,  $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                            if($in_stock)$q->whereIn('id', $in_stock_product_variant_ids);
                            if($is_discounted)$q->whereIn('id', $is_discounted_variant_ids);
                            if($is_deal)$q->whereIn('id', $is_deal_product_variant_ids);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }
                else
                {
                    $query->with('product_inventory.default_image', 'product_inventory.default_video');
                }

            }]);
            if(!$show_all)
            {
                $products_for_bulk_query->whereHas('product', function ($query_10) {
                    $query_10->where("products.status", 1);
                });
            }
            if($search_key)
            {
                $products_for_bulk_query->whereHas('product', function ($query_103) use ($search_key, $name_array) {
                    $query_103->where('products.'.$name_array['search_name'], 'LIKE', '%'.$search_key.'%');
                });
            }

            if($in_stock)
            {
                $products_for_bulk_query->whereHas('product', function ($query_10) use ($in_stock_product_ids) {
                    $query_10->whereIn("products.id", $in_stock_product_ids);
                });
            }
            if($is_discounted)
            {
                $products_for_bulk_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereIn("products.id", $is_discounted_product_ids);
                    if(!in_array('can-purchase-discounted', getUserPermissions())) $query_10->where('products.sale_type', 'NoType');
                });
            }
            if(!in_array('can-purchase-discounted', getUserPermissions()))
            {
                $products_for_bulk_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereNotIn("products.id", $is_discounted_product_ids);
                });
            }
            if($is_deal)
            {
                $products_for_bulk_query->whereHas('product', function ($query_10) use ($is_deal_product_ids) {
                    $query_10->whereIn("products.id", $is_deal_product_ids);
                });
            }
            $products_for_bulk_query->addSelect(DB::raw('(SELECT final_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            if(!empty($category_ids))
            {
                $products_for_bulk_query->whereHas('product.categories', function ($query_1) use ($category_ids) {
                    $query_1->whereIn("categories.id", $category_ids);
                });
            }
            if(!empty($vendor_ids))
            {
                $products_for_bulk_query->whereHas('product.user', function ($query_2) use ($vendor_ids) {
                    $query_2->whereIn("users.id", $vendor_ids);
                });
            }
            if(!empty($boutique_ids))
            {
                $products_for_bulk_query->whereHas('product.boutique', function ($query_3) use ($boutique_ids) {
                    $query_3->whereIn("user_boutiques.id", $boutique_ids);
                });
            }

            if(!empty($brand_ids))
            {
                $products_for_bulk_query->whereHas('product.brand', function ($query_3) use ($brand_ids) {
                    $query_3->whereIn("brands.id", $brand_ids);
                });
            }

            if($is_feature)
            {
                $products_for_bulk_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.is_featured", 1);
                });
            }

            if($is_best_selling)
            {
                $products_for_bulk_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.most_selling_status", 1);
                });
            }

            if($is_best_selling)
            {
                $products_for_bulk_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.most_selling_status", 1);
                });
            }

            if($is_new_arrival)
            {
                $products_for_bulk_query->whereHas('product', function ($query_2) use($current_date_time) {
                    $query_2->where(function($query_4) use ($current_date_time){
                        $query_4->where(function($query_5) use ($current_date_time){
                            $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                        });
                        // $query_4->orWhereNull('new_arrival_start_time');
                    });;
                });
            }

            if($price_range_status)
            {
                $products_for_bulk_query->whereHas('product.product_inventory', function ($query_2) use($min_price_range, $max_price_range) {
                    $query_2->where("product_variants.final_price", ">=", $min_price_range)->where("product_variants.final_price", "<=", $max_price_range);
                });
            }

            if($attribute_value_param)
            {
                $products_for_bulk_query->whereIn('id', $attribute_value_product_auction_sale_ids);
            }

            $products_for_auction_query = ProductForAuctionOrSale::query();
            $products_for_auction_query->where('status', 1);
            $products_for_auction_query->where('type', 'Auction');
            if(!in_array('can-purchase-bulk', getUserPermissions())) $products_for_auction_query->where('type', 'NoType');
            $products_for_auction_query->where('bid_purchase_status', 0);
            $products_for_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time', 'created_at as created_at_sort', 'sales_count');
            $products_for_auction_query->with(['product' => function ($query) use($name_array, $in_stock_product_variant_ids, $is_discounted_variant_ids, $in_stock, $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                $query->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                if($in_stock || $is_discounted || $is_deal)
                {
                    $query->with([
                        'product_inventory' => function($q) use ($is_discounted_variant_ids, $in_stock_product_variant_ids, $in_stock,  $is_discounted, $is_deal, $is_deal_product_variant_ids ) {
                            if($in_stock)$q->whereIn('id', $in_stock_product_variant_ids);
                            if($is_discounted)$q->whereIn('id', $is_discounted_variant_ids);
                            if($is_deal)$q->whereIn('id', $is_deal_product_variant_ids);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }
                else
                {
                    $query->with('product_inventory.default_image', 'product_inventory.default_video');
                }

            }]);
            if(!$show_all)
            {
                $products_for_auction_query->whereHas('product', function ($query_10) {
                    $query_10->where("products.status", 1);
                });
            }
            if($search_key)
            {
                $products_for_auction_query->whereHas('product', function ($query_103) use ($search_key, $name_array) {
                    $query_103->where('products.'.$name_array['search_name'], 'LIKE', '%'.$search_key.'%');
                });
            }
            if($in_stock)
            {
                $products_for_auction_query->whereHas('product', function ($query_10) use ($in_stock_product_ids) {
                    $query_10->whereIn("products.id", $in_stock_product_ids);
                });
            }
            if($is_discounted)
            {
                $products_for_auction_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereIn("products.id", $is_discounted_product_ids);
                    if(!in_array('can-purchase-discounted', getUserPermissions())) $query_10->where('products.sale_type', 'NoType');
                });
            }
            if(!in_array('can-purchase-discounted', getUserPermissions()))
            {
                $products_for_auction_query->whereHas('product', function ($query_10) use ($is_discounted_product_ids) {
                    $query_10->whereNotIn("products.id", $is_discounted_product_ids);
                });
            }
            if($is_deal)
            {
                $products_for_auction_query->whereHas('product', function ($query_10) use ($is_deal_product_ids) {
                    $query_10->whereIn("products.id", $is_deal_product_ids);
                });
            }
            $products_for_auction_query->addSelect(DB::raw('(SELECT bid_start_price from product_variants WHERE product_id = product_for_auction_or_sales.product_id AND status = 1 ORDER BY id ASC LIMIT 1) as price_for_sort'));
            $products_for_auction_query->where(function ($query_6) use($current_date_time) {
                $query_6->where('bid_end_time', '>', $current_date_time);
            });
            if(!empty($category_ids))
            {
                $products_for_auction_query->whereHas('product.categories', function ($query_1) use ($category_ids) {
                    $query_1->whereIn("categories.id", $category_ids);
                });
            }
            if(!empty($vendor_ids))
            {
                $products_for_auction_query->whereHas('product.user', function ($query_2) use ($vendor_ids) {
                    $query_2->whereIn("users.id", $vendor_ids);
                });
            }
            if(!empty($boutique_ids))
            {
                $products_for_auction_query->whereHas('product.boutique', function ($query_3) use ($boutique_ids) {
                    $query_3->whereIn("user_boutiques.id", $boutique_ids);
                });
            }

            if(!empty($brand_ids))
            {
                $products_for_auction_query->whereHas('product.brand', function ($query_3) use ($brand_ids) {
                    $query_3->whereIn("brands.id", $brand_ids);
                });
            }

            if($is_feature)
            {
                $products_for_auction_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.is_featured", 1);
                });
            }

            if($is_best_selling)
            {
                $products_for_auction_query->whereHas('product', function ($query_3) {
                    $query_3->where("products.most_selling_status", 1);
                });
            }

            if($is_new_arrival)
            {
                $products_for_auction_query->whereHas('product', function ($query_2) use($current_date_time) {
                    $query_2->where(function($query_4) use ($current_date_time){
                        $query_4->where(function($query_5) use ($current_date_time){
                            $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                        });
                        // $query_4->orWhereNull('new_arrival_start_time');
                    });;
                });
            }

            if($price_range_status)
            {
                $products_for_auction_query->whereHas('product.product_inventory', function ($query_2) use($min_price_range, $max_price_range) {
                    $query_2->where("product_variants.bid_start_price", ">=", $min_price_range)->where("product_variants.bid_start_price", "<=", $max_price_range);
                });
            }

            if($attribute_value_param)
            {
                $products_for_auction_query->whereIn('id', $attribute_value_product_auction_sale_ids);
            }

            if(!$sale_type_status)
            {
                // no sale type selected : that means All products should be shown
                if($is_discounted || $is_deal)
                {
                $final_products_query = $products_for_sale_query->union($products_for_bulk_query);
                }
                else
                {
                $final_products_query_1 = $products_for_auction_query->union($products_for_sale_query);
                $final_products_query = $final_products_query_1->union($products_for_bulk_query);
                }
            }
            else
            {
                if($sale_type == 'Sale')
                {
                    $final_products_query = $products_for_sale_query;
                }
                elseif($sale_type == 'Auction')
                {
                    $final_products_query = $products_for_auction_query;
                }
                elseif($sale_type == 'Bulk')
                {
                    $final_products_query = $products_for_bulk_query;
                }
                else
                {
                    if($is_discounted || $is_deal)
                    {
                        $final_products_query = $products_for_sale_query->union($products_for_bulk_query);
                    }
                    else
                    {
                        $final_products_query_1 = $products_for_auction_query->union($products_for_sale_query);
                        $final_products_query = $final_products_query_1->union($products_for_bulk_query);
                    }
                }
            }




            if($sort_by == 'date')
            {
                $final_products_query->orderBy('created_at_sort', 'DESC');
            }
            elseif($sort_by == 'price_high_to_low')
            {
                $final_products_query->orderBy("price_for_sort", "DESC");
            }
            elseif($sort_by == 'price_low_to_high')
            {
                $final_products_query->orderBy("price_for_sort", "ASC");
            }
            elseif($sort_by == 'relevance')
            {
                $final_products_query->orderBy("sales_count", "DESC");
            }
            elseif($sort_by == 'distance')
            {

            }
            else
            {
                $final_products_query->orderBy('created_at_sort', 'DESC');
            }

            $products_for_auction_or_sale_count_query = clone $final_products_query;
            $products_for_auction_or_sale_query = clone $final_products_query;
            $product_variant_query = clone $final_products_query;
            $product_query = clone $final_products_query;
            $products_for_auction_or_sale_count = $products_for_auction_or_sale_count_query->count();
            $products_for_auction_or_sale_ids = $products_for_auction_or_sale_query->pluck('id')->toArray();

           if($show_filters)
           {
               $product_ids = $product_query->pluck('product_id')->toArray();
               $product_variant_ids = ProductVariant::whereIn('product_id', $product_ids)->pluck('id')->toArray();
               $product_category_ids = DB::table('product_categories')->whereIn('product_id', $product_ids)->distinct()->pluck('category_id')->toArray();
               $product_attribute_ids = DB::table('product_attributes')->whereIn('product_id', $product_ids)->distinct()->pluck('attribute_id')->toArray();
               $user_boutique_ids = Product::whereIn('id', $product_ids)->distinct()->pluck('user_boutique_id')->toArray();
               $boutique_category_ids =  DB::table('user_boutique_categories')->whereIn('user_boutique_id', $user_boutique_ids)->distinct()->pluck('boutique_category_id')->toArray();
               $product_brand_ids =  Product::whereIn('id', $product_ids)->distinct()->pluck('brand_id')->toArray();

               $filter_categories = Category::whereIn('id', $product_category_ids)->select('id', $name_array['name'], 'parent_id')->orderBy('sort_order')->get();
               $filter_brands = Brand::whereIn('id', $product_brand_ids)->select('id', $name_array['title'])->orderBy('sort')->get();
               $filter_boutique_categories = BoutiqueCategory::whereIn('id', $boutique_category_ids)->select('id', $name_array['title'])->get();
               $filter_attributes = Attribute::whereIn('id', $product_attribute_ids)->select('id', $name_array['name'], 'colour_palette')->with('attribute_values')->get();
               $filter_boutiques = UserBoutique::whereIn('id', $user_boutique_ids)->select('id', $name_array['name'])->get();

               $filter_categories_array = [];
               foreach ($filter_categories as $filter_category)
               {
                   $temp_category = [
                       "id" => $filter_category->id,
                       "name" => (string) $filter_category->name,
                       "parent_id" => (string) $filter_category->parent_id,
                   ];

                   array_push($filter_categories_array, $temp_category);
               }

               $filter_bands_array = [];
               foreach ($filter_brands as $filter_brand)
               {
                   $temp_brand = [
                       "id" => $filter_brand->id,
                       "name" => (string) $filter_brand->title,
                   ];

                   array_push($filter_bands_array, $temp_brand);
               }

               $filter_boutique_categories_array = [];
               foreach ($filter_boutique_categories as $filter_boutique_category)
               {
                   $temp_boutique_category = [
                       "id" => $filter_boutique_category->id,
                       "name" => (string) $filter_boutique_category->title,
                   ];

                   array_push($filter_boutique_categories_array, $temp_boutique_category);
               }

               $filter_boutiques_array = [];
               foreach ($filter_boutiques as $filter_boutique)
               {
                   $temp_boutique = [
                       "id" => $filter_boutique->id,
                       "name" => (string) $filter_boutique->name,
                   ];

                   array_push($filter_boutiques_array, $temp_boutique);
               }

               $max_final_price = ProductVariant::whereIn('id', $product_variant_ids)->max('final_price');
               $max_bid_start_price = ProductVariant::whereIn('id', $product_variant_ids)->max('bid_start_price');
               $max_price = $max_final_price;
               if($max_final_price < $max_bid_start_price)
               {
                   $max_price = $max_bid_start_price;
               }

               $filter_attributes_array = [];
               foreach ($filter_attributes as $filter_attribute)
               {
                   $filter_attribute_values_array = [];
                   foreach ($filter_attribute->attribute_values as $filter_attribute_value)
                   {
                       $product_ids_for_count = DB::table('product_attributes')->where('attribute_id', $filter_attribute->id)->where('attribute_value_id', $filter_attribute_value->id)->distinct()->pluck('product_id')->toArray();
                       $attribute_value_product_count = ProductForAuctionOrSale::whereIn('product_id', $product_ids_for_count)->whereIn('id', $products_for_auction_or_sale_ids)->count();
                       $colour = "";
                       if($filter_attribute->colour_palette)
                       {
                           $colour = $filter_attribute_value->colour;
                       }
                       $attribute_value_temp = [
                           "id" => $filter_attribute_value->id,
                           "name" => (string) $filter_attribute_value->name,
                           "colour" => (string) $colour,
                           "product_count" => (string) $attribute_value_product_count,
                       ];
                       array_push($filter_attribute_values_array, $attribute_value_temp);

                   }
                   $attribute_temp = [
                       "id" => $filter_attribute->id,
                       "name" => (string) $filter_attribute->name,
                       "attribute_values" =>  $filter_attribute_values_array,
                   ];
                   array_push($filter_attributes_array, $attribute_temp);
               }
           }
           else
           {
               $filter_categories_array = [];
               $filter_bands_array = [];
               $filter_boutiques_array = [];
               $filter_boutique_categories_array = [];
               $filter_attributes_array = [];
               $max_price = 0;
           }


            if(empty($request->get('page'))) $per_page = $products_for_auction_or_sale_count;
            $products_for_auction_or_sale_data = $final_products_query->paginate($per_page);
            $product_data = productData($products_for_auction_or_sale_data->items(), $time_zone); // defined in helpers
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_products_success',
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
                        "products" => $product_data,
                        "categories" => $filter_categories_array,
                        "brands" => $filter_bands_array,
                        "boutiques" => $filter_boutiques_array,
                        "boutique_categories" => $filter_boutique_categories_array,
                        "attributes" => $filter_attributes_array,
                        "min_price" => (string) 0,
                        "max_price" => (string) round($max_price,-3),
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

    public function getUserProductDetails(Request $request)
    {
        try
        {
            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $sale_or_auction_id = 0;
            $product_variant_id = 0;
            if(isset($request->id) && $request->id) $sale_or_auction_id = $request->id;
            if(isset($request->product_for_auction_or_sale_id) && $request->product_for_auction_or_sale_id) $sale_or_auction_id = $request->product_for_auction_or_sale_id;
            if(isset($request->product_variant_id) && $request->product_variant_id) $product_variant_id = $request->product_variant_id;
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['description'] = "description_ar as description";
                $name_array['offer_text'] = "offer_text_ar as offer_text";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['description'] = "description";
                $name_array['offer_text'] = "offer_text";
            }

            $products_for_sale_query = ProductForAuctionOrSale::query();
            $products_for_sale_query->where('status', 1);
            $products_for_sale_query->where('id', $sale_or_auction_id);
            $products_for_sale_query->select('id', 'product_id', 'user_id', 'type', 'user_view_count', 'bid_start_time', 'bid_end_time');
            $products_for_sale_query->with(['product' => function ($query) use($name_array, $product_variant_id) {
                $query->select('id', $name_array['name'], $name_array['description'], 'attribute_set_id', 'sale_type', 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
                if($product_variant_id)
                {
                    $query->with([
                        'product_inventory' => function($q) use ($product_variant_id) {
                            $q->where('id','=',$product_variant_id);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }
                else
                {
                    $query->with('product_inventory.default_image', 'product_inventory.default_video', 'product_inventory.images');
                }
            }]);
             $products_for_sale_data = $products_for_sale_query->first();

            $product_id = $products_for_sale_data->product->id;
            $product_variant_id = $products_for_sale_data->product->product_inventory->id;
            $attribute_sets = AttributeSet::where('id', $products_for_sale_data->product->attribute_set_id)->first();
            $attribute_set_attributes = $attribute_sets->attributes()->select('attributes.id', 'code', 'colour_palette', $name_array['name'])->get();
            $attribute_data = [];
            $product_temp = Product::where('id', $product_id)->firstOrFail();
            foreach ($attribute_set_attributes as $key => $attribute_set_attribute)
            {
                $product_attribute_values = DB::table('product_attributes')->where('product_id', $product_id)->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id')->get();
                $attribute_data[$key]['id'] = $attribute_set_attribute->id;
                $attribute_data[$key]['name'] = $attribute_set_attribute->name;
                $attribute_data[$key]['code'] = $attribute_set_attribute->code;
                if($attribute_set_attribute->colour_palette) $attribute_data[$key]['colour_palette'] = true; else $attribute_data[$key]['colour_palette'] = false;
                $attribute_value_data = [];
                foreach ($product_attribute_values as $key2 => $product_attribute_value)
                {
                    $attribute_value =  AttributeValue::select('id', $name_array['name'], 'colour')->where('id', $product_attribute_value->attribute_value_id)->where('attribute_id', $attribute_set_attribute->id)->first();
                    if($attribute_value)
                    {
                        if($product_variant_id == $product_attribute_value->product_variant_id) $active = 1; else $active = 0;
                        if($attribute_set_attribute->colour_palette) $colour = $attribute_value->colour; else $colour = null;
                        $stock_checking_product_variant = ProductVariant::where('id', $product_attribute_value->product_variant_id)->first();
                        if($stock_checking_product_variant->availableStockQuantity() <= 0) $stock_available = 0; else $stock_available = 1;
                        $temp_data_2 = [
                            'id' => $attribute_value->id,
                            'name' => (string) $attribute_value->name,
                            'active' => (string) $active,
                            'stock_available' => (string) $stock_available,
                            'colour' => (string) $colour,
                        ];
                        array_push($attribute_value_data, $temp_data_2);
                    }
                }
                $attribute_value_data =  unique_attribute_values($attribute_value_data, $product_temp->type);
                $attribute_data[$key]['attribute_values'] = $attribute_value_data;
            }
            $products_for_sale_data['product']['product_inventory']['attribute_sets'] = $attribute_data;


            if($product_temp->type == "Simple")
            {
                $product_variant_items = $product_temp->product_variants()->select('product_id', 'id')->limit(1)->orderBy('id', 'ASC')->get();
            }
            else
            {
                $product_variant_items = $product_temp->product_variants()->select('product_id', 'id')->get();
            }

            $product_variant_array = [];
            $is_available_for_detail = 1;
            foreach ($product_variant_items as $product_variant_item)
            {
                $product_inventory = $products_for_sale_data->product->product_inventory($product_variant_item->id)->first();
                if($product_inventory->availableStockQuantity() <= 0) $is_available_for_detail = 0;
                if($products_for_sale_data->product->sale_type == 'Sale')
                {
                    $product_variant_temp = [
                        "id" => $products_for_sale_data->id,
                        "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                        "product_id" => $products_for_sale_data->product->id,
                        "product_variant_id" => $product_variant_item->id,
                        "boutique_id" => $products_for_sale_data->product->boutique->id,
                        "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                        "brand_id" => $products_for_sale_data->product->brand->id,
                        "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                        "sale_type" => (string) $products_for_sale_data->type,
                        "product_type" => (string) $products_for_sale_data->product->type,
                        "name" => (string) $products_for_sale_data->product->name,
                        "attribute_names" => (string) $product_inventory->getAttributeValueOnly(),
                        "available_quantity" => (string) $product_inventory->availableStockQuantity(),
                        "final_price" => (string) $product_inventory->getFinalPrice(),
                        "regular_price" => (string) $product_inventory->getRegularPrice(),
                        "initial_quantity" => "",
                        "incremental_quantity" => "",
                        "incremental_price" => "",
                        "bid_start_price" => "",
                        "bid_value" => "",
                        "current_bid_amount" => "",
                        "estimate_start_price" => "",
                        "estimate_end_price" => "",
                        "is_special_price" => (string) $product_inventory->getSpecialPriceStatus(),
                        "special_price_start_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialStartTime(), $time_zone),
                        "special_price_end_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialEndTime(), $time_zone),
                        "start_time" => "",
                        "end_time" => "",
                        "auction_status" => "",
                        "auction_status_text" => "",
                        "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
//                        "purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                        "purchase_status" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($product_variant_item->id),
                        "cart_status" => (string) $products_for_sale_data->getCartStatus($product_variant_item->id),
                        "description" => (string) $products_for_sale_data->product->description,
                        "views_count" => (string) $products_for_sale_data->user_view_count,
                        "thumb_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->thumb_image:'',
                        "original_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->original_image:'',
                        "thumb_images" => $product_inventory->thumb_images(),
                        "original_images" => $product_inventory->original_images(),
                        "video_url" => ($product_inventory->default_video)?$product_inventory->default_video->video_url:'',
                        "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                        "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                        "is_available" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "bid_lists" => [],
                        "total_bidding_count" => (string) 0,
                    ];
                }
                elseif($products_for_sale_data->product->sale_type == 'Auction')
                {
                    $winner_user_id = 0;
                    if($products_for_sale_data->bid_end_time < $current_date_time)
                    {
                        $winner_bid = BiddingSummary::where('product_for_auction_or_sale_id', $products_for_sale_data->id)->orderBy('current_bid_amount', 'DESC')->first();
                        if($winner_bid) $winner_user_id = $winner_bid->user_id;

                    }

                    $bidding_history_query = BiddingSummary::query();
                    $bidding_history_query->where('product_for_auction_or_sale_id', $products_for_sale_data->id);
                    $bidding_history_query->where('product_variant_id', $product_variant_item->id);
                    $bidding_history_query->orderBy('current_bid_amount', 'DESC');
                    $bidding_history_count_query = clone $bidding_history_query;
                    $bidding_count = $bidding_history_count_query->count();
                    if(empty($request->get('page'))) $per_page = $bidding_count;
                    $bidding_histories_data_array = $bidding_history_query->limit(10)->get();
                    $bidding_histories_data = [];
                    foreach ($bidding_histories_data_array as $bidding_history)
                    {
                        if($bidding_history->user->id == auth('api')->id())
                        {
//                            $first_name = __('messages.your_bid');
//                            $last_name = "";
                            $first_name = $bidding_history->user->first_name;
                            $last_name = $bidding_history->user->last_name;
                            $is_own_bid = 1;
                        }
                        else
                        {
                            $first_name = $bidding_history->user->first_name;
                            $last_name = $bidding_history->user->last_name;
                            $is_own_bid = 0;
                        }

                        $is_winner = 0;
                        $is_ready_to_checkout = 0;
                        if($bidding_history->user->id == $winner_user_id)
                        {
                            $is_winner = 1;
                            if(!$products_for_sale_data->bid_purchase_status)
                            {
                                $is_ready_to_checkout = 1;
                                $checkout_expiry_time_in_hours = getCheckoutExpiryInHr();
                                $checkout_expiry_time = date('Y-m-d H:i:s', strtotime($products_for_sale_data->bid_end_date.'+'.$checkout_expiry_time_in_hours.' hours'));
                                if($checkout_expiry_time < $current_date_time)
                                {
                                    $is_ready_to_checkout = 0;
                                }
                            }
                        }

                        $bidding_histories_data_temp = [
                            'id' => $bidding_history->id,
                            'user_first_name' => (string) $first_name,
                            'user_last_name' => (string) $last_name,
                            'profile_image_thumb' => (string) $bidding_history->user->thumb_image,
                            'profile_image_original' => (string) $bidding_history->user->original_image,
                            'bid_amount' => (string) $bidding_history->current_bid_amount,
                            'is_own_bid' => (string) $is_own_bid,
                            'is_winner' => (string) $is_winner,
                            'is_ready_to_checkout' => (string) $is_ready_to_checkout,
                        ];
                        array_push($bidding_histories_data, $bidding_histories_data_temp);
                    }
                    $product_variant_temp = [
                        "id" => $products_for_sale_data->id,
                        "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                        "product_id" => $products_for_sale_data->product->id,
                        "product_variant_id" => $product_variant_item->id,
                        "boutique_id" => $products_for_sale_data->product->boutique->id,
                        "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                        "brand_id" => $products_for_sale_data->product->brand->id,
                        "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                        "sale_type" => (string) $products_for_sale_data->type,
                        "product_type" => (string) $products_for_sale_data->product->type,
                        "name" => (string) $products_for_sale_data->product->name,
                        "attribute_names" => (string) $product_inventory->getAttributeValueOnly(),
                        "available_quantity" => (string) $product_inventory->availableStockQuantity(),
                        "final_price" => "",
                        "regular_price" => "",
                        "initial_quantity" => "",
                        "incremental_quantity" => "",
                        "incremental_price" => "",
                        "bid_start_price" => (string) $product_inventory->getBidStartPrice(),
                        "bid_value" => (string) $product_inventory->getBidValue(),
                        "current_bid_amount" => (string) $product_inventory->getCurrentBidAmount($products_for_sale_data->id),
                        "estimate_start_price" => (string) $product_inventory->getEstimateStartPrice(),
                        "estimate_end_price" => (string) $product_inventory->getEstimateEndPrice(),
                        "is_special_price" => (string) $product_inventory->getSpecialPriceStatus(),
                        "special_price_start_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialStartTime(), $time_zone),
                        "special_price_end_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialEndTime(), $time_zone),
                        "start_time" => (string) getLocalTimeFromUtc($products_for_sale_data->bid_start_time, $time_zone),
                        "end_time" => (string) getLocalTimeFromUtc($products_for_sale_data->bid_end_time, $time_zone),
                        "auction_status" => (string) $products_for_sale_data->getAuctionStatus(),
                        "auction_status_text" => (string) $products_for_sale_data->getAuctionStatusText(),
                        "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
                       // "purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                        "purchase_status" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($product_variant_item->id),
                        "cart_status" => (string) $products_for_sale_data->getCartStatus($product_variant_item->id),
                        "description" => (string) $products_for_sale_data->product->description,
                        "views_count" => (string) $products_for_sale_data->user_view_count,
                        "thumb_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->thumb_image:'',
                        "original_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->original_image:'',
                        "thumb_images" => $product_inventory->thumb_images(),
                        "original_images" => $product_inventory->original_images(),
                        "video_url" => ($product_inventory->default_video)?$product_inventory->default_video->video_url:'',
                        "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                        "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                        "is_available" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "bid_lists" => $bidding_histories_data,
                        "total_bidding_count" => (string) $bidding_count,
                    ];
                }
                elseif($products_for_sale_data->product->sale_type == 'Bulk')
                {
                    $product_variant_temp = [
                        "id" => $products_for_sale_data->id,
                        "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                        "product_id" => $products_for_sale_data->product->id,
                        "product_variant_id" => $product_variant_item->id,
                        "boutique_id" => $products_for_sale_data->product->boutique->id,
                        "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                        "brand_id" => $products_for_sale_data->product->brand->id,
                        "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                        "sale_type" => (string) $products_for_sale_data->type,
                        "product_type" => (string) $products_for_sale_data->product->type,
                        "name" => (string) $products_for_sale_data->product->name,
                        "attribute_names" => (string) $product_inventory->getAttributeValueOnly(),
                        "available_quantity" => (string) $product_inventory->availableStockQuantity(),
                        "final_price" => (string) $product_inventory->getFinalPrice(),
                        "regular_price" => (string) $product_inventory->getRegularPrice(),
                        "initial_quantity" => (string) $product_inventory->getInitialQuantity(),
                        "incremental_quantity" => (string) $product_inventory->getIncrementalQuantity(),
                        "incremental_price" => (string) $product_inventory->getIncrementalPrice(),
                        "bid_start_price" => "",
                        "bid_value" => "",
                        "current_bid_amount" => "",
                        "estimate_start_price" => "",
                        "estimate_end_price" => "",
                        "is_special_price" => (string) $product_inventory->getSpecialPriceStatus(),
                        "special_price_start_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialStartTime(), $time_zone),
                        "special_price_end_time" => (string) getLocalTimeFromUtc($product_inventory->getSpecialEndTime(), $time_zone),
                        "start_time" => "",
                        "end_time" => "",
                        "auction_status" => "",
                        "auction_status_text" => "",
                        "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
                       // "purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                        "purchase_status" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($product_variant_item->id),
                        "cart_status" => (string) $products_for_sale_data->getCartStatus($product_variant_item->id),
                        "description" => (string) $products_for_sale_data->product->description,
                        "views_count" => (string) $products_for_sale_data->user_view_count,
                        "thumb_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->thumb_image:'',
                        "original_image" => (string) ($product_inventory->default_image)?$product_inventory->default_image->original_image:'',
                        "thumb_images" => $product_inventory->thumb_images(),
                        "original_images" => $product_inventory->original_images(),
                        "video_url" => ($product_inventory->default_video)?$product_inventory->default_video->video_url:'',
                        "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                        "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                        "is_available" => ($product_inventory->availableStockQuantity() > 0)?'1':'0',
                        "bid_lists" => [],
                        "total_bidding_count" => (string) 0,
                    ];
                }
                else
                {
                    $product_variant_temp = [];
                }
                array_push($product_variant_array, $product_variant_temp);
            }

            $product_attribute_values_1 = DB::table('product_attributes')->where('product_id', $product_id)->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id')->get() ->groupBy(['product_variant_id']);;
            $product_variant_attribute_combinations = [];
            foreach ($product_attribute_values_1 as $key_1 => $product_attribute_value_1)
            {
                $product_variant_temp = ProductVariant::where('id', $key_1)->first();
                if($product_variant_temp->availableStockQuantity() <= 0) $stock_available = 0; else $stock_available = 1;
                $product_variant_attribute_combination_temp['product_variant_id'] = $key_1;
                $product_variant_attribute_combination_temp['stock_available'] = (string) $stock_available;
                $product_variant_attribute_combination_temp['product_variant_combination_names'] = $product_variant_temp->getAttributeValueOnly();
                foreach ($product_attribute_value_1 as $key_2 => $product_attribute_value_item_1)
                {
                    $product_variant_attribute_combination_temp['attributes'][$key_2]['attribute_id'] = $product_attribute_value_item_1->attribute_id;
                    $attribute_temp = Attribute::where('id', $product_attribute_value_item_1->attribute_id)->first();
                    $product_variant_attribute_combination_temp['attributes'][$key_2]['attribute_name'] = $attribute_temp->getAttributeName();
                    $product_variant_attribute_combination_temp['attributes'][$key_2]['attribute_value_id'] = $product_attribute_value_item_1->attribute_value_id;
                    $attribute_value_temp = AttributeValue::where('id', $product_attribute_value_item_1->attribute_value_id)->first();
                    $product_variant_attribute_combination_temp['attributes'][$key_2]['attribute_value'] = $attribute_value_temp->getAttributeValueName();
                }
                array_push($product_variant_attribute_combinations, $product_variant_attribute_combination_temp);
            }



            if($products_for_sale_data->product->sale_type == 'Sale')
            {
                $product_temp_data = [
                    "id" => $products_for_sale_data->id,
                    "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                    "product_id" => $products_for_sale_data->product->id,
                    "product_variant_id" => $products_for_sale_data->product->product_inventory->id,
                    "boutique_id" => $products_for_sale_data->product->boutique->id,
                    "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                    "brand_id" => $products_for_sale_data->product->brand->id,
                    "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                    "sale_type" => (string) $products_for_sale_data->type,
                    "product_type" => (string) $products_for_sale_data->product->type,
                    "name" => (string) $products_for_sale_data->product->name,
                    "attribute_names" => (string) $products_for_sale_data->product->product_inventory->getAttributeValueOnly(),
                    "available_quantity" => (string) $products_for_sale_data->product->product_inventory->availableStockQuantity(),
                    "final_price" => (string) $products_for_sale_data->product->product_inventory->getFinalPrice(),
                    "regular_price" => (string) $products_for_sale_data->product->product_inventory->getRegularPrice(),
                    "initial_quantity" => "",
                    "incremental_quantity" => "",
                    "incremental_price" => "",
                    "bid_start_price" => "",
                    "bid_value" => "",
                    "current_bid_amount" => "",
                    "estimate_start_price" => "",
                    "estimate_end_price" => "",
                    "is_special_price" => (string) $products_for_sale_data->product->product_inventory->getSpecialPriceStatus(),
                    "special_price_start_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialStartTime(), $time_zone),
                    "special_price_end_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialEndTime(), $time_zone),
                    "start_time" => "",
                    "end_time" => "",
                    "auction_status" => "",
                    "auction_status_text" => "",
                    "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
                   // "purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                    "purchase_status" => (string) $is_available_for_detail,
                    "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($products_for_sale_data->product->product_inventory->id),
                    "cart_status" => (string) $products_for_sale_data->getCartStatus($products_for_sale_data->product->product_inventory->id),
                    "description" => (string) $products_for_sale_data->product->description,
                    "views_count" => (string) $products_for_sale_data->user_view_count,
                    "thumb_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->thumb_image:'',
                    "original_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->original_image:'',
                    "thumb_images" => $products_for_sale_data->product->product_inventory->thumb_images(),
                    "original_images" => $products_for_sale_data->product->product_inventory->original_images(),
                    "video_url" => ($products_for_sale_data->product->product_inventory->default_video)?$products_for_sale_data->product->product_inventory->default_video->video_url:'',
                    "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                    "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                    "is_available" => (string) $is_available_for_detail,
                    "bid_lists" => [],
                    "total_bidding_count" => (string) 0,
                    "product_variants" => $product_variant_array,
                    "attribute_sets" => $attribute_data,
                    "available_combinations" => $product_variant_attribute_combinations,
                    'cart_count' => (string) getCartCount()
                ];
            }
            elseif($products_for_sale_data->product->sale_type == 'Auction')
            {

                $winner_user_id = 0;
                if($products_for_sale_data->bid_end_time < $current_date_time)
                {
                    $winner_bid = BiddingSummary::where('product_for_auction_or_sale_id', $products_for_sale_data->id)->orderBy('current_bid_amount', 'DESC')->first();
                    if($winner_bid) $winner_user_id = $winner_bid->user_id;

                }

                $bidding_history_query = BiddingSummary::query();
                $bidding_history_query->where('product_for_auction_or_sale_id', $products_for_sale_data->id);
                $bidding_history_query->where('product_variant_id', $products_for_sale_data->product->product_inventory->id);
                $bidding_history_query->orderBy('current_bid_amount', 'DESC');
                $bidding_history_count_query = clone $bidding_history_query;
                $bidding_count = $bidding_history_count_query->count();
                if(empty($request->get('page'))) $per_page = $bidding_count;
                $bidding_histories_data_array = $bidding_history_query->limit(10)->get();
                $bidding_histories_data = [];
                foreach ($bidding_histories_data_array as $bidding_history)
                {
                    if($bidding_history->user->id == auth('api')->id())
                    {
//                        $first_name = __('messages.your_bid');
//                        $last_name = "";
                        $first_name = $bidding_history->user->first_name;
                        $last_name = $bidding_history->user->last_name;
                        $is_own_bid = 1;
                    }
                    else
                    {
                        $first_name = $bidding_history->user->first_name;
                        $last_name = $bidding_history->user->last_name;
                        $is_own_bid = 0;
                    }

                    $is_winner = 0;
                    $is_ready_to_checkout = 0;
                    if($bidding_history->user->id == $winner_user_id)
                    {
                        $is_winner = 1;
                        if(!$products_for_sale_data->bid_purchase_status)
                        {
                            $is_ready_to_checkout = 1;
                            $checkout_expiry_time_in_hours = getCheckoutExpiryInHr();
                            $checkout_expiry_time = date('Y-m-d H:i:s', strtotime($products_for_sale_data->bid_end_date.'+'.$checkout_expiry_time_in_hours.' hours'));
                            if($checkout_expiry_time < $current_date_time)
                            {
                                $is_ready_to_checkout = 0;
                            }
                        }
                    }

                    $bidding_histories_data_temp = [
                        'id' => $bidding_history->id,
                        'user_first_name' => (string) $first_name,
                        'user_last_name' => (string) $last_name,
                        'profile_image_thumb' => (string) $bidding_history->user->thumb_image,
                        'profile_image_original' => (string) $bidding_history->user->original_image,
                        'bid_amount' => (string) $bidding_history->current_bid_amount,
                        'is_own_bid' => (string) $is_own_bid,
                        'is_winner' => (string) $is_winner,
                        'is_ready_to_checkout' => (string) $is_ready_to_checkout,
                    ];
                    array_push($bidding_histories_data, $bidding_histories_data_temp);
                }

                $product_temp_data = [
                    "id" => $products_for_sale_data->id,
                    "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                    "product_id" => $products_for_sale_data->product->id,
                    "product_variant_id" => $products_for_sale_data->product->product_inventory->id,
                    "boutique_id" => $products_for_sale_data->product->boutique->id,
                    "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                    "brand_id" => $products_for_sale_data->product->brand->id,
                    "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                    "sale_type" => (string) $products_for_sale_data->type,
                    "product_type" => (string) $products_for_sale_data->product->type,
                    "name" => (string) $products_for_sale_data->product->name,
                    "attribute_names" => (string) $products_for_sale_data->product->product_inventory->getAttributeValueOnly(),
                    "available_quantity" => (string) $products_for_sale_data->product->product_inventory->availableStockQuantity(),
                    "final_price" => "",
                    "regular_price" => "",
                    "initial_quantity" => "",
                    "incremental_quantity" => "",
                    "incremental_price" => "",
                    "bid_start_price" => (string) $products_for_sale_data->product->product_inventory->getBidStartPrice(),
                    "bid_value" => (string) $products_for_sale_data->product->product_inventory->getBidValue(),
                    "current_bid_amount" => (string) $products_for_sale_data->product->product_inventory->getCurrentBidAmount($products_for_sale_data->id),
                    "estimate_start_price" => (string) $products_for_sale_data->product->product_inventory->getEstimateStartPrice(),
                    "estimate_end_price" => (string) $products_for_sale_data->product->product_inventory->getEstimateEndPrice(),
                    "is_special_price" => (string) $products_for_sale_data->product->product_inventory->getSpecialPriceStatus(),
                    "special_price_start_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialStartTime(), $time_zone),
                    "special_price_end_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialEndTime(), $time_zone),
                    "start_time" => (string) getLocalTimeFromUtc($products_for_sale_data->bid_start_time, $time_zone),
                    "end_time" => (string) getLocalTimeFromUtc($products_for_sale_data->bid_end_time, $time_zone),
                    "auction_status" => (string) $products_for_sale_data->getAuctionStatus(),
                    "auction_status_text" => (string) $products_for_sale_data->getAuctionStatusText(),
                    "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
                   // "purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                    "purchase_status" => (string) $is_available_for_detail,
                    "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($products_for_sale_data->product->product_inventory->id),
                    "cart_status" => (string) $products_for_sale_data->getCartStatus($products_for_sale_data->product->product_inventory->id),
                    "description" => (string) $products_for_sale_data->product->description,
                    "views_count" => (string) $products_for_sale_data->user_view_count,
                    "thumb_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->thumb_image:'',
                    "original_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->original_image:'',
                    "thumb_images" => $products_for_sale_data->product->product_inventory->thumb_images(),
                    "original_images" => $products_for_sale_data->product->product_inventory->original_images(),
                    "video_url" => ($products_for_sale_data->product->product_inventory->default_video)?$products_for_sale_data->product->product_inventory->default_video->video_url:'',
                    "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                    "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                    "is_available" => (string) $is_available_for_detail,
                    "bid_lists" => $bidding_histories_data,
                    "total_bidding_count" => (string) $bidding_count,
                    "product_variants" => $product_variant_array,
                    "attribute_sets" => $attribute_data,
                    "available_combinations" => $product_variant_attribute_combinations,
                    'cart_count' => (string) getCartCount()
                ];
            }
            elseif($products_for_sale_data->product->sale_type == 'Bulk')
            {
                $product_temp_data = [
                    "id" => $products_for_sale_data->id,
                    "product_for_auction_or_sale_id" => $products_for_sale_data->id,
                    "product_id" => $products_for_sale_data->product->id,
                    "product_variant_id" => $products_for_sale_data->product->product_inventory->id,
                    "boutique_id" => $products_for_sale_data->product->boutique->id,
                    "boutique_name" => (string) $products_for_sale_data->product->boutique()->select($name_array['name'])->first()->name,
                    "brand_id" => $products_for_sale_data->product->brand->id,
                    "brand_name" => (string) $products_for_sale_data->product->brand()->select($name_array['title'])->first()->title,
                    "sale_type" => (string) $products_for_sale_data->type,
                    "product_type" => (string) $products_for_sale_data->product->type,
                    "name" => (string) $products_for_sale_data->product->name,
                    "attribute_names" => (string) $products_for_sale_data->product->product_inventory->getAttributeValueOnly(),
                    "available_quantity" => (string) $products_for_sale_data->product->product_inventory->availableStockQuantity(),
                    "final_price" => (string) $products_for_sale_data->product->product_inventory->getFinalPrice(),
                    "regular_price" => (string) $products_for_sale_data->product->product_inventory->getRegularPrice(),
                    "initial_quantity" => (string) $products_for_sale_data->product->product_inventory->getInitialQuantity(),
                    "incremental_quantity" => (string) $products_for_sale_data->product->product_inventory->getIncrementalQuantity(),
                    "incremental_price" => (string) $products_for_sale_data->product->product_inventory->getIncrementalPrice(),
                    "bid_start_price" => "",
                    "bid_value" => "",
                    "current_bid_amount" => "",
                    "estimate_start_price" => "",
                    "estimate_end_price" => "",
                    "is_special_price" => (string) $products_for_sale_data->product->product_inventory->getSpecialPriceStatus(),
                    "special_price_start_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialStartTime(), $time_zone),
                    "special_price_end_time" => (string) getLocalTimeFromUtc($products_for_sale_data->product->product_inventory->getSpecialEndTime(), $time_zone),
                    "start_time" => "",
                    "end_time" => "",
                    "auction_status" => "",
                    "auction_status_text" => "",
                    "is_me_winner" => (string) $products_for_sale_data->getIsMeWinner(),
                    //"purchase_status" => (string) $products_for_sale_data->getPurchaseStatus(),
                    "purchase_status" => (string) $is_available_for_detail,
                    "favorite_status" => (string) $products_for_sale_data->getFavoriteStatus($products_for_sale_data->product->product_inventory->id),
                    "cart_status" => (string) $products_for_sale_data->getCartStatus($products_for_sale_data->product->product_inventory->id),
                    "description" => (string) $products_for_sale_data->product->description,
                    "views_count" => (string) $products_for_sale_data->user_view_count,
                    "thumb_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->thumb_image:'',
                    "original_image" => (string) ($products_for_sale_data->product->product_inventory->default_image)?$products_for_sale_data->product->product_inventory->default_image->original_image:'',
                    "thumb_images" => $products_for_sale_data->product->product_inventory->thumb_images(),
                    "original_images" => $products_for_sale_data->product->product_inventory->original_images(),
                    "video_url" => ($products_for_sale_data->product->product_inventory->default_video)?$products_for_sale_data->product->product_inventory->default_video->video_url:'',
                    "is_enable" => (string) $products_for_sale_data->product->getEnableStatus(),
                    "is_new_product" => (string) $products_for_sale_data->product->isNewProduct(),
                    "is_available" => (string) $is_available_for_detail,
                    "bid_lists" => [],
                    "total_bidding_count" => (string) 0,
                    "product_variants" => $product_variant_array,
                    "attribute_sets" => $attribute_data,
                    "available_combinations" => $product_variant_attribute_combinations,
                    'cart_count' => (string) getCartCount()
                ];
            }
            else
            {
                $product_temp_data = [];
            }

            $product_view_count = ProductForAuctionOrSale::findOrFail($products_for_sale_data->id)->user_view_count;
            ProductForAuctionOrSale::where('id', $products_for_sale_data->id)->update(['user_view_count' => $product_view_count + 1]);
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_product_detail_success',
                    'data' => $product_temp_data

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

    public function userFavoriteProduct(Request $request)
    {
        try
        {
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $time_zone = getLocalTimeZone($request->time_zone);
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
            $validator = Validator::make($request->all(), [
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
            $product_variant = ProductVariant::where('id', $request->product_variant_id)->firstOrFail();
            $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $request->product_for_auction_or_sale_id)->firstOrFail();
            $fav_un_fav = DB::table('favorite_products')->where('user_id', $user_id)->where('product_variant_id', $request->product_variant_id)->where('product_for_auction_or_sale_id', $request->product_for_auction_or_sale_id)->first();
            if($fav_un_fav)
            {
                DB::table('favorite_products')->where('user_id', $user_id)->where('product_variant_id', $request->product_variant_id)->where('product_for_auction_or_sale_id', $request->product_for_auction_or_sale_id)->delete();
                $message = __('messages.product_successfully_un_favorite');
            }
            else
            {
                DB::table('favorite_products')->insert([
                    'user_id' => $user_id,
                    'product_for_auction_or_sale_id' => $product_for_auction_or_sale->id,
                    'product_variant_id' => $product_variant->id,
                    'created_at' => $current_date_time,
                    'updated_at' => $current_date_time,
                ]);
                $message = __('messages.product_successfully_favorite');
            }
            $wishlists = DB::table('favorite_products')->where('user_id', $user_id)->get();
            $product_wishlists_array = [];
            foreach ($wishlists as $key => $wishlist)
            {
                $products_auction_detail_query = ProductForAuctionOrSale::query();
                $products_auction_detail_query->where('status', 1);
                $products_auction_detail_query->where('id', $wishlist->product_for_auction_or_sale_id);
                $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
                $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $wishlist) {
                    $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
//                    $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                    $query_9->with([
                        'product_inventory' => function($q) use ($wishlist) {
                            $q->where('id','=',$wishlist->product_variant_id);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }]);

//                $products_auction_detail_query->whereHas('product', function ($query_2) use($wishlist) {
//                    $query_2->whereHas('product_inventory', function ($query_10) use($wishlist) {
//                        $query_10->where('product_variants.id', $wishlist->product_variant_id);
//                    });
//                });

                $products_auction_detail_query->orderBy('id', 'DESC');
                $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

                $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

                $product_wishlists_array[$key] = $products_auction_detail_data[0];
            }
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => $message,
                    'message_code' => 'user_fav_un_fav_success',
                    'data' => [
                        "wishlists" => $product_wishlists_array,
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


    public function userFavoriteProductLists(Request $request)
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
            $wishlists = DB::table('favorite_products')->where('user_id', $user_id)->get();
            $product_wishlists_array = [];
            foreach ($wishlists as $key => $wishlist)
            {
                $products_auction_detail_query = ProductForAuctionOrSale::query();
                $products_auction_detail_query->where('status', 1);
                $products_auction_detail_query->where('id', $wishlist->product_for_auction_or_sale_id);
                $products_auction_detail_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
                $products_auction_detail_query->with(['product' => function ($query_9) use($name_array, $wishlist) {
                    $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'attribute_set_id', 'status', 'brand_id');
//                    $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                    $query_9->with([
                        'product_inventory' => function($q) use ($wishlist) {
                            $q->where('id','=',$wishlist->product_variant_id);
                            $q->with('default_image', 'default_video');
                        }
                    ]);
                }]);

//                $products_auction_detail_query->whereHas('product', function ($query_2) use($wishlist) {
//                    $query_2->whereHas('product_inventory', function ($query_10) use($wishlist) {
//                        $query_10->where('product_variants.id', $wishlist->product_variant_id);
//                    });
//                });

                $products_auction_detail_query->orderBy('id', 'DESC');
                $products_auction_detail_data_array = $products_auction_detail_query->limit(1)->get();

                $products_auction_detail_data = productData($products_auction_detail_data_array, $time_zone); // defined in helpers

                $product_wishlists_array[$key] = $products_auction_detail_data[0];
            }
            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'wishlist_success',
                    'data' => [
                        "wishlists" => $product_wishlists_array,
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

    public function getUserProductCombination(Request $request, $sale_or_auction_id)
    {
//        try
//        {
//
//            if(app()->getLocale() == 'ar')
//            {
//                $name_array['name'] = "name_ar as name";
//                $name_array['title'] = "title_ar as name";
//                $name_array['description'] = "description_ar as description";
//                $name_array['offer_text'] = "offer_text_ar as offer_text";
//            }
//            else
//            {
//                $name_array['name'] = "name";
//                $name_array['title'] = "title";
//                $name_array['description'] = "description";
//                $name_array['offer_text'] = "offer_text";
//            }
//
//            $product_for_auction_or_sale = ProductForAuctionOrSale::where('id', $sale_or_auction_id)->first();
//            $product_id = $product_for_auction_or_sale->product->id;
//
//            $attribute_ids = $request->attribute_id;
//            $attribute_value_ids = $request->attribute_value_id;
//
//            if(!empty($attribute_ids) && !empty($attribute_value_ids))
//            {
//                $product_attribute_value_query = DB::table('product_attributes');
//                $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
//                $product_attribute_value_query->where(function($q) use ($attribute_ids, $attribute_value_ids) {
//                    foreach ($attribute_ids as $key1 => $attribute_id)
//                    {
//                        if($key1 == 0)
//                        {
//                            $q->where('attribute_value_id', $attribute_value_ids[$key1])->where('attribute_id', $attribute_ids[$key1]);
//                        }
//                        else
//                        {
//                            $q->orWhere('attribute_value_id', $attribute_value_ids[$key1])->where('attribute_id', $attribute_ids[$key1]);
//                        }
//                    }
//                });
//
//                $attribute_set_count = count($attribute_ids);
//                $product_attribute_count_query = $product_attribute_value_query->where('product_id', $product_id);
//                $product_attribute_count = $product_attribute_count_query->count();
//                if($attribute_set_count == $product_attribute_count)
//                {
//                    $product_variant_id = $product_attribute_value_query->first()->product_variant_id;
//                    $products_for_sale_query = ProductForAuctionOrSale::query();
//                    $products_for_sale_query->where('status', 1);
//                    $products_for_sale_query->where('id', $sale_or_auction_id);
//                    $products_for_sale_query->select('id', 'product_id', 'user_id', 'type', 'user_view_count');
//                    $products_for_sale_query->with(['product' => function ($query) use($name_array) {
//                        $query->select('id', $name_array['name'], $name_array['description'], 'attribute_set_id');
//                       // $query->with('product_inventory.default_image', 'product_inventory.default_video', 'product_inventory.images');
//                    }]);
//                    $products_for_sale_query->whereHas('product', function ($query1) use ($product_id) {
//                            $query1->where("products.id", "=", $product_id);
//                        });
//                    $products_for_sale_query->whereHas('product.product_variants', function ($query2) use ($product_variant_id) {
//                        $query2->where("product_variants.id", "=", $product_variant_id);
//                    });
//                    $products_for_sale_data = $products_for_sale_query->first();
//
//                    $product_id = $products_for_sale_data->product->id;
//                    $products_for_sale_data['product']['product_inventory'] = $products_for_sale_data->product->product_inventory($product_variant_id)->first();
//                    $products_for_sale_data['product']['product_inventory']['default_image'] = $products_for_sale_data->product->product_inventory(4)->first()->default_image;
//                    $products_for_sale_data['product']['product_inventory']['images'] = $products_for_sale_data->product->product_inventory(4)->first()->images;
//                    $product_variant_id = $products_for_sale_data->product->product_inventory->id;
//                    $attribute_sets = AttributeSet::where('id', $products_for_sale_data->product->attribute_set_id)->first();
//                    $attribute_set_attributes = $attribute_sets->attributes()->select('attributes.id', 'code', 'colour_palette', $name_array['name'])->get();
//                    $attribute_data = [];
//                    foreach ($attribute_set_attributes as $key => $attribute_set_attribute)
//                    {
//
//                        $product_attribute_values = DB::table('product_attributes')->where('product_id', $product_id)->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id')->get();
//                        $attribute_data[$key]['id'] = $attribute_set_attribute->id;
//                        $attribute_data[$key]['name'] = $attribute_set_attribute->name;
//                        $attribute_data[$key]['code'] = $attribute_set_attribute->code;
//                        $attribute_data[$key]['colour_palette'] = $attribute_set_attribute->colour_palette;
//                        $attribute_value_data = [];
//                        foreach ($product_attribute_values as $key2 => $product_attribute_value)
//                        {
//                            $attribute_value =  AttributeValue::select('id', $name_array['name'], 'colour')->where('id', $product_attribute_value->attribute_value_id)->where('attribute_id', $attribute_set_attribute->id)->first();
//                            if($attribute_value)
//                            {
//                                if($product_variant_id == $product_attribute_value->product_variant_id) $active = true; else $active = false;
//                                if($attribute_set_attribute->colour_palette) $colour = $attribute_value->colour; else $colour = null;
//                                $temp_data_2 = [
//                                    'id' => $attribute_value->id,
//                                    'name' => $attribute_value->name,
//                                    'active' => $active,
//                                    'colour' => $colour,
//                                ];
//                                array_push($attribute_value_data, $temp_data_2);
//                            }
//                        }
//                        $attribute_data[$key]['attribute_values'] = $attribute_value_data;
//                    }
//                    $products_for_sale_data['product']['product_inventory']['attribute_sets'] = $attribute_data;
//                    return response()->json(
//                        [
//                            'success' => true,
//                            'status' => 200,
//                            'message' => __('messages.success'),
//                            'message_code' => 'user_products_success',
//                            'data' => $products_for_sale_data
//
//                        ], 200);
//
//                }
//                else
//                {
//                    return response()->json(
//                        [
//                            'success' => false,
//                            'status' => 400,
//                            'message' => __('messages.no_combination_found'),
//                            'message_code' => 'user_products_combination_not_found',
//                            'data' => []
//
//                        ], 200);
//                }
//            }
//            else
//            {
//                return response()->json(
//                    [
//                        'success' => false,
//                        'status' => 400,
//                        'message' => __('messages.no_combination_found'),
//                        'message_code' => 'user_products_combination_params_mismatch',
//                        'data' => []
//
//                    ], 200);
//            }
//        }
//        catch (\Exception $exception)
//        {
//            return response()->json(
//                [
//                    'success' => false,
//                    'status' => 500,
//                    'message' => __('messages.something_went_wrong'),
//                    'message_code' => 'try_catch',
//                    'exception' => $exception->getMessage()
//                ], 500);
//        }

    }

}

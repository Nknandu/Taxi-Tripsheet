<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use App\Models\BiddingSummary;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\SpecialPrice;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\NullableType;

class UserHomeController extends Controller
{
   public function getUserHomeData(Request $request)
   {
       try
       {
           $name_array = [];
           if(app()->getLocale() == 'ar')
           {
               $name_array['name'] = "name_ar as name";
               $name_array['title'] = "title_ar as title";
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
           $category_query = Category::query();
           $category_query->where('shown_in_app', 1);
           $category_query->select('id', 'parent_id', $name_array['name'], 'image', 'image_ar');
           $category_query->orderBy('sort_order', 'ASC');
           $category_data_array = $category_query->limit(10)->get();
           $category_data = [];
           foreach ($category_data_array as $key => $category_data_item)
           {

               $category_temp_data = [
                   "id" => $category_data_item->id,
                   "parent_id" => (string) $category_data_item->parent_id,
                   "name" => $category_data_item->name,
                   "thumb_image" => (string) $category_data_item->thumb_image,
                   "original_image" => (string) $category_data_item->original_image,
               ];
               array_push($category_data, $category_temp_data);
           }


           $brand_query = Brand::query();
           $brand_query->where('status', 1);
           $brand_query->select('id', $name_array['title'], 'image', 'image_ar', 'status', 'sort');
           $brand_query->orderBy('sort', 'ASC');
           $brand_data_array = $brand_query->limit(10)->get();
           $brand_data = [];
           foreach ($brand_data_array as $key => $brand_data_item)
           {

               $brand_temp_data = [
                   "id" => $brand_data_item->id,
                   "name" => $brand_data_item->title,
                   "thumb_image" => (string) $brand_data_item->thumb_image,
                   "original_image" => (string) $brand_data_item->original_image,
               ];
               array_push($brand_data, $brand_temp_data);
           }

           $app_banner_query = AppBanner::query();
           $app_banner_query->where('status', 1);
           $app_banner_query->select('id', 'tag', 'tag_id', $name_array['title'], 'image', 'image_ar', 'start_time', 'end_time', 'tag', 'tag_id', 'external_link');
           $app_banner_query->where(function($query) use ($current_date_time){
               $query->where(function($query2) use ($current_date_time){
                   $query2->where('start_time', '<=', $current_date_time)->where('end_time', '>=', $current_date_time);
               });
               $query->orWhereNull('start_time');
           });
           $app_banner_query->orderBy('id', 'DESC');
           $app_banner_data_array = $app_banner_query->get();
           $app_banner_data = [];
           foreach ($app_banner_data_array as $key => $app_banner_data_item)
           {
               $app_banner_temp_data = [
                   "id" => $app_banner_data_item->id,
                   "title" => (string) $app_banner_data_item->title,
                   "tag" => (string) $app_banner_data_item->tag,
                   "tag_id" => ($app_banner_data_item->tag_id)?$app_banner_data_item->tag_id:0,
                   "external_link" => (string) $app_banner_data_item->external_link,
                   "thumb_image" => (string) $app_banner_data_item->thumb_image,
                   "original_image" => (string) $app_banner_data_item->original_image,
               ];
               array_push($app_banner_data, $app_banner_temp_data);
           }

           $special_variant_ids = SpecialPrice::where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time)->distinct()->pluck('product_variant_id')->toArray();
           $is_discounted_variant_ids = ProductVariant::whereRaw('final_price < regular_price')->distinct()->pluck('id')->toArray();
           $is_discounted_variant_ids = array_merge($is_discounted_variant_ids, $special_variant_ids);
           $is_discounted_product_ids = ProductVariant::whereIn('id', $is_discounted_variant_ids)->distinct()->pluck('product_id')->toArray();
           $is_discounted_product_ids = Product::whereIn('id', $is_discounted_product_ids)->whereIn('sale_type', ['Sale', 'Bulk'])->distinct()->pluck('id')->toArray();

           $products_for_sale_most_selling_query = ProductForAuctionOrSale::query();
           $products_for_sale_most_selling_query->where('status', 1);
           $products_for_sale_most_selling_query->where('type', 'Sale');
           if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_most_selling_query->where('type', 'NoType');
           $products_for_sale_most_selling_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
           $products_for_sale_most_selling_query->with(['product' => function ($query_1) use($name_array) {
               $query_1->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
               $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
           }]);
           $products_for_sale_most_selling_query->whereHas('product', function ($query_2) {
               $query_2->where("products.most_selling_status", 1);
               $query_2->where("products.status", 1);
           });

           if(!in_array('can-purchase-discounted', getUserPermissions()))
           {
               $products_for_sale_most_selling_query->whereHas('product', function ($query_2) use ($is_discounted_product_ids) {
                   $query_2->whereNotIn("products.id", $is_discounted_product_ids);
               });
           }

           $products_for_sale_most_selling_query->orderBy('id', 'DESC');
           $products_for_sale_most_selling_data_array = $products_for_sale_most_selling_query->limit(10)->get();

           $products_for_sale_most_selling_data = productData($products_for_sale_most_selling_data_array, $time_zone); // defined in helpers

           $products_for_sale_new_arrivals_query = ProductForAuctionOrSale::query();
           $products_for_sale_new_arrivals_query->where('status', 1);
           $products_for_sale_new_arrivals_query->where('type', 'Sale');
           if(!in_array('can-purchase-sale', getUserPermissions())) $products_for_sale_new_arrivals_query->where('type', 'NoType');
           $products_for_sale_new_arrivals_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
           $products_for_sale_new_arrivals_query->with(['product' => function ($query_3) use($name_array) {
               $query_3->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'brand_id');
               $query_3->with('product_inventory.default_image', 'product_inventory.default_video');
           }]);
           $products_for_sale_new_arrivals_query->whereHas('product', function ($query_2) use($current_date_time) {
               $query_2->where(function($query_4) use ($current_date_time){
                   $query_4->where(function($query_5) use ($current_date_time){
                       $query_5->where('new_arrival_start_time', '<=', $current_date_time)->where('new_arrival_end_time', '>=', $current_date_time);
                   });
                  // $query_4->orWhereNull('new_arrival_start_time');
               });
               $query_2->where('status', 1);
           });
           if(!in_array('can-purchase-discounted', getUserPermissions()))
           {
               $products_for_sale_new_arrivals_query->whereHas('product', function ($query_2) use ($is_discounted_product_ids) {
                   $query_2->whereNotIn("products.id", $is_discounted_product_ids);
               });
           }
           $products_for_sale_new_arrivals_query->orderBy('id', 'DESC');
           $products_for_sale_new_arrivals_data_array = $products_for_sale_new_arrivals_query->limit(10)->get();

           $products_for_sale_new_arrivals_data = productData($products_for_sale_new_arrivals_data_array, $time_zone); // defined in helpers

           $boutique_query = UserBoutique::query();
           $boutique_query->select('id', $name_array['name'], $name_array['description'], 'image', 'cover_image', 'is_featured');
           $boutique_query->where('status', 1);
           if(boutiqueFeaturedSorting()) $boutique_query->orderBy('is_featured', 'DESC');
           $boutique_query->orderBy('sort_order', 'ASC');
           $boutique_query_data_array = $boutique_query->limit(10)->get();

           $boutiques_data = [];
           foreach ($boutique_query_data_array as $key => $boutique_query_data_item)
           {
               $pro_ids = Product::where('user_boutique_id', $boutique_query_data_item->id)->pluck('id')->toArray();
               $category_ids = DB::table('product_categories')->whereIn('product_id', $pro_ids)->pluck('category_id')->toArray();
               $categories = Category::whereIn('id', $category_ids)->where('parent_id', NULL)->pluck($name_array['name'])->implode(', ');
               $boutique_temp_data = [
                   "id" => $boutique_query_data_item->id,
                   "name" => (string) $boutique_query_data_item->name,
                   "is_live" =>  (string) $boutique_query_data_item->getIsLiveStatus(),
                   "is_featured" => (string) $boutique_query_data_item->is_featured,
                   "description" => $boutique_query_data_item->description,
                   "categories" => (string) $categories,
                   "thumb_image" => (string) $boutique_query_data_item->thumb_image,
                   "original_image" => (string) $boutique_query_data_item->original_image,
                   "cover_image_thumb" => (string) $boutique_query_data_item->cover_image_thumb,
                   "cover_image_original" => (string) $boutique_query_data_item->cover_image_original,
               ];
               array_push($boutiques_data, $boutique_temp_data);
           }

           $live_auctions = [
               [
                   'id' => 1,
                   'start_date' => $current_date_time_local,
                   "thumb_image" => asset('assets/media/dummy/auction_1.png'),
                   "original_image" => asset('assets/media/dummy/auction_1.png'),
               ],
               [
                   'id' => 2,
                   'start_date' => $current_date_time_local,
                   "thumb_image" => asset('assets/media/dummy/auction_2.png'),
                   "original_image" => asset('assets/media/dummy/auction_2.png'),
               ]
           ];

           $products_upcoming_auction_query = ProductForAuctionOrSale::query();
           $products_upcoming_auction_query->where('status', 1);
           $products_upcoming_auction_query->where('type', 'Auction');
           if(!in_array('can-participate-auction', getUserPermissions())) $products_upcoming_auction_query->where('type', 'NoType');
           $products_upcoming_auction_query->where('bid_purchase_status', 0);
           $products_upcoming_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
           $products_upcoming_auction_query->with(['product' => function ($query_9) use($name_array) {
               $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
               $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
           }]);
           $products_upcoming_auction_query->where(function ($query_6) use($current_date_time) {
               $query_6->where('bid_end_time', '>', $current_date_time);
           });
           $products_upcoming_auction_query->whereHas('product', function ($query_10) {
               $query_10->where("products.status", 1);
           });
           if(!in_array('can-purchase-discounted', getUserPermissions()))
           {
               $products_upcoming_auction_query->whereHas('product', function ($query_2) use ($is_discounted_product_ids) {
                   $query_2->whereNotIn("products.id", $is_discounted_product_ids);
               });
           }
           $products_upcoming_auction_query->orderBy('bid_start_time', 'ASC');
           $products_upcoming_auction_data_array = $products_upcoming_auction_query->limit(10)->get();

            $products_upcoming_auction_data = productData($products_upcoming_auction_data_array, $time_zone); // defined in helpers

           $products_won_auction_data_list = [];
           if(auth('api')->check())
           {
               $auth_user_id = auth('api')->id();
               $completed_auction_ids = ProductForAuctionOrSale::where('bid_end_time', '<', $current_date_time)->where('type', 'Auction')->where('bid_purchase_status', 0)->pluck('id')->toArray();
               $won_ids = [];
               $variant_ids = [];
               foreach ($completed_auction_ids as $completed_auction_id)
               {
                   $highest_bid = BiddingSummary::where('product_for_auction_or_sale_id', $completed_auction_id)->orderBy('current_bid_amount', 'DESC')->first();
                   if($highest_bid)
                   {
                       if($highest_bid->user_id == $auth_user_id)
                       {
                           $won_ids[] = $highest_bid->product_for_auction_or_sale_id;
                           $variant_ids[] = $highest_bid->product_variant_id;
                       }

                   }
               }
               $products_won_auction_data_list = [];
               foreach ($won_ids as $key12 => $won_id)
               {
                   $products_won_auction_query = ProductForAuctionOrSale::query();
                   $products_won_auction_query->where('status', 1);
                   $products_won_auction_query->where('type', 'Auction');
                   $products_won_auction_query->where('id', $won_id);
                  // $products_won_auction_query->where('bid_purchase_status', 0);
                   $products_won_auction_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
                   $products_won_auction_query->with(['product' => function ($query_9) use($name_array, $variant_ids, $key12) {
                       $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
//                       $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                       $query_9->with([
                           'product_inventory' => function($q) use ($variant_ids, $key12) {
                               $q->where('id','=',$variant_ids[$key12]);
                               $q->with('default_image', 'default_video');
                           }
                       ]);
                   }]);
//                   $products_won_auction_query->whereHas('product', function ($query_2) use($variant_ids, $key12) {
//                       $query_2->whereHas('product_inventory', function ($query_10) use($variant_ids, $key12) {
//                           $query_10->where('product_variants.id', $variant_ids[$key12]);
//                       });
//                   });
                   $products_won_auction_query->orderBy('id', 'DESC');
                   $products_won_auction_data = $products_won_auction_query->limit(1)->get();

                   $products_won_auction_data_detail = productData($products_won_auction_data, $time_zone); // defined in helpers

                   array_push($products_won_auction_data_list, $products_won_auction_data_detail[0]);
               }

           }
           $deal_variant_ids = SpecialPrice::where('start_time', '<', $current_date_time)->where('end_time', '>', $current_date_time)->distinct()->limit(10)->pluck("product_variant_id")->toArray();
           $deal_variant_ids = array_unique($deal_variant_ids);
           $deal_variant_ids = array_unique($deal_variant_ids);
           $products_deal_data_list = [];
           foreach ($deal_variant_ids as $key_20 => $deal_variant_id)
           {
               $product_00 = ProductVariant::where('id', $deal_variant_id)->select('id', 'product_id')->first();
               if($product_00)
               {
                   $product_sale_query = ProductForAuctionOrSale::query();
                   $product_sale_query->where('product_id', $product_00->product_id);
                   $product_sale_query->where('parent_id', NULL)->select('id')->first();
                   $product_sale_query->whereHas('product', function ($query_10) {
                   $query_10->where("products.status", 1);
                   });
                   if(!in_array('can-purchase-sale', getUserPermissions()) && !in_array('can-purchase-bulk', getUserPermissions()))
                   {
                       $product_sale_query->where('type', 'NoType');
                   }
                   elseif(in_array('can-purchase-sale', getUserPermissions()) && !in_array('can-purchase-bulk', getUserPermissions()))
                   {

                       $product_sale_query->whereIn('type', ['Sale']);
                   }
                   elseif(!in_array('can-purchase-sale', getUserPermissions()) && in_array('can-purchase-bulk', getUserPermissions()))
                   {
                       $product_sale_query->whereIn('type', ['Sale', 'Bulk']);
                   }
                   else
                   {
                       $product_sale_query->whereHas('product', function ($query_10) {
                           $query_10->whereIn('products.sale_type', ['Sale', 'Bulk']);
                       });
                   }
                   $product_sale = $product_sale_query->select('id')->first();

                   if($product_sale)
                   {
                       $products_deal_query = ProductForAuctionOrSale::query();
                       $products_deal_query->where('status', 1);
                       $products_deal_query->where('id', $product_sale->id);
                       $products_deal_query->select('id', 'product_id', 'user_id', 'type', 'bid_start_time', 'bid_end_time');
                       $products_deal_query->with(['product' => function ($query_9) use($name_array, $deal_variant_id) {
                           $query_9->select('id', $name_array['name'], $name_array['description'], 'type', 'user_boutique_id', 'most_selling_status', 'is_featured', 'attribute_set_id', 'status', 'brand_id');
                          // $query_9->with('product_inventory.default_image', 'product_inventory.default_video');
                           $query_9->with([
                               'product_inventory' => function($q) use ($deal_variant_id) {
                                     $q->where('id','=',$deal_variant_id);
                                     $q->with('default_image', 'default_video');
                                }
                            ]);
                       }]);

                       $products_deal_query->orderBy('id', 'DESC');
                       $products_deal_data = $products_deal_query->limit(1)->get();

                       $products_deal_data_detail = productData($products_deal_data, $time_zone); // defined in helpers
                       array_push($products_deal_data_list, $products_deal_data_detail[0]);
                   }
               }

           }


           $user_permissions = [];
           $user_package_permissions = [];
           $features  = Feature::where('status', 1)->get();
           foreach ($features as $feature)
           {
               $status = "false";
               $user_package_permissions[$feature->slug] = (string) $status;
           }
           $user_permissions = $user_package_permissions;

           $user_package_permissions = [];
           $package_features = [];
           $package = Package::where('is_default', 1)->first();
           if($package)
           {
               $package_features = $package->features()->pluck('slug')->toArray();
           }
           foreach ($features as $feature)
           {
               $status = "false";
               if(in_array($feature->slug, $package_features))
               {
                   $status = "true";
               }

               $user_package_permissions[$feature->slug] = (string) $status;

           }


           $user_package_status = "";
           $user_type = "";
           $user_package_end_date = "";
           $logged_user_id = 0;
           if(auth('api')->check())
           {
               $user_permissions = auth('api')->user()->getPackageFeatureAppSide();
               $user_package_status = auth('api')->user()->getPackageStatus();
               $user_package_end_date = auth('api')->user()->getPackageEndDate();
               $logged_user_id = auth('api')->id();
           }

           return response()->json(
               [
                   'success' => true,
                   'status' => 200,
                   'message' => __('messages.success'),
                   'message_code' => 'user_home_success',
                   'data' => [
                       'app_banners' => $app_banner_data,
                       'boutiques' => $boutiques_data,
                       'cart_count' => (string) getCartCount(),
                       'categories' => $category_data,
                       'brands' => $brand_data,
                       'upcoming_auctions' => $products_upcoming_auction_data,
                       'deals' => $products_deal_data_list,
                       'live_auctions' => [], // $live_auctions
                       'most_selling_products' => $products_for_sale_most_selling_data,
                       'new_arrival_products' => $products_for_sale_new_arrivals_data,
                       'won_auction_products' => $products_won_auction_data_list,
                       'user_permissions' =>  $user_permissions,
                       'user_package_status' => (string) $user_package_status,
                       'user_package_end_date' => (string) ($user_package_end_date) ? getLocalTimeFromUtc($user_package_end_date, $time_zone):"",
                       'user_profile_frame' => userProfileSlab($logged_user_id),
                       'user_type' => (string) $user_type,
                       'instagram_link' => (string) adminGeneralSettings("INSTAGRAM"),
                       'tiktok_link' => (string) adminGeneralSettings("TIKTOK"),
                       'youtube_link' => (string) adminGeneralSettings("YOUTUBE"),
                       'twitter_link' => (string) adminGeneralSettings("TWITTER"),
                       'snapchat_link' => (string) adminGeneralSettings("SNAPCHAT"),
                       'facebook_link' => (string) adminGeneralSettings("FACEBOOK"),
                       'contact_number' => (string) adminGeneralSettings("CONTACT_NUMBER"),
                       'email' => (string) adminGeneralSettings("EMAIL"),
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
}

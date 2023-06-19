<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use App\Models\BiddingSummary;
use App\Models\BoutiqueCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSearchController extends Controller
{
    public function getSearchSuggestions(Request $request)
    {
        try
        {
            $search_data = [];
            $name_array = [];
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['title'] = "title_ar as name";
                $name_array['search_name'] = "name_ar";
                $name_array['search_title'] = "title_ar";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['title'] = "title";
                $name_array['search_name'] = "name";
                $name_array['search_title'] = "title";
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            $category_query = Category::query();
            $category_query->where('shown_in_app', 1);
            $category_query->select('id', $name_array['name']);
            if(isset($request->q) && $request->q)
            {
                $category_query->where(function ($s_query) use ($request, $name_array){
                    $s_query->where($name_array['search_name'], 'LIKE', '%'.$request->q.'%');
                });
            }
            $category_query->orderBy('sort_order', 'ASC');
            $category_data_array = $category_query->get();
            $category_data = [];
            foreach ($category_data_array as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "name" => $category_data_item->name,
                    "type" => "Category",
                ];
                array_push($category_data, $category_temp_data);
            }

            $search_data = array_merge($search_data, $category_data);

            $boutique_category_query = BoutiqueCategory::query();
            $boutique_category_query->where('status', 1);
            $boutique_category_query->select('id', $name_array['title']);
            if(isset($request->q) && $request->q)
            {
                $boutique_category_query->where(function ($s_query) use ($request, $name_array){
                    $s_query->where($name_array['search_title'], 'LIKE', '%'.$request->q.'%');
                });
            }
            $boutique_category_data_array = $boutique_category_query->get();
            $boutique_category_data = [];
            foreach ($boutique_category_data_array as $key => $boutique_category_data_item)
            {
                $boutique_category_temp_data = [
                    "id" => $boutique_category_data_item->id,
                    "name" => $boutique_category_data_item->title,
                    "type" => "BoutiqueCategory",
                ];
                array_push($boutique_category_data, $boutique_category_temp_data);
            }

            $search_data = array_merge($search_data, $boutique_category_data);

            $brand_query = Brand::query();
            $brand_query->where('status', 1);
            $brand_query->select('id', $name_array['title']);
            if(isset($request->q) && $request->q)
            {
                $brand_query->where(function ($s_query) use ($request, $name_array){
                    $s_query->where($name_array['search_title'], 'LIKE', '%'.$request->q.'%');
                });
            }
            $brand_query->orderBy('sort', 'ASC');
            $brand_data_array = $brand_query->get();
            $brand_data = [];
            foreach ($brand_data_array as $key => $brand_data_item)
            {
                $brand_temp_data = [
                    "id" => $brand_data_item->id,
                    "name" => $brand_data_item->title,
                    "type" => "Brand",
                ];
                array_push($brand_data, $brand_temp_data);
            }

            $search_data = array_merge($search_data, $brand_data);

            $boutique_query = UserBoutique::query();
            $boutique_query->select('id', $name_array['name']);
            $boutique_query->where('status', 1);
            if(boutiqueFeaturedSorting()) $boutique_query->orderBy('is_featured', 'DESC');
            $boutique_query->orderBy('sort_order', 'ASC');
            if(isset($request->q) && $request->q)
            {
                $boutique_query->where(function ($s_query) use ($request, $name_array){
                    $s_query->where($name_array['search_name'], 'LIKE', '%'.$request->q.'%');
                });
            }
            $boutique_query->orderBy('id', 'DESC');
            $boutique_query_data_array = $boutique_query->get();
            $boutiques_data = [];
            foreach ($boutique_query_data_array as $key => $boutique_query_data_item)
            {
                $boutique_temp_data = [
                    "id" => $boutique_query_data_item->id,
                    "name" => (string) $boutique_query_data_item->name,
                    "type" => "Boutique",
                ];
                array_push($boutiques_data, $boutique_temp_data);
            }

            $search_data = array_merge($search_data, $boutiques_data);

            $products_for_sale_most_selling_query = ProductForAuctionOrSale::query();
            $products_for_sale_most_selling_query->where('status', 1);
            $products_for_sale_most_selling_query->select('id', 'product_id');
            $products_for_sale_most_selling_query->with(['product' => function ($query_1) use($name_array) {
                $query_1->select('id', $name_array['name']);
            }]);
            $products_for_sale_most_selling_query->whereHas('product', function ($query_2) use ($request, $name_array) {
                $query_2->where("products.status", 1);
                if(isset($request->q) && $request->q)
                {
                    $query_2->where(function ($s_query) use ($request, $name_array){
                        $s_query->where('products.'.$name_array['search_name'], 'LIKE', '%'.$request->q.'%');
                    });
                }
            });
            $products_for_sale_most_selling_query->orderBy('id', 'DESC');
            $products_for_sale_most_selling_data_array = $products_for_sale_most_selling_query->get();

            $products_data = [];
            foreach ($products_for_sale_most_selling_data_array as $key => $product_query_data_item)
            {
                $product_temp_data = [
                    "id" => $product_query_data_item->id,
                    "name" => (string) $product_query_data_item->product->name,
                    "type" => "Product",
                ];
                array_push($products_data, $product_temp_data);
            }

            $search_data = array_merge($search_data, $products_data);

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_search_success',
                    'data' => [
                        'search_results' => $search_data,
                        'cart_count' => (string) getCartCount(),
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

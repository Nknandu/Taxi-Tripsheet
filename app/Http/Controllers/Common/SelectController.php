<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductForAuctionOrSale;
use App\Models\User;
use Illuminate\Http\Request;

class SelectController extends Controller
{
    public function getSelectCategory(Request $request)
    {
        if(app()->getLocale() == 'ar')
        {
            $name_array['name'] = "name_ar as name";
            $name_array['search_name'] = "name_ar";
        }
        else
        {
            $name_array['name'] = "name";
            $name_array['search_name'] = "name";

        }
            $data_query = Category::query();
            $data_query->select('id', $name_array['name']);
            $data_query->where('shown_in_app', 1);
            if(isset($request->search) && $request->search)
            {
                $search = $request->search;
                $data_query->where($name_array['search_name'], 'LIKE', '%'.$search.'%');
            }
            if(isset($request->tag_id) && $request->tag_id)
            {
                $data_query->orWhere("id", $request->tag_id);
            }
            $categories = $data_query->orderBy('sort_order', 'ASC')->limit(100)->get();
            $category_data = [];
            foreach ($categories as $category)
            {
                $temp = [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
                array_push($category_data, $temp);
            }
        return response()->json($category_data);
    }

    public function getSelectBrand(Request $request)
    {
        if(app()->getLocale() == 'ar')
        {
            $name_array['title'] = "title_ar as title";
            $name_array['search_title'] = "title_ar";
        }
        else
        {
            $name_array['title'] = "title";
            $name_array['search_title'] = "title";

        }
        $data_query = Brand::query();
        $data_query->select('id', $name_array['title']);
        $data_query->where('status', 1);
        if(isset($request->search) && $request->search)
        {
            $search = $request->search;
            $data_query->where($name_array['search_title'], 'LIKE', '%'.$search.'%');
        }
        if(isset($request->tag_id) && $request->tag_id)
        {
            $data_query->orWhere("id", $request->tag_id);
        }
        $brands = $data_query->limit(100)->get();
        $brand_data = [];
        foreach ($brands as $brand)
        {
            $temp = [
                'id' => $brand->id,
                'name' => $brand->title,
            ];
            array_push($brand_data, $temp);
        }
        return response()->json($brand_data);
    }

    public function getSelectProduct(Request $request)
    {
        if(app()->getLocale() == 'ar')
        {
            $name_array['search_name'] = "name_ar";
            $name_array['name'] = "name_ar as name";
        }
        else
        {
            $name_array['search_name'] = "name";
            $name_array['name'] = "name";

        }
        $products_for_sale_most_selling_query = ProductForAuctionOrSale::query();
        $products_for_sale_most_selling_query->where('status', 1);
        $products_for_sale_most_selling_query->select('id', 'product_id');
        $products_for_sale_most_selling_query->with(['product' => function ($query_1) use($name_array) {
            $query_1->select('id', $name_array['name']);
        }]);
        $products_for_sale_most_selling_query->whereHas('product', function ($query_2) use ($request, $name_array) {
            $query_2->where("products.status", 1);
            if(isset($request->search) && $request->search)
            {
                $search = $request->search;
                $query_2->where(function ($s_query) use ($search, $name_array){
                    $s_query->where('products.'.$name_array['search_name'], 'LIKE', '%'.$search.'%');
                });
            }
            if(isset($request->tag_id) && $request->tag_id)
            {
                $query_2->orWhere("id", $request->tag_id);
            }
        });
        $products_for_sale_most_selling_query->orderBy('id', 'DESC');
        $products_for_sale_most_selling_data_array = $products_for_sale_most_selling_query->limit(100)->get();

        $products_data = [];
        foreach ($products_for_sale_most_selling_data_array as $key => $product_query_data_item)
        {
            $product_temp_data = [
                "id" => $product_query_data_item->id,
                "name" => $product_query_data_item->product->name,

            ];
            array_push($products_data, $product_temp_data);
        }
        return response()->json($products_data);
    }

    public function getSelectUser(Request $request)
    {

        $data_query = User::query();
        $data_query->select('id', 'first_name', 'last_name', 'mobile_number', 'user_type');
        if(isset($request->search) && $request->search)
        {
            $search = $request->search;
            $data_query->where('first_name', 'LIKE', '%'.$search.'%');
            $data_query->orWhere('last_name', 'LIKE', '%'.$search.'%');
            $data_query->orWhere('mobile_number', 'LIKE', '%'.$search.'%');
        }
        if(isset($request->user_id) && $request->user_id)
        {
            $data_query->orWhere("id", $request->user_id);
        }
        if(isset($request->type) && $request->type)
        {
            $data_query->where("user_type", $request->type);
        }
        $users = $data_query->limit(100)->get();
        $user_data = [];
        foreach ($users as $user)
        {
            $temp = [
                'id' => $user->id,
                'name' => $user->first_name." ".$user->last_name." || ".__('messages.'.$user->user_type)." || ".$user->mobile_number,
            ];
            array_push($user_data, $temp);
        }
        return response()->json($user_data);
    }

}

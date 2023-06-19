<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserBoutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserVendorController extends Controller
{
    public function getUserVendors(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
                $name_array['description'] = "description_ar as description";
            }
            else
            {
                $name_array['name'] = "name";
                $name_array['description'] = "description";
            }

            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $vendor_query = User::query();
            // $vendor_query->where('status', 1);

            //$vendor_query->orderBy('id', 'DESC');
            $vendor_count_query = clone $vendor_query;
            $vendor_count =  $vendor_count_query->count();
            if(empty($request->get('page'))) $per_page = $vendor_count;
            $vendor_data_array = $vendor_query->paginate($per_page);
            $vendor_data = [];
            foreach ($vendor_data_array->items() as $key => $vendor_data_item)
            {
                $boutique_data = [];
                if($vendor_data_item->boutiques()->count())
                {
                    foreach ($vendor_data_item->boutiques as $key => $boutique_data_item)
                    {
                        $pro_ids = Product::where('user_boutique_id', $boutique_data_item->id)->pluck('id')->toArray();
                        $category_ids = DB::table('product_categories')->whereIn('product_id', $pro_ids)->pluck('category_id')->toArray();
                        $categories = Category::whereIn('id', $category_ids)->where('parent_id', NULL)->pluck($name_array['name'])->implode(', ');
                        $boutique_temp_data = [
                            "id" => $boutique_data_item->id,
                            "name" => (string) $boutique_data_item->name,
                            "description" => $boutique_data_item->description,
                            "categories" => (string) $categories,
                            "is_live" =>  (string) $boutique_data_item->getIsLiveStatus(),
                            "is_featured" => (string) $boutique_data_item->is_featured,
                            "thumb_image" => (string) $boutique_data_item->thumb_image,
                            "original_image" => (string) $boutique_data_item->original_image,
                            "cover_image_thumb" => (string) $boutique_data_item->cover_image_thumb,
                            "cover_image_original" => (string) $boutique_data_item->cover_image_original,
                        ];
                        array_push($boutique_data, $boutique_temp_data);
                    }
                }
                $vendor_temp_data = [
                    "id" => $vendor_data_item->id,
                    "first_name" => (string) $vendor_data_item->first_name,
                    "last_name" => (string) $vendor_data_item->last_name,
                    "mobile_number" => (string) $vendor_data_item->mobile_number,
                    "email" => (string) $vendor_data_item->email,
                    "is_supplier_vendor" =>  (string) $vendor_data_item->is_supplier_vendor,
                    "is_user_vendor" => (string) $vendor_data_item->is_user_vendor,
                    "user_type" => (string) $vendor_data_item->user_type,
                    "package_id" => $vendor_data_item->package_id,
                    "package" => ($vendor_data_item->package_id)?$vendor_data_item->package->getTitle():'',
                    "package_start_date" => ($vendor_data_item->package_id) ? (string) getDbDateFormat($vendor_data_item->package_start_date) : '',
                    "package_end_date" => ($vendor_data_item->package_id) ? (string) getDbDateFormat($vendor_data_item->package_end_date) : '',
                    "profile_image_thumb" => (string) $vendor_data_item->thumb_image,
                    "profile_image_original" => (string) $vendor_data_item->original_image,
                    "boutiques" => $boutique_data,
                ];
                array_push($vendor_data, $vendor_temp_data);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_vendor_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $vendor_data_array->lastPage(),
                            'current_page' => (string) $vendor_data_array->currentPage(),
                            'total_records' => (string) $vendor_data_array->total(),
                            'records_on_current_page' => (string) $vendor_data_array->count(),
                            'record_from' => (string) $vendor_data_array->firstItem(),
                            'record_to' => (string) $vendor_data_array->lastItem(),
                            'per_page' => (string) $vendor_data_array->perPage(),
                        ],
                        "vendors" => $vendor_data,
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
}

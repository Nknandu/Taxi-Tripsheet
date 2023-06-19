<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class UserBrandController extends Controller
{
    public function getUserBrands(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['title'] = "title_ar as title";
            }
            else
            {
                $name_array['title'] = "title";
            }
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $brand_query = Brand::query();
            $brand_query->where('status', 1);
            $brand_query->select('id', $name_array['title'], 'image', 'image_ar', 'sort', 'status');
            $brand_query->orderBy('sort', 'ASC');
            $brand_count_query = clone $brand_query;
            $brand_count =  $brand_count_query->count();
            if(empty($request->get('page'))) $per_page = $brand_count;
            $brand_data_array = $brand_query->paginate($per_page);
            $brand_data = [];
            foreach ($brand_data_array->items() as $key => $brand_data_item)
            {
                $brand_temp_data = [
                    "id" => $brand_data_item->id,
                    "title" => (string) $brand_data_item->title,
                    "thumb_image" => (string) $brand_data_item->thumb_image,
                    "original_image" => (string) $brand_data_item->original_image,
                ];
                array_push($brand_data, $brand_temp_data);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_brand_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $brand_data_array->lastPage(),
                            'current_page' => (string) $brand_data_array->currentPage(),
                            'total_records' => (string) $brand_data_array->total(),
                            'records_on_current_page' => (string) $brand_data_array->count(),
                            'record_from' => (string) $brand_data_array->firstItem(),
                            'record_to' => (string) $brand_data_array->lastItem(),
                            'per_page' => (string) $brand_data_array->perPage(),
                        ],
                        "brands" => $brand_data,
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

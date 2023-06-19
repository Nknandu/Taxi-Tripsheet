<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function Symfony\Component\HttpFoundation\getLocale;

class UserCategoryController extends Controller
{
    public function getUserCategories(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['name'] = "name_ar as name";
            }
            else
            {
                $name_array['name'] = "name";
            }
            $per_page = $request->per_page;
             $per_page = getPerPageLimit($per_page);
            $category_query = Category::query();
            $category_query->where('shown_in_app', 1);
            $category_query->select('id', 'parent_id', $name_array['name'], 'image', 'image_ar');
            $category_query->orderBy('sort_order', 'ASC');
            $category_count_query = clone $category_query;
            $category_count =  $category_count_query->count();
            if(empty($request->get('page'))) $per_page = $category_count;
            $category_data_array = $category_query->paginate($per_page);
            $category_data = [];
            foreach ($category_data_array->items() as $key => $category_data_item)
            {
                $category_temp_data = [
                    "id" => $category_data_item->id,
                    "parent_id" => (string) $category_data_item->parent_id,
                    "name" => (string) $category_data_item->name,
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
                    'message_code' => 'user_category_success',
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
                        "categories" => $category_data,
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

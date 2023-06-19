<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class UserPackageController extends Controller
{
    public function getUserPackages(Request $request)
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
            $package_query = Package::query();
            $package_query->where('status', 1);
            $package_query->where('is_default', 0);
            $package_query->select('id', $name_array['title'], 'image', 'price', 'validity_in_days', 'colour');
            $package_query->orderBy('price', 'ASC');
            $package_data = $package_query->paginate($per_page);

            $package_count_query = clone $package_query;
            $package_count =  $package_count_query->count();
            if(empty($request->get('page'))) $per_page = $package_count;
            $package_data_array = $package_query->paginate($per_page);
            $package_data = [];
            foreach ($package_data_array->items() as $key => $package_data_item)
            {
                $features = $package_data_item->features()->pluck($name_array['title'])->toArray();
                $package_temp_data = [
                    "id" => $package_data_item->id,
                    "title" => (string) $package_data_item->title,
                    "price" => (string) $package_data_item->price,
                    "colour" => (string) $package_data_item->colour,
                   // "validity_in_days" => (string) $package_data_item->validity_in_days,
                    "package" => (string) $package_data_item->package,
                    "thumb_image" => (string) $package_data_item->thumb_image,
                    "original_image" => (string) $package_data_item->original_image,
                    "features" => $features,
                ];
                array_push($package_data, $package_temp_data);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_package_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $package_data_array->lastPage(),
                            'current_page' => (string) $package_data_array->currentPage(),
                            'total_records' => (string) $package_data_array->total(),
                            'records_on_current_page' => (string) $package_data_array->count(),
                            'record_from' => (string) $package_data_array->firstItem(),
                            'record_to' => (string) $package_data_array->lastItem(),
                            'per_page' => (string) $package_data_array->perPage(),
                        ],
                        "packages" => $package_data,
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

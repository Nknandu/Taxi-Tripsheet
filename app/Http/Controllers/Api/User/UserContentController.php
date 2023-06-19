<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserContentController extends Controller
{
    public function getCms(Request $request)
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

            $validator = Validator::make($request->all(), [
                'type' => 'required|string',
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

            $cms_query = Content::query();
            $cms_query->where('status', 1);
            $cms_query->where('type', $request->type);
            $cms_query->select('id', $name_array['title'], $name_array['description'], 'type', 'image', 'image_ar');
            $cms_query->orderBy('id', 'DESC');
            $cms_data_array = $cms_query->first();


            $cms_temp_data = [
                "id" => $cms_data_array->id,
                "title" => (string) $cms_data_array->title,
                "description" => (string) $cms_data_array->description,
                "type" => (string) $cms_data_array->type,
                "thumb_image" => (string) $cms_data_array->thumb_image,
                "original_image" => (string) $cms_data_array->original_image,
            ];

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_cms_success',
                    'data' => [
                        "cms" => $cms_temp_data,
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

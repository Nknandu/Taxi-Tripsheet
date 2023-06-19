<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class UserFaqController extends Controller
{
    public function getFaqs(Request $request)
    {
        try
        {
            if(app()->getLocale() == 'ar')
            {
                $name_array['question'] = "question_ar as question";
                $name_array['answer'] = "answer_ar as answer";
                $where_array['question'] = "question_ar";
                $where_array['answer'] = "answer_ar";
            }
            else
            {
                $name_array['question'] = "question";
                $name_array['answer'] = "answer";
                $where_array['question'] = "answer";
                $where_array['answer'] = "answer";
            }
            $per_page = $request->per_page;
            $per_page = getPerPageLimit($per_page);
            $faq_query = Faq::query();
            $faq_query->where('status', 1);
            if(isset($request->search_key) && $request->search_key)
            {
                $faq_query->where(function ($query_2) use ($request, $where_array){
                    $query_2->where($where_array['question'], 'LIKE', '%' .$request->search_key. '%');
                    $query_2->orWhere($where_array['answer'], 'LIKE', '%' .$request->search_key. '%');
                });
            }
            $faq_query->select('id', 'question', 'question_ar', 'answer', 'answer_ar');
            $faq_query->orderBy('sort_order', 'ASC');
            $faq_count_query = clone $faq_query;
            $faq_count =  $faq_count_query->count();
            if(empty($request->get('page'))) $per_page = $faq_count;
            $faq_data_array = $faq_query->paginate($per_page);
            $faq_data = [];
            foreach ($faq_data_array->items() as $key => $faq_data_item)
            {

                if(app()->getLocale() == 'ar')
                {
                    $faq_temp_data = [
                        "id" => $faq_data_item->id,
                        "question" => (string) ($faq_data_item->question_ar!=null)?$faq_data_item->question_ar:$faq_data_item->question,
                        "answer" => (string) ($faq_data_item->answer_ar!=null)?$faq_data_item->answer_ar:$faq_data_item->answer,
                    ];
                }
                else
                {
                    $faq_temp_data = [
                        "id" => $faq_data_item->id,
                        "question" => (string) $faq_data_item->question,
                        "answer" => (string) $faq_data_item->answer,
                    ];
                }

                array_push($faq_data, $faq_temp_data);
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'user_faq_success',
                    'data' => [
                        'meta' =>[
                            'total_pages' => (string) $faq_data_array->lastPage(),
                            'current_page' => (string) $faq_data_array->currentPage(),
                            'total_records' => (string) $faq_data_array->total(),
                            'records_on_current_page' => (string) $faq_data_array->count(),
                            'record_from' => (string) $faq_data_array->firstItem(),
                            'record_to' => (string) $faq_data_array->lastItem(),
                            'per_page' => (string) $faq_data_array->perPage(),
                        ],
                        "faqs" => $faq_data,
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

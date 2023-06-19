<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BiddingSummary;
use App\Models\Cart;
use App\Models\PaymentMethod;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserStockController extends Controller
{
    public function userCheckStockItems(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'sale_type' => 'required',
                'product_variant_id' => 'required_if:type,==,DirectBuy|nullable|numeric|min:1',
                'id' => 'required_if:type,==,DirectBuy',
                'quantity' => 'required_if:type,==,DirectBuy',
                'address_id' => 'nullable|numeric|min:1',
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

            $promo_code_applied = 0;
            $promo_code_removed = 0;
            return checkStockItems($request, $promo_code_applied, $promo_code_removed);
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

<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\ProductForAuctionOrSale;
use App\Models\ReservedStock;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function proceedToPayment(Request $request)
    {
        $success_url = "/";
        $failed_url = "/";
        if(isset($request->tap_id) && $request->tap_id)
        {
            $charge_id = $request->tap_id;
            $transaction = Transaction::where('charge_request_id', $charge_id)->first();
            if(!$transaction)
            {
                return abort(500);
            }
            $payment_method = PaymentMethod::where('id', $transaction->payment_method_id)->first();
            if(!$payment_method)
            {
               return abort(500);
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.tap.company/v2/charges/'.$charge_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => '%7B%7D=',
                CURLOPT_HTTPHEADER => array(
                    'authorization: Bearer '.env('TAP_SECRET_KEY'),
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if($httpcode == 200)
            {
                $current_date_time = Carbon::now('UTC')->toDateTimeString();
                $response = json_decode($response, true);
                if($response['status'] == "CAPTURED")
                {
                    $transaction->payment_status = $response['status'];
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->transaction_authorization_id = $response['transaction']['authorization_id'];
                    $transaction->reference_track = $response['reference']['track'];
                    $transaction->reference_payment = $response['reference']['payment'];
                    $transaction->reference_gateway = $response['reference']['gateway'];
                    $transaction->receipt_id = $response['receipt']['id'];
                    $transaction->save();
                    $order = Order::where('id', $transaction->order_id)->first();
                    if(!$order)
                    {
                        return abort(500);
                    }
                    $order->payment_status = $response['status'];
                    $order->payment_status_updated_on = $current_date_time;
                    $order->save();

                    $redirect_url = $payment_method->success_url;
                    if($order->checkout_type == "Cart")
                    {
                        $product_for_auction_or_sale_ids = Cart::where('user_id', $transaction->user_id)->pluck('product_for_auction_or_sale_id')->toArray();
                        foreach ($product_for_auction_or_sale_ids as $pro_id)
                        {
                            $sales_count = ProductForAuctionOrSale::where('id', $pro_id)->first()->sales_count + 1;
                            ProductForAuctionOrSale::where('id', $pro_id)->update(['sales_count' => $sales_count, 'updated_at' => $current_date_time]);
                        }
                        Cart::where('user_id', $transaction->user_id)->delete();
                    }
                    else
                    {
                        $product_for_auction_or_sale_ids = OrderDetail::where('order_id', $order->id)->pluck('product_for_auction_or_sale_id')->toArray();
                        foreach ($product_for_auction_or_sale_ids as $pro_id)
                        {
                            $sales_count = ProductForAuctionOrSale::where('id', $pro_id)->first()->sales_count + 1;
                            ProductForAuctionOrSale::where('id', $pro_id)->update(['sales_count' => $sales_count, 'bid_purchase_status' => true, 'purchased_user_id' => $transaction->user_id, 'updated_at' => $current_date_time]);
                        }
                    }
                    DB::table('user_promo_codes')->where('user_id', $transaction->user_id)->delete();
                    sendOrderInvoice($order->id);
                    sendOrderInvoiceVendor($order->id);
                    $push_title_en = "You Got A New Order";
                    $push_message_en = "You Got A New Order";
                    $push_title_ar = "You Got A New Order";
                    $push_message_ar = "You Got A New Order";
                    $push_target = "Vendor";
                    $user_ids = User::where('id', $order->vendor_id)->pluck('id')->toArray();
                    $headingData = [
                        "en" => $push_title_en,
                        "ar" => $push_title_ar,
                    ];

                    $contentData = [
                        "en" => $push_message_en,
                        "ar" => $push_message_ar,
                    ];

                    $pushData = [
                        "name_en" => $push_title_en,
                        "name_ar" => $push_title_ar,
                        "message_en" =>$push_message_en,
                        "message_ar" => $push_message_ar,
                        "target" => "",
                        "target_id" => "",
                    ];
                    sendPushNotifications($push_target, $user_ids, $headingData, $contentData, $pushData);
                }
                else
                {
                    $transaction->payment_status = $response['status'];
                    $transaction->payment_status_updated_on = $current_date_time;
                    $transaction->transaction_authorization_id = "";
                    $transaction->reference_track = $response['reference']['track'];
                    $transaction->reference_payment = $response['reference']['payment'];
                    $transaction->reference_gateway = $response['reference']['gateway'];
                    $transaction->receipt_id = $response['receipt']['id'];
                    $transaction->save();
                    $order = Order::where('id', $transaction->order_id)->first();
                    if(!$order)
                    {
                        return abort(500);
                    }
                    $order->payment_status = "FAILED";
                    $order->payment_status_updated_on = $current_date_time;
                    $order->save();

                    $redirect_url = $payment_method->fail_url;
                }
            }
            else
            {

                return abort(500);
            }

        }
        else
        {
            return abort(500);
        }

        if($transaction->payment_status != "CAPTURED")
        {
            ReservedStock::where('order_id', $transaction->order_id)->delete();
        }

        $redirect_url = $redirect_url."?status=".$transaction->payment_status."&authorization_id=".$transaction->transaction_authorization_id."&track=".$transaction->reference_track."&payment=".$transaction->reference_payment."&transaction=".$transaction->transaction_id."&id=".$transaction->receipt_id;

        return redirect($redirect_url);

    }


    public function paymentFailed(Request $request)
    {
        if(isset($request->tap_id) && $request->tap_id)
        {
            $charge_id = $request->tap_id;
            $transaction = Transaction::where('charge_request_id', $charge_id)->first();
            if($transaction && $transaction->payment_status != "CAPTURED")
            {
                Transaction::where('id', $transaction->id)->update(['payment_status' => 'FAILED']);
                Order::where('id', $transaction->order_id)->update(['payment_status' => 'FAILED']);
                ReservedStock::where('order_id', $transaction->order_id)->delete();
            }
        }
        else
        {
            return abort(500);
        }

    }
}

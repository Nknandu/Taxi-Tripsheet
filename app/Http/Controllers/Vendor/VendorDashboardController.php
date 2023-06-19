<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    public function getVendorDashboard(Request $request)
    {

        $user_id = auth('web')->id();
        $user_boutique_ids = UserBoutique::where('user_id', $user_id)->pluck('id')->toArray();
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $current_date_time = Carbon::now('UTC')->toDateTimeString();
        $current_date_time_local = getLocalTimeFromUtc($current_date_time, $time_zone);
        $year = date('Y', strtotime($current_date_time_local));
        $month = date('m', strtotime($current_date_time_local));
        $current_month = date('M', strtotime($current_date_time_local));
        $current_month_text = __('messages.'.strtolower($current_month));
        $total_days_in_month = date('t', strtotime($current_date_time_local));
        $sale_graph_month_array = [];

        $previous_month_start_date = date('Y-m-01', strtotime($current_date_time_local. ' -1 months'));
        $previous_month_end_date = date('Y-m-t', strtotime($current_date_time_local. ' -1 months'));
        $current_month_start_date = date('Y-m-01', strtotime($current_date_time_local));
        $current_month_end_date = date('Y-m-t', strtotime($current_date_time_local));
        $total_sale_previous_month = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->WhereBetween('created_at', [$previous_month_start_date, $previous_month_end_date])->whereIn('user_boutique_id', $user_boutique_ids)->sum('total_amount');
        $total_sale_this_month  = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->WhereBetween('created_at', [$current_month_start_date, $current_month_end_date])->whereIn('user_boutique_id', $user_boutique_ids)->sum('total_amount');
        $total_sales_this_year  = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->WhereYear('created_at', $year)->whereIn('user_boutique_id', $user_boutique_ids)->sum('total_amount');

        if($total_sale_previous_month && $total_sale_this_month)
        {
            $month_percentage = (($total_sale_this_month * 100) / $total_sale_previous_month) - 100;
        }
        elseif(!$total_sale_previous_month && $total_sale_this_month)
        {
            $month_percentage = 100;
        }
        elseif($total_sale_previous_month && !$total_sale_this_month)
        {
            $month_percentage = -100;
        }
        else
        {
            $month_percentage = 0;
        }

        // $month_percentage = (($total_sale_this_month - $total_sale_previous_month) / $total_sale_previous_month ) * 100;

        for($i=1; $i<=$total_days_in_month; $i++)
        {
            $total_sales_day = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->whereDay('created_at', $i)->WhereMonth('created_at', $month)->WhereYear('created_at', $year)->whereIn('user_boutique_id', $user_boutique_ids)->sum('total_amount');
            $total_sales_day = round($total_sales_day, 2);
            $temp_array = [
                "day" => $i,
                "year" => $year,
                "month" =>  $month,
                "total_sales" => round($total_sales_day, 2)
            ];
            array_push($sale_graph_month_array, $temp_array);
        }

        $year = date('Y', strtotime($current_date_time_local));
        $month_array = [__('messages.jan'), __('messages.feb'), __('messages.mar'), __('messages.apr'), __('messages.may'), __('messages.jun'), __('messages.jul'), __('messages.aug'), __('messages.sep'), __('messages.oct'), __('messages.nov'), __('messages.dec')];
        $sale_graph_year_array = [];
        for($i=1; $i<=12; $i++)
        {
            $total_sales_month = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->whereMonth('created_at', $i)->WhereYear('created_at', $year)->whereIn('user_boutique_id', $user_boutique_ids)->sum('total_amount');
            $user_count = User::whereMonth('created_at', $i)->WhereYear('created_at', $year)->count();
            $temp_array = [
                "month" => $month_array[$i-1],
                "year" =>  date('Y'),
                "total_sales" =>  round($total_sales_month, 2),
                "user_count" =>  $user_count
            ];
            array_push($sale_graph_year_array, $temp_array);
        }

        $new_users_this_month = User::whereBetween('created_at', [$current_month_start_date, $current_month_end_date])->limit(10)->get();
        $new_users_this_month_count = User::whereBetween('created_at', [$current_month_start_date, $current_month_end_date])->count();
        $new_users_this_year = User::whereYear('created_at', $year)->count();

        $popular_boutiques = UserBoutique::where('status', 1)->whereIn('id', $user_boutique_ids)->limit(5)->get();
        $total_deliveries = Order::where('payment_status', 'CAPTURED')->where('order_status', 'Delivered')->whereHas('order_details')->whereIn('user_boutique_id', $user_boutique_ids)->count();
        $total_orders = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->whereIn('user_boutique_id', $user_boutique_ids)->count();
        $delivery_percentage = ($total_orders)?($total_deliveries / $total_orders ) * 100:0;
        $delivery_percentage = round($delivery_percentage, 2);
        $orders = Order::where('payment_status', 'CAPTURED')->whereHas('order_details')->whereIn('user_boutique_id', $user_boutique_ids)->inRandomOrder()->limit(10)->get();

        $in_stock_item_count = Stock::whereIn('user_boutique_id', $user_boutique_ids)->whereHas('product_variant', function ($query_1){
        $query_1->whereNull('product_variants.deleted_at');
         })->count();
        $stocks = Stock::inRandomOrder()->whereIn('user_boutique_id', $user_boutique_ids)->whereHas('product_variant', function ($query_1){
        $query_1->whereNull('product_variants.deleted_at');
        })->limit(10)->get();

        $data = [
            'sale_graph_month_array' => $sale_graph_month_array,
            'total_sale_this_month' => round($total_sale_this_month, 2),
            'total_sale_previous_month' => round($total_sale_previous_month, 2),
            'current_month_text' => $current_month_text,
            'current_year' => $year,
            'month_percentage' => round($month_percentage ,2),
            'sale_graph_year_array' => $sale_graph_year_array,
            'total_sales_this_year' => round($total_sales_this_year,2),
            'new_users_this_month' => $new_users_this_month,
            'new_users_this_month_count' => $new_users_this_month_count,
            'new_users_this_year' => $new_users_this_year,
            'popular_boutiques' => $popular_boutiques,
            'total_deliveries' => $total_deliveries,
            'stocks' => $stocks,
            'in_stock_item_count' => $in_stock_item_count,
            'total_orders' => $total_orders,
            'orders' => $orders,
            'delivery_percentage' => round($delivery_percentage, 2),
        ];
        return view('pages.user.dashboard',$data);
    }
}

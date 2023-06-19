<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BiddingHistory;
use App\Models\BiddingSummary;
use App\Models\ProductForAuctionOrSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class VendorAuctionController extends Controller
{
    public function index(Request $request)
    {

        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $current_date_time = Carbon::now('UTC')->toDateTimeString();

        $auction_query = ProductForAuctionOrSale::query();
        $auction_query->where('status', 1);
        $auction_query->where('type', 'Auction');
        $auction_query->where('user_id', auth('web')->id());
        $auction_query->with(['product' => function ($query_1) {
            $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
        }]);
        if(isset($request->status) && $request->status == "Completed")
        {
            $auction_query->where('bid_end_time', '<', $current_date_time);
            $status_text = __('messages.completed');
        }
        elseif(isset($request->status) && $request->status == "Ongoing")
        {
            $auction_query->where('bid_start_time', '<=', $current_date_time)->where('bid_end_time', '>=', $current_date_time);
            $status_text = __('messages.ongoing');
        }
        elseif(isset($request->status) && $request->status == "Upcoming")
        {
            $auction_query->where('bid_start_time', '>', $current_date_time);
            $status_text = __('messages.upcoming');
        }
        else
        {
            $status_text = __('messages.all_auctions');
        }
        $auction_query->orderBy('bid_start_time', 'ASC');
        $auctions = $auction_query->paginate(10);

        $ongoing_auction =  ProductForAuctionOrSale::where('type', 'Auction')->where('bid_start_time', '<=', $current_date_time)->where('bid_end_time', '>=', $current_date_time)->first();
        $reload_time_in_millisecond = 0;
        if($ongoing_auction)
        {
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $timeFirst  = strtotime($current_date_time);
            $timeSecond = strtotime($ongoing_auction->bid_end_time);
            $reload_time_in_millisecond = ($timeSecond - $timeFirst) * 1000;
        }

        return view('pages.user.auctions.index', compact('auctions', 'reload_time_in_millisecond', 'status_text'));
    }

    public function show($id)
    {
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $current_date_time = Carbon::now('UTC')->toDateTimeString();

        $auction_query = ProductForAuctionOrSale::query();
        $auction_query->where('status', 1);
        $auction_query->where('type', 'Auction');
        $auction_query->where('id', $id);
        $auction_query->with(['product' => function ($query_1) {
            $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
        }]);
        $auction_query->orderBy('bid_start_time', 'ASC');
        $auction = $auction_query->first();
        $reload_time_in_millisecond = 0;
        if($auction)
        {
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $timeFirst  = strtotime($current_date_time);
            $timeSecond = strtotime($auction->bid_end_time);
            $reload_time_in_millisecond = ($timeSecond - $timeFirst) * 1000;
        }
        return view('pages.user.auctions.show', compact('auction', 'reload_time_in_millisecond'));
    }

    public function getParticipants(Request $request)
    {
        if ($request->ajax()) {
            if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
            $current_date_time = Carbon::now('UTC')->toDateTimeString();

            $auction_query = ProductForAuctionOrSale::query();
            $auction_query->where('status', 1);
            $auction_query->where('type', 'Auction');
            $auction_query->where('id', $request->auction_id);
            $auction_query->with(['product' => function ($query_1) {
                $query_1->with('product_inventory.default_image', 'product_inventory.default_video');
            }]);
            $auction_query->orderBy('bid_start_time', 'ASC');
            $auction = $auction_query->first();
            $reload_time_in_millisecond = 0;
            if($auction)
            {
                $current_date_time = Carbon::now('UTC')->toDateTimeString();
                $timeFirst  = strtotime($current_date_time);
                $timeSecond = strtotime($auction->bid_end_time);
                $reload_time_in_millisecond = ($timeSecond - $timeFirst) * 1000;
            }

            $product_variant_id = $auction->product->product_inventory->id;

            $data_query =   BiddingSummary::query();
            $data_query->where('product_for_auction_or_sale_id', $auction->id);
            $data_query->where('product_variant_id', $product_variant_id);
            $data_query->orderBy('current_bid_amount', 'DESC');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('participant', function($row){
                    $participant = '<div class="d-flex align-items-center">
                                        <div class="symbol symbol-50px me-3">
                                            <img src="'.$row->user->thumb_image.'" class="" alt="">
                                        </div>
                                        <div class="d-flex justify-content-start flex-column">
                                            <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$row->user->first_name.' '.$row->user->last_name.'</a>
                                            <span class="text-gray-400 fw-semibold d-block fs-7">'.$row->user->user_type.'</span>
                                        </div>
                                    </div>';

                    return $participant;
                })
                ->addColumn('previous_bid_amount', function($row){
                    $bidding_histories = BiddingHistory::where('user_id', $row->user_id)->where('product_variant_id', $row->product_variant_id)->where('product_for_auction_or_sale_id', $row->product_for_auction_or_sale_id)->orderBy('current_bid_amount', 'DESC')->limit(2)->get();
                    if($bidding_histories->count() == 2)
                    {
                        $previous_amount = $bidding_histories[1]->current_bid_amount;
                    }
                    elseif($bidding_histories->count() == 1)
                    {
                        $previous_amount = $bidding_histories[0]->current_bid_amount;
                    }
                    else
                    {
                        $previous_amount = null;
                    }
                    return $previous_amount." ".__('messages.kwd');
                })
                ->addColumn('current_bid_amount', function($row){

                    return $row->current_bid_amount." ".__('messages.kwd');
                })
                ->addIndexColumn()
                ->rawColumns(['participant', 'current_bid_amount', 'previous_bid_amount'])
                ->make(true);
        }

        return view('pages.user.app_banners.index');
    }
}

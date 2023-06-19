<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Address;
use App\Models\Attribute;
use App\Models\AttributeSet;
use App\Models\AttributeValue;
use App\Models\BiddingSummary;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\ReservedStock;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mail;
use Ladumor\OneSignal\OneSignal;

class TestController extends Controller
{
    public function homePage()
    {
         return view('pages.user.home');
    }

    public function aboutUsPage()
    {
        return view('pages.user.about_us');
    }

    public function pricingPage()
    {
        return view('pages.user.pricing');
    }


    public function kitLibraryPage()
    {
        return view('pages.user.kit_libraries');
    }


    public function kitLibraryDetailPage()
    {
        return view('pages.user.kit_detail');
    }



}

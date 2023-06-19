<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function getAdminDashboard(Request $request)
    {

        return view('pages.admin.dashboard');
    }
}

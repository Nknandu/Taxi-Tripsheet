<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userDashboard(Request $request)
    {
        return view('pages.user.dashboard.dashboard');
    }
    public function userFavorites(Request $request)
    {
        return view('pages.user.dashboard.favorites');
    }
    public function userSettings(Request $request)
    {
        return view('pages.user.dashboard.settings');
    }
}

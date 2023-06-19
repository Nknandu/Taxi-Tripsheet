<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App;

class LanguageController extends Controller
{
    public function changeLanguage(Request $request)
    {
        App::setLocale($request->language);
        session()->put('locale', $request->language);
        return redirect()->back();
    }
}

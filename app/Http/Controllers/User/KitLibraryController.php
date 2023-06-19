<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\KitLibrary;
use App\Models\Package;
use Illuminate\Http\Request;

class KitLibraryController extends Controller
{
    public function getKitLibraryPage(Request $request)
    {
        $categories = Category::select('id', 'title')->orderBy('title', 'ASC')->get();
        $packages = Package::select('id', 'title')->orderBy('price', 'ASC')->get();
        $kit_libraries = KitLibrary::orderBy('id', 'DESC')->paginate(12);

        if($request->ajax())
        {
          return view('pages.user.kit_libraries.kit_libraries_ajax', compact('kit_libraries'));
        }
        return view('pages.user.kit_libraries.kit_libraries',compact('categories', 'packages', 'kit_libraries'));
    }

    public function getKitLibraryDetail(Request $request, $slug)
    {
        $kit_library = KitLibrary::where('slug', $slug)->first();
        if(!$kit_library) return abort(404);
        $similar_kit_libraries = KitLibrary::where('category_id', $kit_library->category_id)->where('kit_type_id', $kit_library->kit_type_id)->limit(3)->get();
       return view('pages.user.kit_libraries.kit_detail', compact('kit_library', 'similar_kit_libraries'));

    }



}

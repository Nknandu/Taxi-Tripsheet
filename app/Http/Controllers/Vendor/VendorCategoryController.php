<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use Image;

class VendorCategoryController extends Controller
{
    public function index()
    {
        unset($_COOKIE['selected_product_ids']);
        return view('pages.user.categories.index');
    }


    public function create(Request $request)
    {
        try
        {
            $categories = Category::orderBy('sort_order', 'ASC')->get();
            $selected_parent = null;
            if(isset($request->selected_parent) && $request->selected_parent) $selected_parent = $request->selected_parent;
            $create_form = view('pages.user.categories.create', compact('categories', 'selected_parent'))->render();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'create_form' => $create_form,
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }

    }
    public function getCategoryProductView(Request $request)
    {
        try
        {
            if(isset($request->selected_parent) && $request->selected_parent)
            {
                $selected_parent = $request->selected_parent;
                $category = Category::where('id', $selected_parent)->first();
                $category_name = $category->getCategoryName();
                $selected_product_ids = DB::table('product_categories')->where('category_id', $request->selected_parent)->distinct()->pluck('product_id')->implode(',');

            }
            else
            {
                $selected_parent = 0;
                $category_name = __('messages.all_category');
                $selected_product_ids = "0";
            }
            $create_form = view('pages.user.categories.category_products', compact('category_name', 'selected_parent', 'selected_product_ids'))->render();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'view_form' => $create_form,
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }

    }


    public function categoryProducts(Request $request)
    {

        if(app()->getLocale() == 'ar')
        {
            $name_array['name'] = "name_ar as name";
        }
        else
        {
            $name_array['name'] = "name";
        }
        if ($request->ajax()) {
            if(isset($_COOKIE['selected_product_ids']))
            {
                $selected_ids = explode(',', $_COOKIE['selected_product_ids']);
            }
            else
            {
                $selected_ids = [];
            }
            $product_ids = [];
            if(isset($request->category_id) && $request->category_id)
            {
                $category = Category::where('id', $request->category_id)->first();
                $product_ids = DB::table('product_categories')->where('category_id', $request->category_id)->distinct()->pluck('product_id')->toArray();
            }
            $selected_ids =  array_merge($selected_ids, $product_ids);
            $selected_ids = array_unique($selected_ids);
            $data_query =   Product::query();
           // $data_query->where('user_id', auth('web'));
            $data_query->orderBy('id', 'DESC');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('name', function($row){
                    return $row->getProductName();
                })
                ->addColumn('category', function($row){
                    return $row->category_list;
                })
                ->addColumn('checkbox', function ($row) use ($selected_ids){
                    if(in_array($row->id, $selected_ids))
                    {
                        $checkbox =  '<input type="checkbox" name="selected_ids[]" class="selected_id" checked value="'.$row->id.'" onclick="handleClick(this)" />';
                    }
                    else
                    {
                        $checkbox =  '<input type="checkbox" name="selected_ids[]" class="selected_id" value="'.$row->id.'" onclick="handleClick(this)" />';
                    }
                    return $checkbox;
                })
                ->addIndexColumn()
                ->rawColumns(['category', 'name', 'checkbox'])
                ->make(true);
        }
    }


    public function store(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:100',
                'name_ar' => 'required|max:100',
                'slug' => 'required|alpha_dash|unique:categories',
                'image' => 'mimes:jpeg,jpg,png,gif',
                'image_ar' => 'mimes:jpeg,jpg,png,gif',
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.validation_error'),
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            $slug = $request->slug;
            $image_name_in_db = null;
            if($request->hasfile('image'))
            {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(100, 100, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/categories/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/categories/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }

            $image_ar_name_in_db = null;
            if($request->hasfile('image_ar'))
            {
                $image_ar = $request->file('image_ar');
                $image_ar_name = $slug."-ar-".time().'.'.$image_ar->extension();
                if(strtolower($image_ar->extension()) == 'gif')
                {
                    $original_image_ar_file = $thumb_image_ar_file = file_get_contents($request->image_ar);
                }
                else
                {
                    $original_image_ar = Image::make($image_ar);
                    $thumb_image_ar = Image::make($image_ar)->fit(100, 100, function ($constraint) { });
                    $original_image_ar_file = $original_image_ar->stream()->__toString();
                    $thumb_image_ar_file = $thumb_image_ar->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/categories/original/'.$image_ar_name, $original_image_ar_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/categories/thumb/'.$image_ar_name, $thumb_image_ar_file, ['visibility' => 'public']))
                    {
                        $image_ar_name_in_db = $image_ar_name;
                    }
                }
            }

            if(isset($request->hide_in_app) && $request->hide_in_app)
            {
                $shown_in_app = false;
            }
            else
            {
                $shown_in_app = true;
            }

            if(isset($request->category_parent) && $request->category_parent != 0)
            {
                $category_parent = $request->category_parent;
            }
            else
            {
                $category_parent = null;
            }

            $category = new Category();
            $category->parent_id = $category_parent;
            $category->name = $request->name;
            $category->name_ar = $request->name_ar;
            $category->slug = $slug;
            $category->shown_in_app = $shown_in_app;
            $category->image = $image_name_in_db;
            $category->image_ar = $image_ar_name_in_db;
            if($category->parent_id)
            {
                $parent_sort_order = Category::where('id', $category_parent)->max('sort_order');
                $category->sort_order = $parent_sort_order + 1;
            }
            else
            {
                $parent_sort_order = Category::max('sort_order');
                $category->sort_order = $parent_sort_order + 1;
            }
            $category->save();
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.create_success'),
                'message_code' => 'created_success',
                'url' => route('user.categories.index')
            ]);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }


    public function show($id)
    {
        return \Request::route()->getName();
    }


    public function edit(Request $request, $id)
    {
        try
        {
            $categories = Category::orderBy('sort_order', 'ASC')->get();
            $category = Category::where('id', $id)->first();
            // foreach ($categories as $category) return $category;
            $edit_form = view('pages.user.categories.edit', compact('categories', 'category'))->render();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'edit_form' => $edit_form,
                    'category' => $category,
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:100',
                'name_ar' => 'required|max:100',
                'slug'=>'required|unique:categories,slug,'.$id,
                'image' => 'mimes:jpeg,jpg,png,gif',
                'image_ar' => 'mimes:jpeg,jpg,png,gif',
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.validation_error'),
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            $category = Category::where('id', $id)->first();

            $slug = $request->slug;
            $image_name_in_db = null;
            if($request->hasfile('image'))
            {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if(strtolower($image->extension()) == 'gif')
                {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                }
                else
                {
                    $original_image = Image::make($image);
                    $thumb_image = Image::make($image)->fit(100, 100, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }

                if(Storage::disk('public')->put('uploads/categories/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/categories/original/'.$category->image))
                    {
                        Storage::disk('public')->delete('uploads/categories/original/'.$category->image);
                    }

                    if(Storage::disk('public')->put('uploads/categories/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/categories/thumb/'.$category->image))
                        {
                            Storage::disk('public')->delete('uploads/categories/thumb/'.$category->image);
                        }
                        $image_name_in_db = $image_name;
                        $category->image = $image_name_in_db;
                    }
                }
            }

            $image_ar_name_in_db = null;
            if($request->hasfile('image_ar'))
            {
                $image_ar = $request->file('image_ar');
                $image_ar_name = $slug."-ar-".time().'.'.$image_ar->extension();
                if(strtolower($image_ar->extension()) == 'gif')
                {
                    $original_image_ar_file = $thumb_image_ar_file = file_get_contents($request->image_ar);
                }
                else
                {
                    $original_image_ar = Image::make($image_ar);
                    $thumb_image_ar = Image::make($image_ar)->fit(100, 100, function ($constraint) { });
                    $original_image_ar_file = $original_image_ar->stream()->__toString();
                    $thumb_image_ar_file = $thumb_image_ar->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/categories/original/'.$image_ar_name, $original_image_ar_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/categories/original/'.$category->image_ar))
                    {
                        Storage::disk('public')->delete('uploads/categories/original/'.$category->image_ar);
                    }

                    if(Storage::disk('public')->put('uploads/categories/thumb/'.$image_ar_name, $thumb_image_ar_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/categories/thumb/'.$category->image_ar))
                        {
                            Storage::disk('public')->delete('uploads/categories/thumb/'.$category->image_ar);
                        }
                        $image_ar_name_in_db = $image_ar_name;
                        $category->image_ar = $image_ar_name_in_db;
                    }
                }
            }
            $shown_in_app = true;
            if(isset($request->hide_in_app) && $request->hide_in_app)
            {
                $shown_in_app = false;
            }

            $category_parent = null;
            if(isset($request->category_parent) && $request->category_parent != 0)
            {
                $category_parent = $request->category_parent;
            }

            $category->parent_id = $category_parent;
            $category->name = $request->name;
            $category->name_ar = $request->name_ar;
            $category->slug = $slug;
            $category->shown_in_app = 1;
            $category->save();

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'update_success',
                'url' => route('user.categories.index')
            ]);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }

    public function updateCategoryProduct(Request $request, $id)
    {
        try
        {
            $category = Category::where('id', $id)->first();
            if(isset($_COOKIE['selected_product_ids']))
            {
                $selected_ids = explode(',', $_COOKIE['selected_product_ids']);
            }
            else
            {
                $selected_ids = [];
            }

            DB::table('product_categories')->where('category_id', $id)->delete();

            $category = Category::where('id', $id)->with('parent')->first();
            $category_array =  $category->getParentIds();

            $product_ids = [];
            foreach($selected_ids as $selected_id)
            {
                if($selected_id)
                {
                    $product_ids[] = $selected_id;
                }
            }

            foreach ($category_array as $category)
            {
                $category = Category::where('id', $category)->first();
                $category->products()->attach($product_ids);
            }


            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => __('messages.update_success'),
                'message_code' => 'update_success',
                'url' => route('user.categories.index')
            ]);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }



    public function destroy($id)
    {
        try
        {
            $category = Category::where('id', $id)->first();
            $category->delete();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.delete_success'),
                    'message_code' => 'success',
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }

    public function getCategoryData(Request $request)
    {
        try
        {
            $assigned_category_ids = [];
            if(isset($request->assigned_category_ids) && $request->assigned_category_ids)
            {
                $assigned_category_ids = explode(',', $request->assigned_category_ids);
            }
            $categories = Category::withCount('sub_categories')->orderBy('sort_order', 'ASC')->get();
            $category_script = view('pages.user.categories.tree', compact('categories', 'assigned_category_ids'))->render();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.success'),
                    'message_code' => 'success',
                    'category_script' => $category_script,
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }

    public function moveCategoryData(Request $request)
    {
        try
        {
            $updated_categories = json_decode($request->categories);
            $current_time = Carbon::now()->toDateTimeString(); // Y-m-d H:i:s
            foreach ($updated_categories as $key => $updated_category)
            {
                $parent_id = null;
                if($updated_category->parent != '#') $parent_id = $updated_category->parent;
                $category = Category::where('id', $updated_category->id)->first();
                if($category)
                {
                    $category->parent_id = $parent_id;
                    $category->sort_order = $updated_category->position;
                    $category->save();
                }
            }

            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.update_success'),
                    'message_code' => 'success',
                    'errors' => []
                ], 200);
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }
}

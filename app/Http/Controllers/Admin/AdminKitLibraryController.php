<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\KitLibrary;
use App\Models\KitLibraryFeature;
use App\Models\KitType;
use App\Models\Package;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Image;

class AdminKitLibraryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data_query = KitLibrary::query();
            $data_query->select('id','title','slug', 'category_id', 'kit_type_id', 'tag_ids', 'status');
            $data = $data_query->get();
            return DataTables::of($data)->addIndexColumn()
                ->addColumn('action', function($row){
                    $action_button = '<a class="btn btn-icon btn-active-light-primary w-30px h-30px" href="'.route('admin.kit_libraries.edit', $row->id).'">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-pencil-square text-warning fs-1"></i>
		                                </span>
                                    </a>';
                    if(1)
                    {
                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px">
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-lock-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                    }
                    else
                    {
                        $action_button .= '<button class="btn btn-icon btn-active-light-primary w-30px h-30px"  onclick="deleteKitLibrary('.$row->id.')" >
                                        <span class="svg-icon svg-icon-3">
                                         <i class="bi bi-trash-fill text-danger fs-1"></i>
		                                </span>
                                    </button>';
                    }

                    return $action_button;
                })
                ->addColumn('status', function($row){
                    if($row->status)
                    {
                        $status = '<span class="badge badge-success fw-bold px-4 py-3">Active</span>';
                    }
                    else
                    {
                        $status = '<span class="badge badge-danger fw-bold px-4 py-3">In Active</span>';
                    }

                    return $status;
                })
                ->addColumn('packages', function($row){
                    return $row->getPackageNames();
                })
                ->addColumn('category', function($row){
                    return $row->category->title;
                })
                ->addColumn('kit_type', function($row){
                    return $row->kit_type->title;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status', 'packages', 'category', 'kit_type'])
                ->make(true);
        }

        return view('pages.admin.kit_libraries.index');
    }


    public function create(Request $request)
    {
        $categories = Category::select('id', 'title')->where('status', 1)->orderBy('title', 'ASC')->get();
        $tags = Tag::select('id', 'title')->where('status', 1)->orderBy('title', 'ASC')->get();
        $packages = Package::select('id', 'title')->where('status', 1)->orderBy('price', 'ASC')->get();
        $kit_types = KitType::select('id', 'title')->where('status', 1)->orderBy('Title', 'ASC')->get();
        return view('pages.admin.kit_libraries.create', compact('categories', 'tags', 'packages', 'kit_types'));

    }


    public function store(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:kit_libraries,title',
                'description' => 'required',
                'preview_link' => 'required|url',
                'category' => 'required',
                'kit_type' => 'required',
                'packages' => 'array|min:1',
                'tags' => 'array|min:1',
                'kit_library_feature' => 'array|min:1',
                'kit_library_feature.*.title' => 'required',
                'kit_library_feature.*.description' => 'required',
                'kit_library_feature.*.icon' => 'required',
                'image' => 'required|mimes:jpeg,jpg,png,gif',
                'cover_image' => 'required|mimes:jpeg,jpg,png,gif',
                'file' => 'required|mimes:json',
            ]);

            if ($validator->fails())
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => 'Validation Errors',
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            $slug = Str::slug($request->title, '-');
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
                    $thumb_image = Image::make($image)->fit(600, 400, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/kit_libraries/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }

            $cover_image_name_in_db = null;
            if($request->hasfile('cover_image'))
            {
                $cover_image = $request->file('cover_image');
                $cover_image_name = $slug."-".time().'.'.$cover_image->extension();
                if(strtolower($cover_image->extension()) == 'gif')
                {
                    $original_cover_image_file = $thumb_cover_image_file = file_get_contents($request->$cover_image);
                }
                else
                {
                    $original_cover_image = Image::make($cover_image);
                    $thumb_cover_image = Image::make($cover_image)->fit(600, 200, function ($constraint) { });
                    $original_cover_image_file = $original_cover_image->stream()->__toString();
                    $thumb_cover_image_file = $thumb_cover_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/original/'.$cover_image_name, $original_cover_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->put('uploads/kit_libraries/thumb/'.$cover_image_name, $thumb_cover_image_file, ['visibility' => 'public']))
                    {
                        $cover_image_name_in_db = $cover_image_name;
                    }
                }
            }

            $file_name_in_db = null;
            if($request->hasfile('file'))
            {
                $file = $request->file('file');
                $random_string_name = Str::random(8);
                $file_name = $slug."-".$random_string_name."-".time().'.'.$file->extension();
                if(strtolower($file->extension()) != 'json')
                {
                    return response()->json([
                        'success' => false,
                        'status_code' => 200,
                        'message' => 'File Type Must Be JSON',
                        'message_code' => 'validation_error_json',
                        'errors' => []
                    ]);
                }
                else
                {
                    $original_file = file_get_contents($request->file);
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/file/'.$file_name, $original_file, ['visibility' => 'public']))
                {
                    $file_name_in_db = $file_name;
                }
            }

            $tags = implode(",", $request->tags);

            $kit_library = new KitLibrary();
            $kit_library->title = $request->title;
            $kit_library->slug = $slug;
            $kit_library->description = $request->description;
            $kit_library->preview_link = $request->preview_link;
            $kit_library->category_id = $request->category;
            $kit_library->kit_type_id = $request->kit_type;
            $kit_library->tag_ids = $tags;
            $kit_library->image = $image_name_in_db;
            $kit_library->cover_image = $cover_image_name_in_db;
            $kit_library->file = $file_name_in_db;
            $kit_library->status = $status;
            $kit_library->created_by_admin_id = Auth::id();
            $kit_library->updated_by_admin_id = Auth::id();
            $kit_library->save();

            $kit_library_features = $request->kit_library_feature;
            foreach ($kit_library_features as $key => $kit_library_feature_item)
            {
                $kit_library_feature = new  KitLibraryFeature();
                $kit_library_feature->kit_library_id = $kit_library->id;
                $kit_library_feature->title = $kit_library_feature_item['title'];
                $kit_library_feature->description = $kit_library_feature_item['description'];
                $kit_library_feature->icon = $kit_library_feature_item['icon'];
                $kit_library_feature->created_by_admin_id = Auth::id();
                $kit_library_feature->updated_by_admin_id = Auth::id();
                $kit_library_feature->save();
            }
            $kit_library->packages()->attach($request->packages);

            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Created Successfully',
                'message_code' => 'created_success',

            ]);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Something Went Wrong',
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
        $categories = Category::select('id', 'title')->where('status', 1)->orderBy('title', 'ASC')->get();
        $tags = Tag::select('id', 'title')->where('status', 1)->orderBy('title', 'ASC')->get();
        $packages = Package::select('id', 'title')->where('status', 1)->orderBy('price', 'ASC')->get();
        $kit_types = KitType::select('id', 'title')->where('status', 1)->orderBy('Title', 'ASC')->get();
        $kit_library = KitLibrary::where('id', $id)->first();
        $assigned_packages_ids = $kit_library->packages()->pluck('packages.id')->toArray();
        $assigned_tags_ids = explode(',', $kit_library->tag_ids);
        return view('pages.admin.kit_libraries.edit', compact( 'kit_library','categories', 'tags', 'packages', 'kit_types', 'assigned_packages_ids', 'assigned_tags_ids'));
    }


    public function update(Request $request, $id)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:kit_libraries,title,'.$id,
                'description' => 'required',
                'preview_link' => 'required|url',
                'category' => 'required',
                'kit_type' => 'required',
                'packages' => 'array|min:1',
                'tags' => 'array|min:1',
                'image' => 'mimes:jpeg,jpg,png,gif',
                'cover_image' => 'mimes:jpeg,jpg,png,gif',
                'file' => 'mimes:json',
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

            $status = false;
            if(isset($request->status) && $request->status == 1)
            {
                $status = true;
            }

            DB::beginTransaction();
            $kit_library = KitLibrary::where('id', $id)->first();


            $slug = Str::slug($request->title, '-');
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
                    $thumb_image = Image::make($image)->fit(600, 400, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/kit_libraries/original/'.$kit_library->image))
                    {
                        Storage::disk('public')->delete('uploads/kit_libraries/original/'.$kit_library->image);
                    }
                    if(Storage::disk('public')->put('uploads/kit_libraries/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/kit_libraries/thumb/'.$kit_library->image))
                        {
                            Storage::disk('public')->delete('uploads/kit_libraries/thumb/'.$kit_library->image);
                        }
                        $image_name_in_db = $image_name;
                        $kit_library->image = $image_name_in_db;
                    }
                }
            }
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
                    $thumb_image = Image::make($image)->fit(600, 400, function ($constraint) { });
                    $original_image_file = $original_image->stream()->__toString();
                    $thumb_image_file = $thumb_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/kit_libraries/original/'.$kit_library->image))
                    {
                        Storage::disk('public')->delete('uploads/kit_libraries/original/'.$kit_library->image);
                    }
                    if(Storage::disk('public')->put('uploads/kit_libraries/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/kit_libraries/thumb/'.$kit_library->image))
                        {
                            Storage::disk('public')->delete('uploads/kit_libraries/thumb/'.$kit_library->image);
                        }
                        $image_name_in_db = $image_name;
                        $kit_library->image = $image_name_in_db;
                    }
                }
            }
            if($request->hasfile('cover_image'))
            {
                $cover_image = $request->file('cover_image');
                $cover_image_name = $slug."-".time().'.'.$cover_image->extension();
                if(strtolower($cover_image->extension()) == 'gif')
                {
                    $original_cover_image_file = $thumb_cover_image_file = file_get_contents($request->cover_image);
                }
                else
                {
                    $original_cover_image = Image::make($cover_image);
                    $thumb_cover_image = Image::make($cover_image)->fit(600, 200, function ($constraint) { });
                    $original_cover_image_file = $original_cover_image->stream()->__toString();
                    $thumb_cover_image_file = $thumb_cover_image->stream()->__toString();
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/original/'.$cover_image_name, $original_cover_image_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/kit_libraries/original/'.$kit_library->cover_image))
                    {
                        Storage::disk('public')->delete('uploads/kit_libraries/original/'.$kit_library->cover_image);
                    }
                    if(Storage::disk('public')->put('uploads/kit_libraries/thumb/'.$cover_image_name, $thumb_cover_image_file, ['visibility' => 'public']))
                    {
                        if(Storage::disk('public')->exists('uploads/kit_libraries/thumb/'.$kit_library->cover_image))
                        {
                            Storage::disk('public')->delete('uploads/kit_libraries/thumb/'.$kit_library->cover_image);
                        }
                        $cover_image_name_in_db = $cover_image_name;
                        $kit_library->cover_image = $cover_image_name_in_db;
                    }
                }
            }

            if($request->hasfile('file'))
            {
                $file = $request->file('file');
                $file_name = $slug."-".time().'.'.$file->extension();
                if(strtolower($file->extension()) != 'json')
                {
                    return response()->json([
                        'success' => false,
                        'status_code' => 200,
                        'message' => 'File Type Must Be JSON',
                        'message_code' => 'validation_error_json',
                        'errors' => []
                    ]);
                }
                else
                {
                    $original_file = file_get_contents($request->file);
                }
                if(Storage::disk('public')->put('uploads/kit_libraries/file/'.$file_name, $original_file, ['visibility' => 'public']))
                {
                    if(Storage::disk('public')->exists('uploads/kit_libraries/file/'.$kit_library->file))
                    {
                        Storage::disk('public')->delete('uploads/kit_libraries/file/'.$kit_library->file);
                    }
                    $file_name_in_db = $file_name;
                    $kit_library->file = $file_name_in_db;
                }
            }
            $tags = implode(",", $request->tags);

            $kit_library->title = $request->title;
            $kit_library->slug = $slug;
            $kit_library->description = $request->description;
            $kit_library->preview_link = $request->preview_link;
            $kit_library->category_id = $request->category;
            $kit_library->kit_type_id = $request->kit_type;
            $kit_library->tag_ids = $tags;
            $kit_library->status = $status;
            $kit_library->updated_by_admin_id = Auth::id();
            $kit_library->save();

            if(isset($request->kit_library_feature) && $request->kit_library_feature)
            {
                $kit_library_features = $request->kit_library_feature;
                foreach ($kit_library_features as $key => $kit_library_feature_item)
                {
                    if(is_array($kit_library_feature_item))
                    {
                        if($kit_library_feature_item['title'])
                        {
                            $kit_library_feature = new  KitLibraryFeature();
                            $kit_library_feature->kit_library_id = $kit_library->id;
                            $kit_library_feature->title = $kit_library_feature_item['title'];
                            $kit_library_feature->description = $kit_library_feature_item['description'];
                            $kit_library_feature->icon = $kit_library_feature_item['icon'];
                            $kit_library_feature->updated_by_admin_id = Auth::id();
                            $kit_library_feature->save();
                        }

                    }
                }
            }

            $kit_library_feature_id = $request->kit_library_feature_id;
            foreach ($kit_library_feature_id as $key => $value_id)
            {
                $kit_library_feature = KitLibraryFeature::where('id', $value_id)->first();
                if($kit_library_feature)
                {
                    $kit_library_feature->title = $request->kit_library_feature_title[$key];
                    $kit_library_feature->description = $request->kit_library_feature_description[$key];
                    $kit_library_feature->icon = $request->kit_library_feature_icon[$key];
                    $kit_library_feature->updated_by_admin_id = Auth::id();
                    $kit_library_feature->save();
                }

            }
            $kit_library->packages()->sync($request->packages);
            DB::commit();
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Updated Successfully',
                'message_code' => 'updated_success',
            ]);

        }
        catch (\Exception $exception)
        {
            return $exception;
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Something Went Wrong',
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
            $kit_library = KitLibrary::where('id', $id)->first();
            $kit_library->delete();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Deleted Successfully',
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
                    'message' => 'Something Went Wrong',
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }

    public function destroyKitLibraryFeature($id)
    {
        try
        {
            $kit_library_feature = KitLibraryFeature::where('id', $id)->first();
            $kit_library_feature->delete();
            return response()->json(
                [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Deleted Successfully',
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
                    'message' => 'Something Went Wrong',
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }
    }
}

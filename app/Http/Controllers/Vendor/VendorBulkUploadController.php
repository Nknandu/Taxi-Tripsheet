<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Imports\ProductImport;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Models\ProductVariantVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Image;
class VendorBulkUploadController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.user.bulk_uploads.index');
    }

    public function bulkImportExcel(Request $request)
    {
        try
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:xlsx',
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

            $productData = $request->file('file');
            Excel::import(new ProductImport,$request->file('file'));
            $bulk_error_array = Session::get('bulk_error_array');
            $success_count = Session::get('bulk_success_count');
            $failed_count = Session::get('bulk_failed_count');
            Session::forget('bulk_error_array');
            if(empty($bulk_error_array))
            {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.import_success'),
                    'message_code' => 'import_success',
                ]);
            }
            else
            {
                $error_display_table = view('pages.user.bulk_uploads.error_sheet', compact('bulk_error_array', 'success_count', 'failed_count'))->render();
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.import_failed'),
                    'errors' => '',
                    'error_display_table' => $error_display_table,
                    'message_code' => 'import_failed',
                ]);
            }


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
    public function bulkImageUpload(Request $request)
    {
        try
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('upload_max_filesize', '50M');
            ini_set('post_max_size', '50M');
            $validator = Validator::make($request->all(), [
                'images' => 'required',
                'images.*' => 'required|mimes:jpeg,jpg,png,gif',
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
            $images = $request->file('images');
            if(!empty($images))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($images as $key=>$image)
                {
                    $upload_name = $image->getClientOriginalName();
                    $fileName = pathinfo($upload_name,PATHINFO_FILENAME);
                    $upload_name_array = explode('-', $fileName);
                    $product_sku = $upload_name_array[0];
                    $product_variant = ProductVariant::where('sku', $product_sku)->first();
                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $image_name = $slug."-".$key.time().'.'.$image->extension();
                        $image_name_in_db = null;
                        if(strtolower($image->extension()) == 'gif')
                        {
                            $original_image_file = $thumb_image_file = file_get_contents($request->image);
                        }
                        else
                        {
                            $original_image = Image::make($image);
                            $thumb_image = Image::make($image)->fit(500, 500, function ($constraint) { });
                            $original_image_file = $original_image->stream()->__toString();
                            $thumb_image_file = $thumb_image->stream()->__toString();
                        }
                        if(Storage::disk('public')->put('uploads/product_variant_images/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                        {
                            if(Storage::disk('public')->put('uploads/product_variant_images/thumb/'.$image_name, $thumb_image_file, ['visibility' => 'public']))
                            {
                                $image_name_in_db = $image_name;
                            }
                        }

                        if($image_name_in_db)
                        {
                            $is_default_count = ProductVariantImage::where('is_default', 1)->where('product_variant_id', $product_variant->id)->count();
                            if($is_default_count)
                            {
                                $is_default = false;
                            }
                            else
                            {
                                $is_default = true;
                            }
                            $product_variant_image = new ProductVariantImage();
                            $product_variant_image->product_variant_id = $product_variant->id;
                            $product_variant_image->image = $image_name_in_db;
                            $product_variant_image->is_default = $is_default;
                            $product_variant_image->created_by_user_id = auth('web')->id();
                            $product_variant_image->updated_by_user_id = auth('web')->id();
                            $product_variant_image->save();
                            $success_count = $success_count + 1;
                        }
                    }
                    else
                    {
                        $temp_error_array = [
                            "index" => $key + 1,
                            "file_name" => $upload_name,
                            "message" => __('messages.no_product_found_with_given_sku'),
                            "message_code" => "no_product_found_with_given_sku",
                        ];
                        array_push($error_array, $temp_error_array);
                        $failed_count = $failed_count + 1;
                    }

                }
            }

            if(empty($error_array))
            {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.import_success'),
                    'message_code' => 'import_success',
                ]);
            }
            else
            {
                $error_display_table = view('pages.user.bulk_uploads.error_sheet_image', compact('error_array', 'success_count', 'failed_count'))->render();
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.upload_failed'),
                    'errors' => '',
                    'error_display_table' => $error_display_table,
                    'message_code' => 'upload_failed',
                ]);
            }

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


    public function bulkVideoUpload(Request $request)
    {
        try
        {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('upload_max_filesize', '50M');
            ini_set('post_max_size', '50M');
            $validator = Validator::make($request->all(), [
                'videos' => 'required',
                'videos.*' => 'required|mimes:mp4,ogx,oga,ogv,ogg,webm',
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
            $videos = $request->file('videos');
            if(!empty($videos))
            {
                $error_array = [];
                $success_count = 0;
                $failed_count = 0;
                foreach ($videos as $key=>$video)
                {
                    $upload_name = $video->getClientOriginalName();
                    $fileName = pathinfo($upload_name,PATHINFO_FILENAME);
                    $upload_name_array = explode('-', $fileName);
                    $product_sku = $upload_name_array[0];
                    $product_variant = ProductVariant::where('sku', $product_sku)->first();
                    if($product_variant)
                    {
                        $slug = Str::slug($product_variant->product->name, '-');
                        $image_name = $slug."-".$key.time().'.'.$video->extension();
                        $video_name_in_db = null;
                        $video_name = $slug."-video-".$key.time().'.'.$video->extension();
                        if(Storage::disk('public')->put('uploads/product_variant_videos/'.$video_name, file_get_contents($video), ['visibility' => 'public']))
                        {
                            $video_name_in_db = $video_name;
                        }

                        if($video_name_in_db)
                        {
                            $is_default_count = ProductVariantVideo::where('is_default', 1)->where('product_variant_id', $product_variant->id)->count();
                            if($is_default_count)
                            {
                                $is_default = false;
                            }
                            else
                            {
                                $is_default = true;
                            }
                            $product_variant_video = new ProductVariantVideo();
                            $product_variant_video->product_variant_id = $product_variant->id;
                            $product_variant_video->video = $video_name_in_db;
                            $product_variant_video->is_default = $is_default;
                            $product_variant_video->created_by_user_id = auth('web')->id();
                            $product_variant_video->updated_by_user_id = auth('web')->id();
                            $product_variant_video->save();
                            $success_count = $success_count + 1;
                        }
                    }
                    else
                    {
                        $temp_error_array = [
                            "index" => $key + 1,
                            "file_name" => $upload_name,
                            "message" => __('messages.no_product_found_with_given_sku'),
                            "message_code" => "no_product_found_with_given_sku",
                        ];
                        array_push($error_array, $temp_error_array);
                        $failed_count = $failed_count + 1;
                    }

                }
            }

            if(empty($error_array))
            {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => __('messages.upload_success'),
                    'message_code' => 'import_success',
                ]);
            }
            else
            {
                $error_display_table = view('pages.user.bulk_uploads.error_sheet_image', compact('error_array', 'success_count', 'failed_count'))->render();
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.upload_failed'),
                    'errors' => '',
                    'error_display_table' => $error_display_table,
                    'message_code' => 'upload_failed',
                ]);
            }

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

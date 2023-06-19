<?php

namespace App\Imports;

use App\Models\AttributeSet;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductForAuctionOrSale;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\UserBoutique;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendorProductImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        Session::forget('bulk_error_array');
        Session::forget('success_count');
        Session::forget('failed_count');
        $current_date_time = Carbon::now('UTC')->toDateTimeString();
        if(isset($_COOKIE["time_zone"])){ $time_zone = getLocalTimeZone($_COOKIE["time_zone"]); } else {$time_zone = getLocalTimeZone(); }
        $error_array = [];
        $failed_count = 0;
        $success_count = 0;
        $row_count = 0;
        foreach ($rows as $key => $row)
        {
            $row_valid = 1;
            $parent_product_status = 0;
            if($row->filter()->isNotEmpty())
            {
                $row_count = $row_count + 1;
                if($row['product_sku'])
                {
                    $product_variant = ProductVariant::where('sku', $row['product_sku'])->count();
                    if($product_variant)
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "B",
                            "column_name" => "product_sku",
                            "message" => __('messages.product_sku_already_exists'),
                            "message_code" => "product_sku_already_exists",
                        ];
                        array_push($error_array, $temp_error_array);
                    }
                }
                else
                {
                    $row_valid = 0;
                    $temp_error_array = [
                        "row_index" => $key + 1 + 1,
                        "column_index" => "B",
                        "column_name" => "product_sku",
                        "message" => __('messages.product_sku_required'),
                        "message_code" => "product_sku_required",
                    ];
                    array_push($error_array, $temp_error_array);
                }


                $product_attribute_ids = [];
                if($row['product_attribute_values'])
                {
                    $attribute_value_array = explode(',', $row['product_attribute_values']);
                    $attribute_value_ids = [];
                    $product_attribute_ids = [];
                    foreach ($attribute_value_array as $attribute_value_array_item)
                    {
                        $attribute_value_check = AttributeValue::where('code', $attribute_value_array_item)->first();
                        if($attribute_value_check)
                        {
                            $attribute_value_ids[] = $attribute_value_check->id;
                            $product_attribute_ids[] = $attribute_value_check->attribute->id;
                        }
                        else
                        {
                            $row_valid = 0;
                            $attributes_status = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "G",
                                "column_name" => "product_attribute_values",
                                "message" => __('messages.product_attribute_value_not_valid'),
                                "message_code" => "product_attribute_value_not_valid",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                    }
                }
                else
                {
                    $row_valid = 0;
                    $temp_error_array = [
                        "row_index" => $key + 1 + 1,
                        "column_index" => "G",
                        "column_name" => "product_attribute_values",
                        "message" => __('messages.product_attribute_values_required'),
                        "message_code" => "product_attribute_values_required",
                    ];
                    array_push($error_array, $temp_error_array);
                }

                if($row['new_arrival_start_time'])
                {

                }
                else
                {
//                       $row_valid = 0;
//                       $temp_error_array = [
//                           "row_index" => $key + 1 + 1,
//                           "column_index" => "AA",
//                           "column_name" => "new_arrival_start_time",
//                           "message" => __('messages.new_arrival_start_time_required'),
//                           "message_code" => "new_arrival_start_time_required",
//                       ];
//                       array_push($error_array, $temp_error_array);
                }

                if($row['new_arrival_end_time'])
                {

                }
                else
                {
//                       $row_valid = 0;
//                       $temp_error_array = [
//                           "row_index" => $key + 1 + 1,
//                           "column_index" => "AB",
//                           "column_name" => "new_arrival_end_time",
//                           "message" => __('messages.new_arrival_end_time_required'),
//                           "message_code" => "new_arrival_end_time_required",
//                       ];
//                       array_push($error_array, $temp_error_array);
                }

                if($row['new_arrival_start_time'] && $row['new_arrival_end_time'])
                {

                    $new_arrival_start_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['new_arrival_start_time'], $time_zone))->toDateTimeString());
                    $new_arrival_end_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['new_arrival_end_time'], $time_zone))->toDateTimeString());
                    if($new_arrival_start_time > $new_arrival_end_time)
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "AB",
                            "column_name" => "new_arrival_end_time",
                            "message" => __('messages.new_arrival_end_time_must_be_grater_than_new_arrival_start_time'),
                            "message_code" => "new_arrival_end_time_must_be_grater_than_new_arrival_start_time",
                        ];
                    }
                }

                if($row['most_selling_status'])
                {
                    $most_selling_status = 1;
                }
                else
                {
                    $most_selling_status = 0;
                }

                if($row['status'])
                {
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }

                if($row['parent_sku'])
                {

                    $parent_product_variant = ProductVariant::where('sku', $row['parent_sku'])->first();

                    if($parent_product_variant)
                    {

                        if($parent_product_variant->product->type != "Group")
                        {
                            $row_valid = 0;
                            $parent_product_status = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "A",
                                "column_name" => "parent_sku",
                                "message" => __('messages.parent_sku_type_not_group'),
                                "message_code" => "parent_sku_type_not_group",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                        else
                        {
                            $parent_product_status = 1;
                        }

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "A",
                            "column_name" => "parent_sku",
                            "message" => __('messages.parent_sku_not_valid'),
                            "message_code" => "parent_sku_not_valid",
                        ];
                        array_push($error_array, $temp_error_array);
                    }
                }


                if(!$parent_product_status)
                {
                    $boutique_status = 1;
                    if($row['boutique'])
                    {
                        $boutique_id = str_replace("BTQ", "", $row['boutique']);
                        $boutique = UserBoutique::where('id', $boutique_id)->first();
                        if(!$boutique)
                        {
                            $boutique_status = 0;
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "C",
                                "column_name" => "boutique",
                                "message" => __('messages.boutique_not_exists'),
                                "message_code" => "boutique_not_exists",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "C",
                            "column_name" => "boutique",
                            "message" => __('messages.boutique_required'),
                            "message_code" => "boutique_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['brand'])
                    {
                        $brand = Brand::where('unique_code', $row['brand'])->first();
                        if(!$brand)
                        {
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "I",
                                "column_name" => "brand",
                                "message" => __('messages.brand_not_exists'),
                                "message_code" => "brand_not_exists",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                        else
                        {
                            $brand_id = $brand->id;
                        }

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "I",
                            "column_name" => "brand",
                            "message" => __('messages.brand_required'),
                            "message_code" => "brand_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['product_type'])
                    {
                        if(!in_array($row['product_type'], ['Simple', 'Group']))
                        {
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "D",
                                "column_name" => "product_type",
                                "message" => __('messages.product_type_not_match'),
                                "message_code" => "product_type_not_match",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "D",
                            "column_name" => "product_type",
                            "message" => __('messages.product_type_required'),
                            "message_code" => "product_type_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['sale_type'])
                    {
                        if(!in_array($row['sale_type'], ['Sale', 'Auction', 'Bulk']))
                        {
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "E",
                                "column_name" => "sale_type",
                                "message" => __('messages.sale_type_not_match'),
                                "message_code" => "sale_type_not_match",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "E",
                            "column_name" => "sale_type",
                            "message" => __('messages.sale_type_required'),
                            "message_code" => "sale_type_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['attribute_set'])
                    {
                        $attribute_set_code = $row['attribute_set'];
                        $attribute_set = AttributeSet::where('code', $attribute_set_code)->first();
                        if(!$attribute_set)
                        {
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "F",
                                "column_name" => "attribute_set",
                                "message" => __('messages.attribute_set_not_exists'),
                                "message_code" => "attribute_set_not_exists",
                            ];
                            array_push($error_array, $temp_error_array);
                        }
                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "F",
                            "column_name" => "attribute_set",
                            "message" => __('messages.attribute_set_required'),
                            "message_code" => "attribute_set_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['product_name'])
                    {

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "J",
                            "column_name" => "product_name",
                            "message" => __('messages.product_name_required'),
                            "message_code" => "product_name_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }
                    if($row['product_name_ar'])
                    {

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "K",
                            "column_name" => "product_name_ar",
                            "message" => __('messages.product_name_ar_required'),
                            "message_code" => "product_name_ar_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }


                    if($row['description'])
                    {

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "L",
                            "column_name" => "description",
                            "message" => __('messages.description_required'),
                            "message_code" => "description_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['description_ar'])
                    {

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "M",
                            "column_name" => "description_ar",
                            "message" => __('messages.description_ar_required'),
                            "message_code" => "description_ar_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    if($row['product_categories'])
                    {
                        $category_array = explode(',', $row['product_categories']);
                        $category_ids = [];
                        foreach ($category_array as $category_array_item)
                        {
                            $category_check = Category::where('slug', $category_array_item)->first();
                            if($category_check)
                            {
                                $category_ids[] = $category_check->id;
                            }
                            else
                            {
                                $row_valid = 0;
                                $temp_error_array = [
                                    "row_index" => $key + 1 + 1,
                                    "column_index" => "H",
                                    "column_name" => "product_categories",
                                    "message" => __('messages.product_categories_not_valid'),
                                    "message_code" => "product_categories_not_valid",
                                ];
                            }

                        }

                    }
                    else
                    {
                        $row_valid = 0;
                        $temp_error_array = [
                            "row_index" => $key + 1 + 1,
                            "column_index" => "H",
                            "column_name" => "product_categories",
                            "message" => __('messages.product_categories_required'),
                            "message_code" => "product_categories_required",
                        ];
                        array_push($error_array, $temp_error_array);
                    }

                    $sale_type = $row['sale_type'];
                    $product_type = $row['product_type'];

                }
                else
                {
                    $boutique = $parent_product_variant->product->boutique;
                    $sale_type = $parent_product_variant->product->sale_type;
                    $product_type = $parent_product_variant->product->type;
                }


                if($row_valid)
                {
                    $user_boutique = $boutique;
                    $user_vendor = $user_boutique->user;
                    $user_package_features = $user_vendor->getPackageFeatures();

//                        $sale_permission = false;
//                        if($sale_type == "Sale")
//                        {
//                            if (in_array('manage-sale', $user_package_features)) $sale_permission = true;
//                        }
//                        elseif($sale_type == "Auction")
//                        {
//                            if(in_array('manage-auction', $user_package_features)) $sale_permission = true;
//                        }
//                        elseif($sale_type == "Bulk")
//                        {
//                            if(in_array('manage-bulk', $user_package_features)) $sale_permission = true;
//                        }
//                        else
//                        {
//
//                        }

                    if($sale_type == "Auction")
                    {
                        if($product_type != "Simple")
                        {
                            $row_valid = 0;
                            $sale_permission = false;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "D",
                                "column_name" => "product_type",
                                "message" => __('messages.simple_auction_product'),
                                "message_code" => "simple_auction_product",
                            ];
                            array_push($error_array, $temp_error_array);
                        }

                    }

//                        if($user_vendor->getPackageStatus() !='active')
//                        {
//                            $sale_permission = false;
//                            $row_valid = 0;
//                            $temp_error_array = [
//                                "row_index" => $key + 1 + 1,
//                                "column_index" => "C",
//                                "column_name" => "boutique",
//                                "message" => __('messages.no_package_available'),
//                                "message_code" => "no_package_available",
//                            ];
//                            array_push($error_array, $temp_error_array);
//                        }

                    if(1) // if($sale_permission)
                    {
                        DB::beginTransaction();
                        $slug = Str::slug($row['product_name'], '-');
                        if(!$parent_product_status)
                        {
                            $product = new Product();
                            $product->name = $row['product_name'];
                            $product->name_ar = $row['product_name_ar'];
                            $product->type = $row['product_type'];
                            $product->sale_type = $row['sale_type'];
                            $product->user_boutique_id = $boutique->id;
                            $product->user_id = $user_boutique->user_id;
                            $product->brand_id = $brand_id;
                            $product->attribute_set_id = $attribute_set->id;
                            $product->slug = $slug;
                            $product->description = $row['description'];
                            $product->description_ar = $row['description_ar'];
                            if($row['new_arrival_start_time'] && $row['new_arrival_end_time'])
                            {
                                $new_arrival_start_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['new_arrival_start_time'], $time_zone))->toDateTimeString());
                                $new_arrival_end_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['new_arrival_end_time'], $time_zone))->toDateTimeString());
                                $product->new_arrival_start_time = $new_arrival_start_time;
                                $product->new_arrival_end_time = $new_arrival_end_time;
                            }
                            $product->status = $status;
                            $product->most_selling_status = $most_selling_status;
                            $product->created_by_user_id = auth('web')->id();
                            $product->updated_by_user_id = auth('web')->id();
                            $product->save();
                            $category_array = [];
                            foreach ($category_ids as $category)
                            {
                                $category = Category::where('id', $category)->with('parent')->firstOrFail();
                                $category_array =  array_merge($category_array, $category->getParentIds());
                            }
                            $product->categories()->attach($category_array);
                        }
                        else
                        {
                            $product = $parent_product_variant->product;
                        }
                        $attributes_status = 1;
                        $product_attribute_set = AttributeSet::where('id', $product->attribute_set_id)->first();
                        $product_attributes_count = $product_attribute_set->attributes->count();

                        $product_attributes =  $product_attribute_ids;

                        if($product_attributes_count && $attributes_status)
                        {

                            $attributes_value_status = 1;
                            if(count($product_attribute_ids) != $product_attributes_count)
                            {
                                $attributes_value_status = 0;
                            }

                            if(!$attributes_value_status)
                            {
                                $row_valid = 0;
                                $attributes_status = 0;
                                $temp_error_array = [
                                    "row_index" => $key + 1 + 1,
                                    "column_index" => "G",
                                    "column_name" => "product_attribute_values",
                                    "message" => __('messages.product_attribute_value_not_valid2'),
                                    "message_code" => "product_attribute_value_not_valid",
                                ];
                                array_push($error_array, $temp_error_array);
                            }

                        }

                        if($attributes_status)
                        {
                            // $product_attributes =  $product_attribute_set->attributes()->pluck('attributes.id')->toArray();
                            $attribute_values = $attribute_value_ids;
                            $combination_exists = false;
                            if($parent_product_status)
                            {
                                foreach ($product->product_variants as $product_variant_item)
                                {
                                    $product_attribute_value_query = DB::table('product_attributes');
                                    $product_attribute_value_query->where('product_id', $product->id);
                                    $product_attribute_value_query->where('product_variant_id', $product_variant_item->id);
                                    $product_attribute_value_query->select('attribute_value_id', 'attribute_id', 'product_variant_id', 'product_id');
                                    $product_attribute_value_query->where(function ($query_1) use($product, $product_variant_item, $product_attributes, $attribute_values){
                                        foreach ($product_attributes as $key => $product_attribute)
                                        {
                                            if($key == 0)
                                            {
                                                $query_1->where(function ($query_2) use($product_attributes, $attribute_values, $key){
                                                    $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                                                });
                                            }
                                            else
                                            {
                                                $query_1->orWhere(function ($query_2) use($product_attributes, $attribute_values, $key){
                                                    $query_2->where('attribute_id', $product_attributes[$key])->where('attribute_value_id', $attribute_values[$key]);
                                                });
                                            }
                                        }
                                    });
                                    $product_attribute_value_count = $product_attribute_value_query->count();
                                    if($product_attribute_value_count >= $product_attributes_count)
                                    {
                                        $combination_exists = true; // combination exists
                                        break;
                                    }
                                }
                            }

                            if(!$combination_exists)
                            {
                                $cost_status = 1;
                                if($user_vendor->beiaat_handled)
                                {
                                    $cost_status = 0;
                                    if($row['cost'])
                                    {
                                        $cost_status = 1;
                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "Q",
                                            "column_name" => "cost",
                                            "message" => __('messages.cost_required'),
                                            "message_code" => "cost_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                }


                                $product_type_status = 1;

                                if($product->sale_type == 'Auction' || $product->sale_type == 'Bulk' || $product->sale_type == 'Sale')
                                {
                                    if($row['quantity'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "N",
                                            "column_name" => "quantity",
                                            "message" => __('messages.quantity_required'),
                                            "message_code" => "quantity_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }
                                }

                                if($product->sale_type == 'Auction')
                                {
                                    if($row['bid_value'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "V",
                                            "column_name" => "bid_value",
                                            "message" => __('messages.bid_value_required'),
                                            "message_code" => "bid_value_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['bid_start_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "U",
                                            "column_name" => "bid_start_price",
                                            "message" => __('messages.bid_start_price_required'),
                                            "message_code" => "bid_start_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['bid_start_time'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "Y",
                                            "column_name" => "bid_start_time",
                                            "message" => __('messages.bid_start_time_required'),
                                            "message_code" => "bid_start_time_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['bid_end_time'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "Z",
                                            "column_name" => "bid_end_time",
                                            "message" => __('messages.bid_end_time_required'),
                                            "message_code" => "bid_end_time_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['bid_end_time'] && $row['bid_start_time'])
                                    {
                                        $bid_start_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['bid_start_time'], $time_zone))->toDateTimeString());
                                        $bid_end_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['bid_end_time'], $time_zone))->toDateTimeString());
                                        if($bid_start_time > $bid_end_time)
                                        {
                                            $row_valid = 0;
                                            $product_type_status = 0;
                                            $temp_error_array = [
                                                "row_index" => $key + 1 + 1,
                                                "column_index" => "Z",
                                                "column_name" => "bid_end_time",
                                                "message" => __('messages.bid_end_time_must_be_greater_than_bid_start_time'),
                                                "message_code" => "bid_end_time_must_be_greater_than_bid_start_time",
                                            ];
                                        }
                                    }

                                    if($row['estimate_start_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "W",
                                            "column_name" => "estimate_start_price",
                                            "message" => __('messages.estimate_start_price_required'),
                                            "message_code" => "estimate_start_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['estimate_end_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "X",
                                            "column_name" => "estimate_end_price",
                                            "message" => __('messages.estimate_end_price_required'),
                                            "message_code" => "estimate_end_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['estimate_start_price'] && $row['estimate_end_price'])
                                    {
                                        if($row['estimate_start_price'] > $row['estimate_end_price'])
                                        {
                                            $row_valid = 0;
                                            $product_type_status = 0;
                                            $temp_error_array = [
                                                "row_index" => $key + 1 + 1,
                                                "column_index" => "X",
                                                "column_name" => "estimate_end_price",
                                                "message" => __('messages.estimate_end_price_must_be_greater_than_estimate_start_price'),
                                                "message_code" => "estimate_end_price_must_be_greater_than_estimate_start_price",
                                            ];
                                        }
                                    }
                                }

                                if($product->sale_type == 'Sale' || $product->sale_type == 'Bulk')
                                {
                                    if($row['regular_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "O",
                                            "column_name" => "regular_price",
                                            "message" => __('messages.regular_price_required'),
                                            "message_code" => "regular_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['final_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "P",
                                            "column_name" => "final_price",
                                            "message" => __('messages.final_price_required'),
                                            "message_code" => "final_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['regular_price'] && $row['final_price'])
                                    {
                                        if($row['final_price'] > $row['regular_price'])
                                        {
                                            $row_valid = 0;
                                            $product_type_status = 0;
                                            $temp_error_array = [
                                                "row_index" => $key + 1 + 1,
                                                "column_index" => "P",
                                                "column_name" => "final_price",
                                                "message" => __('messages.final_price_must_be_less_than_regular_price'),
                                                "message_code" => "final_price_must_be_less_than_regular_price",
                                            ];
                                            array_push($error_array, $temp_error_array);
                                        }
                                    }

                                }

                                if($product->sale_type == 'Bulk')
                                {
                                    if($row['initial_quantity'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "R",
                                            "column_name" => "initial_quantity",
                                            "message" => __('messages.initial_quantity_required'),
                                            "message_code" => "initial_quantity_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['incremental_quantity'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "S",
                                            "column_name" => "incremental_quantity",
                                            "message" => __('messages.incremental_quantity_required'),
                                            "message_code" => "incremental_quantity_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                    if($row['incremental_price'])
                                    {

                                    }
                                    else
                                    {
                                        $row_valid = 0;
                                        $product_type_status = 0;
                                        $temp_error_array = [
                                            "row_index" => $key + 1 + 1,
                                            "column_index" => "T",
                                            "column_name" => "incremental_price",
                                            "message" => __('messages.incremental_price_required'),
                                            "message_code" => "incremental_price_required",
                                        ];
                                        array_push($error_array, $temp_error_array);
                                    }

                                }

                                if($cost_status && $product_type_status)
                                {
                                    if($product->sale_type = 'Bulk') $incremental_price = ($row['incremental_price'])?$row['incremental_price']:0; else $incremental_price = 0;
                                    if($product->sale_type = 'Bulk') $initial_quantity = ($row['initial_quantity'])?$row['initial_quantity']:0; else $initial_quantity = 0;
                                    if($product->sale_type = 'Bulk') $incremental_quantity = ($row['incremental_quantity'])?$row['incremental_quantity']:0; else $incremental_quantity = 0;

                                    if($product->sale_type = 'Auction') $bid_value = ($row['bid_value'])?$row['bid_value']:0; else $bid_value = 0;
                                    if($product->sale_type = 'Auction') $bid_start_price = ($row['bid_start_price'])?$row['bid_start_price']:0; else $bid_start_price = 0;

                                    if($product->sale_type = 'Auction') $estimate_start_price = ($row['estimate_start_price'])?$row['estimate_start_price']:0; else $estimate_start_price = 0;
                                    if($product->sale_type = 'Auction') $estimate_end_price = ($row['estimate_end_price'])?$row['estimate_end_price']:0; else $estimate_end_price = 0;

                                    if($product->sale_type = 'Sale' || $product->sale_type = 'Bulk') $regular_price = ($row['regular_price'])?$row['regular_price']:0; else $regular_price = 0;
                                    if($product->sale_type = 'Sale' || $product->sale_type = 'Bulk') $final_price = ($row['final_price'])?$row['final_price']:0; else $final_price = 0;

                                    $product_variant = new ProductVariant();
                                    $product_variant->product_id = $product->id;
                                    $product_variant->sku = $row['product_sku'];
                                    if($product->sale_type = 'Sale' || $product->sale_type = 'Bulk') $product_variant->regular_price = $regular_price;
                                    if($product->sale_type = 'Sale' || $product->sale_type = 'Bulk') $product_variant->final_price = $final_price;
                                    if($product->sale_type = 'Bulk') $product_variant->incremental_price = $incremental_price;
                                    if($product->sale_type = 'Bulk') $product_variant->initial_quantity = $initial_quantity;
                                    if($product->sale_type = 'Bulk') $product_variant->incremental_quantity = $incremental_quantity;
                                    if($product->sale_type = 'Auction') $product_variant->bid_value = $bid_value;
                                    if($product->sale_type = 'Auction') $product_variant->bid_start_price = $bid_start_price;
                                    if($product->sale_type = 'Auction') $product_variant->estimate_start_price = $estimate_start_price;
                                    if($product->sale_type = 'Auction') $product_variant->estimate_end_price = $estimate_end_price;
                                    $product_variant->quantity = 0;
                                    $product_variant->status = $status;
                                    $product_variant->created_by_user_id = auth('web')->id();
                                    $product_variant->updated_by_user_id = auth('web')->id();
                                    if($product->boutique->user->beiaat_handled)
                                    {
                                        $product_variant->cost = $row['cost'];
                                    }
                                    $product_variant->save();

                                    $user_boutique = $product_variant->product->boutique;
                                    $stock = new Stock();
                                    $stock->user_boutique_id = $user_boutique->id;
                                    $stock->product_variant_id = $product_variant->id;
                                    $stock->quantity = $row['quantity'];
                                    $stock->created_by_user_id = auth('web')->id();
                                    $stock->updated_by_user_id = auth('web')->id();
                                    $stock->save();

                                    $stock_history = new StockHistory();
                                    $stock_history->stock_id = $stock->id;
                                    $stock_history->user_boutique_id = $user_boutique->id;
                                    $stock_history->product_variant_id = $product_variant->id;
                                    $stock_history->quantity = $row['quantity'];
                                    $stock_history->add_by = "AddByVendor";
                                    $stock_history->created_by_user_id = auth('web')->id();
                                    $stock_history->updated_by_user_id = auth('web')->id();
                                    $stock_history->stock_type = "Add";
                                    $stock_history->save();


                                    foreach ($product_attributes as $key => $product_attribute)
                                    {
                                        DB::table('product_attributes')->insert([
                                            'product_id' => $product->id,
                                            'product_variant_id' => $product_variant->id,
                                            'attribute_id' => $product_attributes[$key],
                                            'attribute_value_id' => $attribute_values[$key],
                                            'created_at' => $current_date_time,
                                            'updated_at' => $current_date_time,
                                        ]);
                                    }

                                    $product_for_auction_or_sale = new ProductForAuctionOrSale();
                                    $product_for_auction_or_sale->user_id = $product->user_id;
                                    $product_for_auction_or_sale->product_id = $product->id;
                                    $product_for_auction_or_sale->type = $product->sale_type;
                                    if($product->sale_type = 'Auction' && $row['bid_start_time'] && $row['bid_end_time'])
                                    {
                                        $bid_start_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['bid_start_time'], $time_zone))->toDateTimeString());
                                        $bid_end_time = getUtcTimeFromLocal(Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['bid_end_time'], $time_zone))->toDateTimeString());
                                        $product_for_auction_or_sale->bid_start_time = $bid_start_time;
                                        $product_for_auction_or_sale->bid_end_time = $bid_end_time;
                                    }
                                    $product_for_auction_or_sale->created_by_user_id = auth('web')->id();
                                    $product_for_auction_or_sale->updated_by_user_id = auth('web')->id();
                                    $product_for_auction_or_sale->save();
                                    DB::commit();
                                    $success_count = $success_count + 1;
                                }

                            }
                            else
                            {
                                DB::rollBack();
                                $row_valid = 0;
                                $temp_error_array = [
                                    "row_index" => $key + 1 + 1,
                                    "column_index" => "G",
                                    "column_name" => "product_attribute_values",
                                    "message" => __('messages.combination_already_exists'),
                                    "message_code" => "combination_already_exists",
                                ];
                                array_push($error_array, $temp_error_array);
                            }
                        }
                        else
                        {
                            DB::rollBack();
                            $row_valid = 0;
                            $temp_error_array = [
                                "row_index" => $key + 1 + 1,
                                "column_index" => "G",
                                "column_name" => "product_attribute_values",
                                "message" => __('messages.combination_already_exists'),
                                "message_code" => "combination_already_exists",
                            ];
                            array_push($error_array, $temp_error_array);
                        }

                    }

                }

            }

        }

        Session::put('bulk_error_array', $error_array);
        Session::put('bulk_success_count', $success_count);
        $failed_count = $row_count - $success_count;
        Session::put('bulk_failed_count', $failed_count);
    }
}

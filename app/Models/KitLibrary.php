<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class KitLibrary extends Model
{
    use HasFactory, SoftDeletes;

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'kit_library_packages');
    }

    public function kit_library_features()
    {
        return $this->hasMany(KitLibraryFeature::class, 'kit_library_id');
    }

    public function kit_type()
    {
        return $this->belongsTo(KitType::class, 'kit_type_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }



    public function getThumbImage()
    {
        $image = null;
        $image_name = null;
        if($this->image)
        {
            $image_name = $this->image;
            $image = asset('storage/uploads/kit_libraries/thumb/'.$this->image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/kit_libraries/thumb/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        return $image;
    }

    public function getOriginalImage()
    {
        $image = null;
        $image_name = null;
        if($this->image)
        {
            $image_name = $this->image;
            $image = asset('storage/uploads/kit_libraries/original/'.$this->image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/kit_libraries/original/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        return $image;
    }

    public function getCoverThumbImage()
    {
        $image = null;
        $image_name = null;
        if($this->cover_image)
        {
            $image_name = $this->cover_image;
            $image = asset('storage/uploads/kit_libraries/thumb/'.$this->cover_image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/kit_libraries/thumb/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        return $image;
    }

    public function getCovertOriginalImage()
    {
        $image = null;
        $image_name = null;
        if($this->cover_image)
        {
            $image_name = $this->cover_image;
            $image = asset('storage/uploads/kit_libraries/original/'.$this->cover_image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/kit_libraries/original/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        return $image;
    }

    public function getPackageNames()
    {
        return $this->packages()->pluck('title')->implode(', ');
    }

    public function getTagNames()
    {
        $tags = $this->tag_ids;
        $tag_ids = explode(",", $tags);
        return Tag::whereIn('id', $tag_ids)->pluck('title')->implode(', ');
    }

    public function gePackageNames()
    {
        return $this->packages()->pluck('title')->implode(', ');
    }
}

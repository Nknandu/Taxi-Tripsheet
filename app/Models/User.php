<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile_number',
        'email',
        'password',
        'gender',
        'date_of_birth',
        'user_type',
        'image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $appends = ['thumb_image', 'original_image'];

    function getUserType()
    {
       if($this->user_type == "Vendor")
       {
           return __('messages.user');
       }
       else
       {
           return __('messages.user');
       }
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function boutiques()
    {
        return $this->hasMany(UserBoutique::class, 'user_id');
    }

    public function getThumbImageAttribute(){
        $image = null;
        $image_name = null;
        if($this->image)
        {
            $image_name = $this->image;
            $image = asset('storage/uploads/users/thumb/'.$this->image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/users/thumb/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        if($image == null)
        {
            $image = asset('assets/media/dummy/no_image.jpeg');
        }
        return $image;
    }

    public function getOriginalImageAttribute(){
        $image = null;
        $image_name = null;
        if($this->image)
        {
            $image_name = $this->image;
            $image = asset('storage/uploads/users/original/'.$this->image);
        }
        if($image != null)
        {
            if(!Storage::disk('public')->exists('uploads/users/original/'.$image_name))
            {
                $image = asset('assets/media/dummy/no_image.jpeg');
            }
        }
        if($image == null)
        {
            $image = asset('assets/media/dummy/no_image.jpeg');
        }
        return $image;
    }

    function getPackageFeatures()
    {
        $package_features = [];
        if($this->package_id)
        {
            if($this->package && $this->package->features)
            {
                $current_date_time = Carbon::now('UTC')->toDateTimeString();
                if($current_date_time >= $this->package_start_date && $current_date_time <= $this->package_end_date)
                {
                    $package_features = $this->package->features()->pluck('slug')->toArray();
                }
            }
         }
        return $package_features;
    }

    function getPackageFeatureAppSide()
    {
        $package_features = [];
        $features  = Feature::where('status', 1)->get();
        $package_features = [];
        if($this->package_id)
        {
            if($this->package && $this->package->features)
            {
                $current_date_time = Carbon::now('UTC')->toDateTimeString();
                if($current_date_time >= $this->package_start_date && $current_date_time <= $this->package_end_date)
                {
                    $package_features = $this->package->features()->pluck('slug')->toArray();
                }
            }
        }

        if(empty($package_features))
        {
            $package = Package::where('is_default', 1)->first();
            if($package)
            {
                $package_features = $package->features()->pluck('slug')->toArray();
            }
        }

        $user_package_permissions = [];
        foreach ($features as $feature)
        {
            $status = "false";
            if(in_array($feature->slug, $package_features))
            {
                $status = "true";
            }

            $user_package_permissions[$feature->slug] = (string) $status;

        }

        return $user_package_permissions;
    }



    function getPackageStatus()
    {
        if($this->package_id)
        {
            if($this->package && $this->package->features)
            {
                $current_date_time = Carbon::now('UTC')->toDateTimeString();
                if($current_date_time >= $this->package_start_date && $current_date_time <= $this->package_end_date)
                {
                    return "active";
                }
                else
                {
                    return "in_active";
                }

            }
            else
            {
                return "invalid_package";
            }
        }
        else
        {
            return "no_package";
        }
    }

    function getPackageEndDate()
    {
        if($this->package_id)
        {
            if($this->package && $this->package->features)
            {
                return $this->package_end_date;
            }
            else
            {
                return null;
            }
        }
        else
        {
            return null;
        }
    }

    function getPackageStartDate()
    {
        if($this->package_id)
        {
            if($this->package && $this->package->features)
            {
                return $this->package_start_date;
            }
            else
            {
                return null;
            }
        }
        else
        {
            return null;
        }
    }




}

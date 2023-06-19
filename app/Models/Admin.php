<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;

    protected $guard = "admin";

    protected $fillable = [
        'first_name',
        'last_name',
        'mobile_number',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin_role()
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    function getAdminPermissions()
    {
        if($this->admin_role_id)
        {
            $admin_role = $this->admin_role;
            $admin_permissions = $admin_role->permissions()->pluck('route_name')->toArray();
        }
        else
        {
            $admin_permissions = Permission::pluck('route_name')->toArray();
        }
       return $admin_permissions;
    }

}

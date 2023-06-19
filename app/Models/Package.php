<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    public function kit_libraries()
    {
        return $this->belongsToMany(KitLibrary::class, 'kit_library_packages');
    }
}

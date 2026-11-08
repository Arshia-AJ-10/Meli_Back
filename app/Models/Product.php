<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_products')
            ->withPivot('rating','text')
            ->withTimestamps();
    }
}

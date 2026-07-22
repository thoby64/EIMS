<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['public_id', 'parent_id', 'organizational_unit_id', 'name', 'code', 'type', 'description', 'is_active'];
}

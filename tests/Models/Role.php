<?php

namespace Brexis\LaravelSSO\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'app_id'];
}

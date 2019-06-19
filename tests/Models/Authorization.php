<?php

namespace Brexis\LaravelSSO\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Authorization extends Model
{
    protected $table = 'authorizations';

    protected $fillable = ['user_id', 'app_id', 'role_id'];
}

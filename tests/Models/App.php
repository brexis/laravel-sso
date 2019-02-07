<?php

namespace Brexis\LaravelSSO\Test\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = ['app_id', 'secret'];
}

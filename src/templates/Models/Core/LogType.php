<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class LogType extends Model
{
    //
    protected $fillable = ['slug','name','description','user_id','facebook_event_type'];

}

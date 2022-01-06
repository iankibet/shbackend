<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationMessage extends Model
{
    
	use HasFactory;
	protected $fillable = ["slug","subject","mail","sms","action_label","action_url"];

}

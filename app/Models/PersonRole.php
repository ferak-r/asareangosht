<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonRole extends Model
{
    protected $fillable = ['person_id','role'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatusHistory extends Model
{
    protected $fillable = ['task_id','old_status','new_status','old_progress','new_progress','note','changed_by'];
}

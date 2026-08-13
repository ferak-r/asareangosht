<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Task extends Model { protected $fillable = ['project_subitem_id','title','description','status','priority','progress_percent','planned_start_date','planned_end_date','actual_start_date','actual_end_date','estimated_days','due_date','completed_at','completed_by','returned_at','returned_by','return_reason','created_by']; public function projectSubitem() { return $this->belongsTo(ProjectSubitem::class); } public function assignees() { return $this->belongsToMany(Person::class,'task_assignees')->withPivot(['assigned_by','assigned_at']); } }

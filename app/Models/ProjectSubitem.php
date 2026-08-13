<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectSubitem extends Model { protected $fillable = ['project_item_id','title','description','budget_amount','responsible_person_id','planned_start_date','planned_end_date','actual_start_date','actual_end_date','progress_percent','status','sort_order']; public function projectItem() { return $this->belongsTo(ProjectItem::class); } public function tasks() { return $this->hasMany(Task::class); } }

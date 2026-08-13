<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProjectItem extends Model { protected $fillable = ['project_id','title','description','budget_amount','responsible_person_id','planned_start_date','planned_end_date','actual_start_date','actual_end_date','progress_percent','status','sort_order']; public function project() { return $this->belongsTo(Project::class); } public function subitems() { return $this->hasMany(ProjectSubitem::class); } }

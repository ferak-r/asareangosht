<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Department extends Model { protected $fillable = ['name','code','description','manager_person_id','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } public function people() { return $this->belongsToMany(Person::class)->withPivot(['joined_at','left_at','is_active'])->withTimestamps(); } }

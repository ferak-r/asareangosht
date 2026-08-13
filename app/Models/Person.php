<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Person extends Model { protected $fillable = ['type','full_name','mobile','national_id','registration_no','address','notes','is_active']; protected function casts(): array { return ['is_active'=>'boolean']; } public function departments(): BelongsToMany { return $this->belongsToMany(Department::class)->withPivot(['joined_at','left_at','is_active'])->withTimestamps(); } public function roles() { return $this->hasMany(PersonRole::class); } public function user() { return $this->hasOne(User::class); } }

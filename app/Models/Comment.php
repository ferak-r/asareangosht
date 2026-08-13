<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['commentable_type','commentable_id','body','created_by','visibility','parent_id'];
    public function commentable() { return $this->morphTo(); }
    public function author() { return $this->belongsTo(User::class, 'created_by'); }
}

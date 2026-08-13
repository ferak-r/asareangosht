<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['disk','path','original_name','mime_type','size_bytes','attachable_type','attachable_id','uploaded_by','visibility','description'];
    public function attachable() { return $this->morphTo(); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}

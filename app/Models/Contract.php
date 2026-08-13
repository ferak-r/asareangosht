<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = ['project_id', 'contract_no', 'title', 'type', 'amount', 'signed_date', 'start_date', 'end_date', 'status', 'description', 'created_by'];

    protected function casts(): array
    {
        return ['signed_date' => 'date', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

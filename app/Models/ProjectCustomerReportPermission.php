<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCustomerReportPermission extends Model
{
    protected $fillable = ['project_id','report_key','is_enabled'];
    protected function casts(): array { return ['is_enabled'=>'boolean']; }
}

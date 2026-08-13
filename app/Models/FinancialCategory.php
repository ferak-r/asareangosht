<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    protected $fillable = ['scope','parent_id','title','code','is_active','sort_order','notes'];
}

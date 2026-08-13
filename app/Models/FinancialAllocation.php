<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAllocation extends Model
{
    protected $fillable = ['financial_document_id','allocatable_type','allocatable_id','amount','notes'];
    public function document() { return $this->belongsTo(FinancialDocument::class, 'financial_document_id'); }
    public function allocatable() { return $this->morphTo(); }
}

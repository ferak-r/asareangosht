<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Check extends Model
{
    protected $fillable = ['type','instrument_type','sayad_id','serial_number','bank_name','branch_name','linked_account_id','amount','issue_date','due_date','issuer_person_id','beneficiary_person_id','current_holder_person_id','status','credit_color','inquiry_result','inquiry_at','project_id','financial_document_id','notes','created_by','voided_at','voided_by','void_reason'];
    protected function casts(): array { return ['issue_date'=>'date','due_date'=>'date','inquiry_at'=>'datetime','voided_at'=>'datetime']; }
    public function document() { return $this->belongsTo(FinancialDocument::class, 'financial_document_id'); }
    public function entries() { return $this->hasMany(FinancialEntry::class); }
}

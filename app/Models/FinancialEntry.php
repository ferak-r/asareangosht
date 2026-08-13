<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialEntry extends Model
{
    protected $fillable = ['financial_document_id','direction','account_id','amount','payment_method_id','transaction_date','reference_no','check_id','status','notes','created_by'];
    protected function casts(): array { return ['transaction_date'=>'date']; }
    public function document() { return $this->belongsTo(FinancialDocument::class, 'financial_document_id'); }
    public function account() { return $this->belongsTo(FinancialAccount::class, 'account_id'); }
    public function check() { return $this->belongsTo(Check::class); }
}

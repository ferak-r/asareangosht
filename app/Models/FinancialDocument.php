<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialDocument extends Model
{
    protected $fillable = ['document_no','type','title','description','issue_date','gross_amount','tax_amount','discount_amount','net_amount','category_id','counterparty_person_id','vendor_person_id','project_id','contract_id','related_document_id','invoice_no','status','due_date','created_by','voided_at','voided_by','void_reason'];
    protected function casts(): array { return ['issue_date'=>'date','due_date'=>'date','voided_at'=>'datetime']; }
    public function entries() { return $this->hasMany(FinancialEntry::class); }
    public function allocations() { return $this->hasMany(FinancialAllocation::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function counterparty() { return $this->belongsTo(Person::class, 'counterparty_person_id'); }
    public function category() { return $this->belongsTo(FinancialCategory::class, 'category_id'); }
    public function getPaidAmountAttribute(): int { return $this->type === 'account_transfer' ? (int) $this->net_amount : (int) $this->entries()->where('status','cleared')->sum('amount'); }
    public function getRemainingAmountAttribute(): int { return max(0, (int) $this->net_amount - $this->paid_amount); }
}

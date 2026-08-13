<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialAccount extends Model
{
    protected $table = 'financial_accounts';
    protected $fillable = ['owner_person_id','name','kind','bank_name','branch_name','account_number','card_number','iban','opening_balance','opening_balance_date','is_workshop_owned','is_active','notes'];
    protected function casts(): array { return ['is_workshop_owned' => 'boolean', 'is_active' => 'boolean']; }
    public function owner() { return $this->belongsTo(Person::class, 'owner_person_id'); }
    public function entries() { return $this->hasMany(FinancialEntry::class, 'account_id'); }
    public function getCurrentBalanceAttribute(): int
    {
        $incoming = $this->entries()->where('direction','in')->where('status','cleared')->sum('amount');
        $outgoing = $this->entries()->where('direction','out')->where('status','cleared')->sum('amount');
        return (int) $this->opening_balance + (int) $incoming - (int) $outgoing;
    }
}

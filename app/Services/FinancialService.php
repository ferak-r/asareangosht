<?php

namespace App\Services;

use App\Models\Check;
use App\Models\FinancialAllocation;
use App\Models\FinancialDocument;
use App\Models\FinancialEntry;
use App\Models\FinancialCategory;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectSubitem;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialService
{
    private const ALLOCATABLES = ['project'=>Project::class,'item'=>ProjectItem::class,'subitem'=>ProjectSubitem::class,'task'=>Task::class];

    public function createDocument(array $data, int $userId): FinancialDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $net = (int) $data['gross_amount'] + (int) ($data['tax_amount'] ?? 0) - (int) ($data['discount_amount'] ?? 0);
            if ($net <= 0) throw ValidationException::withMessages(['gross_amount'=>'مبلغ خالص باید بیشتر از صفر باشد.']);
            if ($data['type'] === 'payroll_payment' && (empty($data['counterparty_person_id']) || ! Person::whereKey($data['counterparty_person_id'])->whereHas('roles',fn($q)=>$q->where('role','employee'))->exists())) {
                throw ValidationException::withMessages(['counterparty_person_id'=>'برای حقوق باید یک شخص با نقش شغلی کارمند انتخاب شود.']);
            }
            if (! empty($data['category_id']) && in_array($data['type'],['expense','income'],true)) {
                $expected=$data['type']==='expense'?'expense':'income';
                if (! FinancialCategory::whereKey($data['category_id'])->where('scope',$expected)->exists()) throw ValidationException::withMessages(['category_id'=>'دسته مالی با نوع سند سازگار نیست.']);
            }
            $document = FinancialDocument::create($data + [
                'document_no' => $this->nextNumber($data['type']), 'net_amount' => $net,
                'status' => 'draft', 'created_by' => $userId,
            ]);
            if (! empty($data['payment_amount'])) $this->addEntry($document, $data, $userId);
            if (! empty($data['allocation_amount'])) $this->allocate($document, $data, $userId);
            return $document->refresh();
        });
    }

    public function addEntry(FinancialDocument $document, array $data, int $userId): FinancialEntry
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            if ($document->status === 'voided') throw ValidationException::withMessages(['document'=>'سند ابطال‌شده قابل پرداخت نیست.']);
            $amount = (int) ($data['payment_amount'] ?? $data['amount'] ?? 0);
            if ($amount <= 0) throw ValidationException::withMessages(['payment_amount'=>'مبلغ پرداخت باید بیشتر از صفر باشد.']);
            if ($document->type !== 'account_transfer' && $document->entries()->whereNotIn('status',['reversed','voided'])->sum('amount') + $amount > $document->net_amount) {
                throw ValidationException::withMessages(['payment_amount'=>'جمع ریزپرداخت‌ها از مبلغ خالص سند بیشتر می‌شود.']);
            }
            $entry = $document->entries()->create([
                'direction' => $data['direction'] ?? $this->directionFor($document->type), 'account_id' => $data['account_id'],
                'amount' => $amount, 'payment_method_id' => $data['payment_method_id'],
                'transaction_date' => $data['transaction_date'] ?? $document->issue_date,
                'reference_no' => $data['reference_no'] ?? null, 'check_id' => $data['check_id'] ?? null,
                'status' => ($data['entry_status'] ?? 'cleared'), 'notes' => $data['entry_notes'] ?? null, 'created_by' => $userId,
            ]);
            $this->refreshStatus($document);
            return $entry;
        });
    }

    public function transfer(array $data, int $userId): FinancialDocument
    {
        if ((int) $data['source_account_id'] === (int) $data['destination_account_id']) throw ValidationException::withMessages(['destination_account_id'=>'حساب مبدأ و مقصد باید متفاوت باشند.']);
        return DB::transaction(function () use ($data, $userId) {
            $document = FinancialDocument::create(['document_no'=>$this->nextNumber('account_transfer'),'type'=>'account_transfer','title'=>$data['title'],'description'=>$data['description'] ?? null,'issue_date'=>$data['transaction_date'],'gross_amount'=>$data['amount'],'net_amount'=>$data['amount'],'status'=>'settled','created_by'=>$userId]);
            foreach ([['out',$data['source_account_id']],['in',$data['destination_account_id']]] as [$direction,$accountId]) {
                FinancialEntry::create(['financial_document_id'=>$document->id,'direction'=>$direction,'account_id'=>$accountId,'amount'=>$data['amount'],'payment_method_id'=>$data['payment_method_id'],'transaction_date'=>$data['transaction_date'],'reference_no'=>$data['reference_no'] ?? null,'status'=>'cleared','created_by'=>$userId]);
            }
            return $document;
        });
    }

    public function allocate(FinancialDocument $document, array $data, int $userId): FinancialAllocation
    {
        if ($document->status === 'voided') throw ValidationException::withMessages(['document'=>'سند ابطال‌شده قابل تخصیص نیست.']);
        $amount = (int) $data['allocation_amount'];
        if ($amount <= 0 || $document->allocations()->sum('amount') + $amount > $document->net_amount) throw ValidationException::withMessages(['allocation_amount'=>'مجموع تخصیص باید مثبت و حداکثر برابر مبلغ خالص سند باشد.']);
        $type = self::ALLOCATABLES[$data['allocation_type']] ?? null;
        $target=$type ? $type::find($data['allocation_id']) : null;
        if (! $target) throw ValidationException::withMessages(['allocation_id'=>'مقصد تخصیص معتبر نیست.']);
        $targetProjectId=$this->targetProjectId($target);
        if ($document->project_id && $targetProjectId !== (int)$document->project_id) throw ValidationException::withMessages(['allocation_id'=>'مقصد تخصیص باید متعلق به پروژه همین سند باشد.']);
        return $document->allocations()->create(['allocatable_type'=>$type,'allocatable_id'=>$data['allocation_id'],'amount'=>$amount,'notes'=>$data['allocation_notes'] ?? null]);
    }

    public function updateCheckStatus(Check $check, string $status, int $userId): Check
    {
        return DB::transaction(function () use ($check, $status, $userId) {
            $check->update(['status'=>$status]);
            if ($status === 'cleared') $check->entries()->update(['status'=>'cleared']);
            if ($status === 'bounced') $check->entries()->update(['status'=>'reversed']);
            if ($status === 'voided') $check->entries()->update(['status'=>'voided']);
            if ($check->document) $this->refreshStatus($check->document);
            return $check->refresh();
        });
    }

    public function void(FinancialDocument $document, string $reason, int $userId): void
    {
        DB::transaction(function () use ($document,$reason,$userId) {
            $document->update(['status'=>'voided','voided_at'=>now(),'voided_by'=>$userId,'void_reason'=>$reason]);
            app(AuditService::class)->record('voided',$document,[],['reason'=>$reason,'entries_preserved'=>true]);
        });
    }

    private function refreshStatus(FinancialDocument $document): void
    {
        if ($document->status === 'voided' || $document->type === 'account_transfer') return;
        $paid=(int)$document->entries()->where('status','cleared')->sum('amount');
        $status=$paid<=0?'draft':($paid<$document->net_amount?'partial':($this->directionFor($document->type)==='in'?'received':'paid'));
        $document->update(['status'=>$status]);
    }
    private function directionFor(string $type): string { return in_array($type,['income','receivable'],true) ? 'in' : 'out'; }
    private function targetProjectId(object $target): int { return match(true) { $target instanceof Project => (int)$target->id, $target instanceof ProjectItem => (int)$target->project_id, $target instanceof ProjectSubitem => (int)$target->projectItem->project_id, $target instanceof Task => (int)$target->projectSubitem->projectItem->project_id }; }
    private function nextNumber(string $type): string { return strtoupper(substr($type,0,3)).'-'.now()->format('YmdHis').'-'.random_int(100,999); }
}

<?php

namespace Tests\Feature;

use App\Models\Check;
use App\Models\FinancialAccount;
use App\Models\FinancialDocument;
use App\Models\FinancialEntry;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialService $service;
    private User $user;
    private PaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FinancialService::class);
        $this->user = User::factory()->create();
        $this->method = PaymentMethod::create(['name'=>'انتقال بانکی','code'=>'bank-transfer-test','is_active'=>true]);
    }

    public function test_account_transfer_creates_equal_in_and_out_entries(): void
    {
        $source=FinancialAccount::create(['name'=>'مبدأ','kind'=>'cashbox','opening_balance'=>1_000_000]);
        $destination=FinancialAccount::create(['name'=>'مقصد','kind'=>'cashbox','opening_balance'=>0]);
        $document=$this->service->transfer(['title'=>'تست انتقال','source_account_id'=>$source->id,'destination_account_id'=>$destination->id,'amount'=>250_000,'payment_method_id'=>$this->method->id,'transaction_date'=>'2026-08-13'],$this->user->id);
        $this->assertSame('account_transfer',$document->type);
        $this->assertSame(250_000,(int)$document->entries()->where('direction','out')->sum('amount'));
        $this->assertSame(250_000,(int)$document->entries()->where('direction','in')->sum('amount'));
        $this->assertSame(750_000,$source->fresh()->current_balance);
        $this->assertSame(250_000,$destination->fresh()->current_balance);
    }

    public function test_allocations_cannot_exceed_document_net_amount(): void
    {
        $project=Project::create(['code'=>'P-TEST','title'=>'پروژه تست','budget_amount'=>0,'contract_total_amount'=>0,'status'=>'draft','created_by'=>$this->user->id]);
        $document=FinancialDocument::create(['document_no'=>'EXP-TEST','type'=>'expense','title'=>'هزینه تست','issue_date'=>'2026-08-13','gross_amount'=>100_000,'net_amount'=>100_000,'status'=>'draft','created_by'=>$this->user->id]);
        $this->service->allocate($document,['allocation_type'=>'project','allocation_id'=>$project->id,'allocation_amount'=>80_000],$this->user->id);
        $this->expectException(ValidationException::class);
        $this->service->allocate($document,['allocation_type'=>'project','allocation_id'=>$project->id,'allocation_amount'=>30_000],$this->user->id);
    }

    public function test_check_clear_and_bounce_update_linked_entry(): void
    {
        $account=FinancialAccount::create(['name'=>'بانک','kind'=>'bank_account','opening_balance'=>0]);
        $document=FinancialDocument::create(['document_no'=>'INC-TEST','type'=>'income','title'=>'درآمد تست','issue_date'=>'2026-08-13','gross_amount'=>300_000,'net_amount'=>300_000,'status'=>'draft','created_by'=>$this->user->id]);
        $check=Check::create(['type'=>'received','instrument_type'=>'ordinary','bank_name'=>'تست','amount'=>300_000,'due_date'=>'2026-08-20','status'=>'received','financial_document_id'=>$document->id,'created_by'=>$this->user->id]);
        $entry=FinancialEntry::create(['financial_document_id'=>$document->id,'direction'=>'in','account_id'=>$account->id,'amount'=>300_000,'payment_method_id'=>$this->method->id,'transaction_date'=>'2026-08-13','check_id'=>$check->id,'status'=>'pending','created_by'=>$this->user->id]);
        $this->service->updateCheckStatus($check,'cleared',$this->user->id);
        $this->assertSame('cleared',$entry->fresh()->status);
        $this->assertSame('received',$document->fresh()->status);
        $this->service->updateCheckStatus($check,'bounced',$this->user->id);
        $this->assertSame('reversed',$entry->fresh()->status);
        $this->assertSame('draft',$document->fresh()->status);
    }

    public function test_three_entries_cannot_exceed_net_and_exact_sum_marks_paid(): void
    {
        $account=FinancialAccount::create(['name'=>'صندوق','kind'=>'cashbox','opening_balance'=>500_000]);
        $document=FinancialDocument::create(['document_no'=>'EXP-THREE','type'=>'expense','title'=>'سه قسط','issue_date'=>'2026-08-13','gross_amount'=>300_000,'net_amount'=>300_000,'status'=>'draft','created_by'=>$this->user->id]);
        foreach([100_000,100_000,100_000] as $amount)$this->service->addEntry($document,['account_id'=>$account->id,'amount'=>$amount,'payment_method_id'=>$this->method->id,'transaction_date'=>'2026-08-13','entry_status'=>'cleared'],$this->user->id);
        $this->assertSame('paid',$document->fresh()->status);
        $this->expectException(ValidationException::class);
        $this->service->addEntry($document,['account_id'=>$account->id,'amount'=>1,'payment_method_id'=>$this->method->id,'transaction_date'=>'2026-08-13','entry_status'=>'cleared'],$this->user->id);
    }
}

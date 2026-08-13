<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FinancialDocument;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp(); Role::findOrCreate('admin','web'); $this->admin=User::factory()->create(['is_active'=>true]); $this->admin->assignRole('admin');
    }

    public function test_report_and_export_use_same_type_filter(): void
    {
        FinancialDocument::create(['document_no'=>'EXP-1','type'=>'expense','title'=>'هزینه قابل مشاهده','issue_date'=>'2026-08-13','gross_amount'=>100,'net_amount'=>100,'status'=>'draft','created_by'=>$this->admin->id]);
        FinancialDocument::create(['document_no'=>'INC-1','type'=>'income','title'=>'درآمد پنهان در فیلتر','issue_date'=>'2026-08-13','gross_amount'=>200,'net_amount'=>200,'status'=>'draft','created_by'=>$this->admin->id]);
        $this->actingAs($this->admin)->get(route('reports.index','financial').'?document_type=expense')->assertOk()->assertSee('هزینه قابل مشاهده')->assertDontSee('درآمد پنهان در فیلتر');
        $this->actingAs($this->admin)->get(route('reports.export','financial').'?document_type=expense')->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
    }

    public function test_void_preserves_real_entries_and_creates_audit(): void
    {
        $document=FinancialDocument::create(['document_no'=>'EXP-V','type'=>'expense','title'=>'ابطال','issue_date'=>'2026-08-13','gross_amount'=>100,'net_amount'=>100,'status'=>'paid','created_by'=>$this->admin->id]);
        app(FinancialService::class)->void($document,'ثبت اشتباه سند',$this->admin->id);
        $this->assertSame('voided',$document->fresh()->status);
        $this->assertDatabaseHas('audit_logs',['event'=>'voided','auditable_type'=>FinancialDocument::class,'auditable_id'=>$document->id]);
    }
}

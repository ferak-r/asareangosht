<?php

namespace App\Services;

use App\Models\Check;
use App\Models\FinancialAccount;
use App\Models\FinancialDocument;
use App\Models\FinancialAllocation;
use App\Models\ProjectItem;
use App\Models\ProjectSubitem;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportService
{
    public const TYPES = ['financial'=>'اسناد مالی','accounts'=>'مانده حساب‌ها','projects'=>'سود و پیشرفت پروژه','checks'=>'چک‌ها','payroll'=>'حقوق'];

    public function rows(string $type, Request $request): Collection
    {
        abort_unless(isset(self::TYPES[$type]),404);
        if (in_array($type,['accounts','checks'],true)) abort_unless($request->user()->hasRole('admin'),403);
        return match ($type) {
            'financial' => $this->financial($request), 'accounts' => $this->accounts(),
            'projects' => $this->projects($request), 'checks' => $this->checks($request), 'payroll' => $this->payroll($request),
        };
    }

    private function financial(Request $request): Collection
    {
        $q=FinancialDocument::with(['project','counterparty']);
        if ($request->user()->hasRole('project_manager')) $q->whereHas('project.managers',fn($manager)=>$manager->whereKey($request->user()->person_id));
        if (! $request->boolean('include_voided')) $q->where('status','!=','voided');
        $this->dates($q,$request,'issue_date');
        if ($request->filled('document_type')) $q->where('type',$request->input('document_type'));
        foreach (['project_id','status'] as $field) if ($request->filled($field)) $q->where($field,$request->input($field));
        return $q->orderByDesc('issue_date')->get()->map(fn($d)=>['شماره'=>$d->document_no,'تاریخ'=>$d->issue_date?->format('Y/m/d'),'نوع'=>$d->type,'عنوان'=>$d->title,'پروژه'=>$d->project?->title,'طرف حساب'=>$d->counterparty?->full_name,'خالص'=>(int)$d->net_amount,'پرداخت‌شده'=>$d->paid_amount,'باقی‌مانده'=>$d->remaining_amount,'وضعیت'=>$d->status]);
    }

    private function accounts(): Collection
    {
        return FinancialAccount::with('owner')->orderBy('name')->get()->map(fn($a)=>['حساب'=>$a->name,'نوع'=>$a->kind,'صاحب'=>$a->owner?->full_name,'مانده اولیه'=>(int)$a->opening_balance,'مانده فعلی'=>$a->current_balance,'فعال'=>$a->is_active?'بله':'خیر']);
    }

    private function projects(Request $request): Collection
    {
        $q=Project::with(['customer','items.subitems.tasks']); if($request->user()->hasRole('project_manager'))$q->whereHas('managers',fn($manager)=>$manager->whereKey($request->user()->person_id)); if ($request->filled('project_id')) $q->whereKey($request->integer('project_id')); if ($request->filled('status')) $q->where('status',$request->input('status'));
        return $q->get()->map(function($p) {
            $subitems=$p->items->flatMap(fn($item)=>$item->subitems); $tasks=$subitems->flatMap(fn($subitem)=>$subitem->tasks);
            $income=FinancialDocument::where('project_id',$p->id)->where('type','income')->where('status','!=','voided')->sum('net_amount');
            $cost=FinancialAllocation::whereHas('document',fn($d)=>$d->whereIn('type',['expense','payroll_payment'])->where('status','!=','voided'))->where(function($a)use($p,$subitems,$tasks){$a->where(fn($x)=>$x->where('allocatable_type',Project::class)->where('allocatable_id',$p->id))->orWhere(fn($x)=>$x->where('allocatable_type',ProjectItem::class)->whereIn('allocatable_id',$p->items->pluck('id')))->orWhere(fn($x)=>$x->where('allocatable_type',ProjectSubitem::class)->whereIn('allocatable_id',$subitems->pluck('id')))->orWhere(fn($x)=>$x->where('allocatable_type',Task::class)->whereIn('allocatable_id',$tasks->pluck('id'))); })->sum('amount');
            return ['کد'=>$p->code,'پروژه'=>$p->title,'مشتری'=>$p->customer?->full_name,'بودجه'=>(int)$p->budget_amount,'قرارداد'=>(int)$p->contract_total_amount,'درآمد ثبت‌شده'=>(int)$income,'هزینه و حقوق تخصیصی'=>(int)$cost,'سود'=>(int)$income-(int)$cost,'پیشرفت'=>$tasks->count()?round($tasks->avg('progress_percent'),1):0,'تسک تأخیردار'=>$tasks->where('due_date','<',now()->toDateString())->whereNotIn('status',['completed','cancelled'])->count()];
        });
    }

    private function checks(Request $request): Collection
    {
        $q=Check::query(); $this->dates($q,$request,'due_date'); foreach(['type','status','bank_name'] as $f) if($request->filled($f))$q->where($f,$request->input($f));
        return $q->orderBy('due_date')->get()->map(fn($c)=>['نوع'=>$c->type,'ابزار'=>$c->instrument_type,'صیاد/سریال'=>$c->sayad_id??$c->serial_number,'بانک'=>$c->bank_name,'مبلغ'=>(int)$c->amount,'سررسید'=>$c->due_date?->format('Y/m/d'),'وضعیت'=>$c->status]);
    }

    private function payroll(Request $request): Collection
    {
        $request->merge(['document_type'=>'payroll_payment']); return $this->financial($request);
    }

    private function dates($query,Request $request,string $column): void { if($request->filled('from'))$query->whereDate($column,'>=',$request->input('from')); if($request->filled('to'))$query->whereDate($column,'<=',$request->input('to')); }
}

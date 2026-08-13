<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Contract;
use App\Models\FinancialDocument;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectSubitem;
use App\Models\Task;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancialDocumentController extends Controller
{
    public function __construct(private FinancialService $service) {}

    public function index(Request $request): View
    {
        $query=FinancialDocument::with(['project','counterparty'])->latest('issue_date');
        if ($request->filled('type')) $query->where('type',$request->string('type'));
        if ($request->filled('project_id')) $query->where('project_id',$request->integer('project_id'));
        if ($request->user()->hasRole('project_manager')) $query->whereHas('project.managers',fn($q)=>$q->whereKey($request->user()->person_id));
        return view('financial.index',['documents'=>$query->paginate(25)->withQueryString(),'projects'=>$this->projects($request)]);
    }

    public function create(Request $request): View { return view('financial.form',$this->options($request)); }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate($this->documentRules()); $this->authorizeProject($request,$data['project_id'] ?? null); $this->validateContractProject($data);
        $document=$this->service->createDocument($data,$request->user()->id);
        return redirect()->route('financial.show',$document)->with('success','سند مالی ثبت شد.');
    }

    public function show(Request $request, FinancialDocument $document): View
    {
        $this->authorizeProject($request,$document->project_id);
        return view('financial.show',['document'=>$document->load(['entries.account','allocations','project','counterparty'])] + $this->options($request));
    }

    public function addEntry(Request $request, FinancialDocument $document): RedirectResponse
    {
        $this->authorizeProject($request,$document->project_id);
        $data=$request->validate(['account_id'=>['required','exists:financial_accounts,id'],'payment_amount'=>['required','integer','min:1'],'payment_method_id'=>['required','exists:payment_methods,id'],'transaction_date'=>['required','date'],'reference_no'=>['nullable','string','max:100'],'entry_status'=>['required',Rule::in(['pending','cleared'])],'entry_notes'=>['nullable','string']]);
        $this->service->addEntry($document,$data,$request->user()->id);
        return back()->with('success','ریزپرداخت ثبت شد.');
    }

    public function allocate(Request $request, FinancialDocument $document): RedirectResponse
    {
        $this->authorizeProject($request,$document->project_id);
        $data=$request->validate(['allocation_type'=>['required',Rule::in(['project','item','subitem','task'])],'allocation_id'=>['required','integer','min:1'],'allocation_amount'=>['required','integer','min:1'],'allocation_notes'=>['nullable','string']]);
        $this->service->allocate($document,$data,$request->user()->id);
        return back()->with('success','تخصیص مالی ثبت شد.');
    }

    public function void(Request $request, FinancialDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'),403); $data=$request->validate(['reason'=>['required','string','min:5','max:1000']]);
        $this->service->void($document,$data['reason'],$request->user()->id); return back()->with('success','سند ابطال شد.');
    }

    private function documentRules(): array { return ['type'=>['required',Rule::in(['expense','income','payroll_payment','receivable','payable'])],'title'=>['required','string','max:180'],'description'=>['nullable','string'],'issue_date'=>['required','date'],'gross_amount'=>['required','integer','min:1'],'tax_amount'=>['nullable','integer','min:0'],'discount_amount'=>['nullable','integer','min:0'],'category_id'=>['nullable','exists:financial_categories,id'],'counterparty_person_id'=>['nullable','exists:people,id'],'vendor_person_id'=>['nullable','exists:people,id'],'project_id'=>['nullable','exists:projects,id'],'contract_id'=>['nullable','exists:contracts,id'],'invoice_no'=>['nullable','string','max:100'],'due_date'=>['nullable','date'],'account_id'=>['nullable','required_with:payment_amount','exists:financial_accounts,id'],'payment_amount'=>['nullable','integer','min:1'],'payment_method_id'=>['nullable','required_with:payment_amount','exists:payment_methods,id'],'transaction_date'=>['nullable','required_with:payment_amount','date'],'reference_no'=>['nullable','string','max:100'],'entry_status'=>['nullable',Rule::in(['pending','cleared'])],'allocation_type'=>['nullable',Rule::in(['project','item','subitem','task'])],'allocation_id'=>['nullable','required_with:allocation_amount','integer','min:1'],'allocation_amount'=>['nullable','integer','min:1'],'allocation_notes'=>['nullable','string']]; }
    private function authorizeProject(Request $request,?int $projectId): void { if ($request->user()->hasRole('admin')) return; abort_unless($projectId && $this->projects($request)->has($projectId),403); }
    private function projects(Request $request) { $q=Project::orderBy('title'); if ($request->user()->hasRole('project_manager')) $q->whereHas('managers',fn($x)=>$x->whereKey($request->user()->person_id)); return $q->pluck('title','id'); }
    private function options(Request $request): array { $projects=$this->projects($request); return ['accounts'=>FinancialAccount::where('is_active',true)->orderBy('name')->get(),'methods'=>PaymentMethod::where('is_active',true)->orderBy('sort_order')->get(),'categories'=>FinancialCategory::where('is_active',true)->orderBy('scope')->orderBy('sort_order')->get(),'people'=>Person::where('is_active',true)->orderBy('full_name')->get(),'projects'=>$projects,'contracts'=>Contract::whereIn('project_id',$projects->keys())->orderBy('title')->get(),'items'=>ProjectItem::orderBy('title')->get(),'subitems'=>ProjectSubitem::orderBy('title')->get(),'tasks'=>Task::orderBy('title')->get()]; }
    private function validateContractProject(array $data): void { if (! empty($data['contract_id'])) { $contract=Contract::findOrFail($data['contract_id']); abort_unless(! empty($data['project_id']) && (int) $contract->project_id === (int) $data['project_id'],422,'قرارداد باید متعلق به پروژه سند باشد.'); } }
}

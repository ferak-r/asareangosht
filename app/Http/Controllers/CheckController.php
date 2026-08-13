<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\FinancialAccount;
use App\Models\FinancialDocument;
use App\Models\FinancialEntry;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Project;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckController extends Controller
{
    public function __construct(private FinancialService $service) {}
    public function index(): View { return view('checks.index',['checks'=>Check::with('document')->orderBy('due_date')->paginate(25)]); }
    public function create(): View { return view('checks.form',$this->options()); }
    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['type'=>['required',Rule::in(['received','issued'])],'instrument_type'=>['required',Rule::in(['sayad','ordinary'])],'sayad_id'=>['nullable','digits:16','unique:checks,sayad_id'],'serial_number'=>['nullable','string','max:100'],'bank_name'=>['required','string','max:150'],'branch_name'=>['nullable','string','max:150'],'linked_account_id'=>['nullable','exists:financial_accounts,id'],'amount'=>['required','integer','min:1'],'issue_date'=>['nullable','date'],'due_date'=>['required','date'],'issuer_person_id'=>['nullable','exists:people,id'],'beneficiary_person_id'=>['nullable','exists:people,id'],'current_holder_person_id'=>['nullable','exists:people,id'],'status'=>['required','string','max:50'],'credit_color'=>['nullable',Rule::in(['white','yellow','orange','brown','red','unknown'])],'project_id'=>['nullable','exists:projects,id'],'financial_document_id'=>['nullable','exists:financial_documents,id'],'notes'=>['nullable','string'],'payment_method_id'=>['nullable','required_with:financial_document_id','exists:payment_methods,id']]);
        $check=DB::transaction(function() use($data,$request) { $check=Check::create($data+['created_by'=>$request->user()->id]); if (!empty($data['financial_document_id']) && !empty($data['linked_account_id'])) FinancialEntry::create(['financial_document_id'=>$data['financial_document_id'],'direction'=>$data['type']==='received'?'in':'out','account_id'=>$data['linked_account_id'],'amount'=>$data['amount'],'payment_method_id'=>$data['payment_method_id'],'transaction_date'=>$data['issue_date'] ?? now()->toDateString(),'check_id'=>$check->id,'status'=>'pending','created_by'=>$request->user()->id]); return $check; });
        if ($data['status']==='cleared') $this->service->updateCheckStatus($check,'cleared',$request->user()->id);
        return redirect()->route('checks.index')->with('success','چک ثبت شد.');
    }
    public function status(Request $request,Check $check): RedirectResponse { $data=$request->validate(['status'=>['required',Rule::in(['draft','issued','registered_in_sayad','awaiting_beneficiary_confirmation','confirmed','received','deposited_to_bank','transferred','cleared','bounced','bad_record_cleared','voided'])]]); $this->service->updateCheckStatus($check,$data['status'],$request->user()->id); return back()->with('success','وضعیت چک به‌روزرسانی شد.'); }
    private function options(): array { return ['people'=>Person::where('is_active',true)->orderBy('full_name')->get(),'accounts'=>FinancialAccount::where('is_active',true)->orderBy('name')->get(),'projects'=>Project::orderBy('title')->get(),'documents'=>FinancialDocument::whereNot('status','voided')->latest()->limit(200)->get(),'methods'=>PaymentMethod::where('is_active',true)->orderBy('sort_order')->get()]; }
}

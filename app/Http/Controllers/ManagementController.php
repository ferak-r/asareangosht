<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Contract;
use App\Models\FinancialAccount;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectSubitem;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManagementController extends Controller
{
    private const RESOURCES = [
        'people' => ['model'=>Person::class,'title'=>'اشخاص','roles'=>['admin','department_manager'],'fields'=>['type','full_name','mobile','national_id','registration_no','address','notes','is_active']],
        'departments' => ['model'=>Department::class,'title'=>'دپارتمان‌ها','roles'=>['admin'],'fields'=>['name','code','description','manager_person_id','is_active']],
        'accounts' => ['model'=>FinancialAccount::class,'title'=>'حساب‌ها','roles'=>['admin'],'fields'=>['owner_person_id','name','kind','bank_name','branch_name','account_number','card_number','iban','opening_balance','opening_balance_date','is_workshop_owned','is_active','notes']],
        'projects' => ['model'=>Project::class,'title'=>'پروژه‌ها','roles'=>['admin','project_manager','department_manager'],'fields'=>['code','title','customer_person_id','planned_start_date','planned_end_date','budget_amount','contract_total_amount','status','site_address','site_phone','description','is_active']],
        'contracts' => ['model'=>Contract::class,'title'=>'قراردادها و الحاقیه‌ها','roles'=>['admin','project_manager'],'fields'=>['project_id','contract_no','title','type','amount','signed_date','start_date','end_date','status','description']],
        'items' => ['model'=>ProjectItem::class,'title'=>'آیتم‌های پروژه','roles'=>['admin','project_manager'],'fields'=>['project_id','title','description','budget_amount','responsible_person_id','planned_start_date','planned_end_date','progress_percent','status','sort_order']],
        'subitems' => ['model'=>ProjectSubitem::class,'title'=>'زیرآیتم‌ها','roles'=>['admin','project_manager'],'fields'=>['project_item_id','title','description','budget_amount','responsible_person_id','planned_start_date','planned_end_date','progress_percent','status','sort_order']],
        'tasks' => ['model'=>Task::class,'title'=>'تسک‌ها','roles'=>['admin','employee','project_manager','department_manager'],'fields'=>['project_subitem_id','title','description','status','priority','progress_percent','planned_start_date','planned_end_date','estimated_days','due_date','return_reason']],
    ];

    public function index(Request $request, string $resource): View
    {
        $config = $this->config($resource); $this->guard($request, $config);
        $query = ($config['model'])::query();
        if ($resource === 'projects') $query = $this->visibleProjects($request, $query);
        if ($resource === 'contracts' && ! $request->user()->hasRole('admin')) $query->whereIn('project_id', $this->visibleProjects($request, Project::query())->select('id'));
        if ($resource === 'tasks' && $request->user()->hasRole('employee')) $query->whereHas('assignees', fn ($q) => $q->whereKey($request->user()->person_id));
        return view('management.index', ['resource'=>$resource, 'config'=>$config, 'records'=>$query->latest()->paginate(20)]);
    }

    public function create(Request $request, string $resource): View
    {
        $config = $this->config($resource); $this->guard($request, $config);
        return view('management.form', ['resource'=>$resource, 'config'=>$config, 'record'=>null, 'options'=>$this->options($request)]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $config = $this->config($resource); $this->guard($request, $config);
        $data = $request->validate($this->rules($resource));
        $this->enforceScope($request, $resource, $data);
        if (in_array($resource, ['projects', 'contracts'], true)) $data['created_by'] = $request->user()->id;
        ($config['model'])::create($data);
        return redirect()->route('management.index',$resource)->with('success', 'رکورد جدید ثبت شد.');
    }

    public function edit(Request $request, string $resource, int $id): View
    {
        $config = $this->config($resource); $this->guard($request, $config); $record = ($config['model'])::findOrFail($id);
        $this->authorizeRecord($request, $resource, $record);
        return view('management.form', compact('resource','config','record') + ['options'=>$this->options($request)]);
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $config = $this->config($resource); $this->guard($request, $config); $record = ($config['model'])::findOrFail($id);
        $this->authorizeRecord($request, $resource, $record); $data = $request->validate($this->rules($resource, $record)); $this->enforceScope($request, $resource, $data); if($resource==='tasks'&&$record->status==='completed'&&($data['status']??null)==='in_progress'&&!$request->user()->hasAnyRole(['admin','project_manager']))abort(403); $record->update($data);
        return redirect()->route('management.index',$resource)->with('success','تغییرات ذخیره شد.');
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $config=$this->config($resource); $this->guard($request,$config); $record=($config['model'])::findOrFail($id); $this->authorizeRecord($request,$resource,$record);
        if (! $request->user()->hasRole('admin')) abort(403); $record->delete();
        return back()->with('success','رکورد حذف شد.');
    }

    private function config(string $resource): array { abort_unless(isset(self::RESOURCES[$resource]),404); return self::RESOURCES[$resource]; }
    private function guard(Request $request, array $config): void { abort_unless($request->user()->hasAnyRole($config['roles']),403); }
    private function visibleProjects(Request $request, $query) { $user=$request->user(); if ($user->hasRole('project_manager')) $query->whereHas('managers',fn($q)=>$q->whereKey($user->person_id)); if ($user->hasRole('department_manager') && ! $user->hasRole('project_manager')) $query->whereHas('departments',fn($q)=>$q->where('manager_person_id',$user->person_id)); return $query; }
    private function authorizeRecord(Request $request,string $resource,Model $record): void { if ($resource==='projects') $this->authorize('update',$record); if (in_array($resource,['contracts','items','subitems','tasks'],true) && ! $request->user()->hasRole('admin')) { $project=$resource==='contracts' ? $record->project : ($resource==='items' ? $record->project : ($resource==='subitems' ? $record->projectItem->project : $record->projectSubitem->projectItem->project)); $this->authorize('update',$project); } }
    private function enforceScope(Request $request,string $resource,array $data): void { if (in_array($resource,['contracts','items','subitems','tasks'],true) && ! $request->user()->hasRole('admin')) { $projectId=in_array($resource,['contracts','items'],true) ? $data['project_id'] : ($resource==='subitems' ? ProjectItem::findOrFail($data['project_item_id'])->project_id : ProjectSubitem::findOrFail($data['project_subitem_id'])->projectItem->project_id); abort_unless($this->visibleProjects($request,Project::query())->whereKey($projectId)->exists(),403); } }
    private function rules(string $resource,?Model $record=null): array { $uniqueId=$record?->id; return match($resource) {
        'people'=>['type'=>['required',Rule::in(['individual','company'])],'full_name'=>['required','string','max:150'],'mobile'=>['required','string','max:20'],'national_id'=>['nullable','string','max:30',Rule::unique('people','national_id')->ignore($uniqueId)],'registration_no'=>['nullable','string','max:30',Rule::unique('people','registration_no')->ignore($uniqueId)],'address'=>['nullable','string'],'notes'=>['nullable','string'],'is_active'=>['boolean']],
        'departments'=>['name'=>['required','string','max:150',Rule::unique('departments','name')->ignore($uniqueId)],'code'=>['required','alpha_dash','max:50',Rule::unique('departments','code')->ignore($uniqueId)],'description'=>['nullable','string'],'manager_person_id'=>['nullable','exists:people,id'],'is_active'=>['boolean']],
        'accounts'=>['owner_person_id'=>['nullable','exists:people,id'],'name'=>['required','string','max:150'],'kind'=>['required',Rule::in(['bank_account','card','sheba','cashbox','personal'])],'bank_name'=>['nullable','string','max:100'],'branch_name'=>['nullable','string','max:100'],'account_number'=>['nullable','string','max:100'],'card_number'=>['nullable','string','max:30'],'iban'=>['nullable','string','max:40'],'opening_balance'=>['required','integer'],'opening_balance_date'=>['nullable','date'],'is_workshop_owned'=>['boolean'],'is_active'=>['boolean'],'notes'=>['nullable','string']],
        'projects'=>['code'=>['required','string','max:50',Rule::unique('projects','code')->ignore($uniqueId)],'title'=>['required','string','max:150'],'customer_person_id'=>['nullable','exists:people,id'],'planned_start_date'=>['nullable','date'],'planned_end_date'=>['nullable','date','after_or_equal:planned_start_date'],'budget_amount'=>['required','integer','min:0'],'contract_total_amount'=>['required','integer','min:0'],'status'=>['required',Rule::in(['draft','pending_start','in_progress','paused','delivered','cancelled'])],'site_address'=>['nullable','string'],'site_phone'=>['nullable','string','max:20'],'description'=>['nullable','string'],'is_active'=>['boolean']],
        'contracts'=>['project_id'=>['required','exists:projects,id'],'contract_no'=>['nullable','string','max:100'],'title'=>['required','string','max:180'],'type'=>['required',Rule::in(['main','addendum'])],'amount'=>['required','integer','min:1'],'signed_date'=>['nullable','date'],'start_date'=>['nullable','date'],'end_date'=>['nullable','date','after_or_equal:start_date'],'status'=>['required',Rule::in(['draft','active','completed','cancelled'])],'description'=>['nullable','string']],
        'items'=>['project_id'=>['required','exists:projects,id'],'title'=>['required','string','max:150'],'description'=>['nullable','string'],'budget_amount'=>['required','integer','min:0'],'responsible_person_id'=>['nullable','exists:people,id'],'planned_start_date'=>['nullable','date'],'planned_end_date'=>['nullable','date'],'progress_percent'=>['required','integer','between:0,100'],'status'=>['required','string','max:30'],'sort_order'=>['required','integer','min:0']],
        'subitems'=>['project_item_id'=>['required','exists:project_items,id'],'title'=>['required','string','max:150'],'description'=>['nullable','string'],'budget_amount'=>['required','integer','min:0'],'responsible_person_id'=>['nullable','exists:people,id'],'planned_start_date'=>['nullable','date'],'planned_end_date'=>['nullable','date'],'progress_percent'=>['required','integer','between:0,100'],'status'=>['required','string','max:30'],'sort_order'=>['required','integer','min:0']],
        'tasks'=>['project_subitem_id'=>['required','exists:project_subitems,id'],'title'=>['required','string','max:150'],'description'=>['nullable','string'],'status'=>['required',Rule::in(['new','in_progress','paused','completed','cancelled'])],'priority'=>['required',Rule::in(['low','normal','high','urgent'])],'progress_percent'=>['required','integer','between:0,100'],'planned_start_date'=>['nullable','date'],'planned_end_date'=>['nullable','date'],'estimated_days'=>['nullable','integer','min:0'],'due_date'=>['nullable','date','after_or_equal:planned_start_date'],'return_reason'=>[$record?->status==='completed'?'required_if:status,in_progress':'nullable','nullable','string','max:1000']],
    }; }
    private function options(Request $request): array { $projects=$this->visibleProjects($request, Project::orderBy('title')); return ['people'=>Person::orderBy('full_name')->pluck('full_name','id'),'departments'=>Department::orderBy('name')->pluck('name','id'),'projects'=>$projects->pluck('title','id'),'items'=>ProjectItem::whereIn('project_id',$projects->select('id'))->orderBy('title')->pluck('title','id'),'subitems'=>ProjectSubitem::whereIn('project_item_id',ProjectItem::whereIn('project_id',$projects->select('id'))->select('id'))->orderBy('title')->pluck('title','id')]; }
}

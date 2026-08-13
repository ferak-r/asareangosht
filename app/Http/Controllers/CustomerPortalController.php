<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\FinancialDocument;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPortalController extends Controller
{
    private const REPORTS = ['progress','financial_summary'];

    public function index(Request $request): View { return view('customer.index',['projects'=>Project::where('customer_person_id',$request->user()->person_id)->get()]); }

    public function show(Request $request,Project $project): View
    {
        $this->memberCanAccess($request,$project); $enabled=$project->reportPermissions()->where('is_enabled',true)->pluck('report_key');
        $financial=$enabled->contains('financial_summary') ? FinancialDocument::where('project_id',$project->id)->where('status','!=','voided')->selectRaw('type, SUM(net_amount) total')->groupBy('type')->pluck('total','type') : collect();
        return view('customer.show',['project'=>$project->load(['items.subitems.tasks','comments.author','attachments']),'enabled'=>$enabled,'financial'=>$financial]);
    }

    public function permission(Request $request,Project $project): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'),403); $data=$request->validate(['report_key'=>['required',Rule::in(self::REPORTS)],'is_enabled'=>['required','boolean']]);
        $project->reportPermissions()->updateOrCreate(['report_key'=>$data['report_key']],['is_enabled'=>$data['is_enabled']]); return back()->with('success','دسترسی گزارش مشتری ذخیره شد.');
    }

    public function comment(Request $request,Project $project): RedirectResponse
    {
        $this->memberCanAccess($request,$project); $data=$request->validate(['body'=>['required','string','max:3000']]);
        $project->comments()->create(['body'=>$data['body'],'created_by'=>$request->user()->id,'visibility'=>$request->user()->hasRole('customer')?'customer_visible':'internal']); return back()->with('success','نظر ثبت شد.');
    }

    public function upload(Request $request,Project $project): RedirectResponse
    {
        $this->memberCanAccess($request,$project); $data=$request->validate(['file'=>['required','file','max:10240','mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],'description'=>['nullable','string','max:500']]); $file=$data['file']; $path=$file->store("projects/{$project->id}",'local');
        $project->attachments()->create(['disk'=>'local','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType()?:'application/octet-stream','size_bytes'=>$file->getSize(),'uploaded_by'=>$request->user()->id,'visibility'=>$request->user()->hasRole('customer')?'customer_visible':'project_members','description'=>$data['description']??null]); return back()->with('success','فایل بارگذاری شد.');
    }

    public function download(Request $request,Attachment $attachment): StreamedResponse
    {
        abort_unless($attachment->attachable_type===Project::class,404); $project=Project::findOrFail($attachment->attachable_id); $this->memberCanAccess($request,$project);
        if($request->user()->hasRole('customer')) abort_unless($attachment->visibility==='customer_visible',403);
        return Storage::disk($attachment->disk)->download($attachment->path,$attachment->original_name);
    }

    private function customerOwns(Request $request,Project $project): void { abort_unless($request->user()->hasRole('customer') && $request->user()->person_id===$project->customer_person_id,403); }
    private function memberCanAccess(Request $request,Project $project): void { if($request->user()->hasRole('admin'))return; if($request->user()->hasRole('customer')){$this->customerOwns($request,$project);return;} $this->authorize('view',$project); }
}

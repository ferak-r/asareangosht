<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}
    public function index(Request $request,string $type='financial'): View { $projects=Project::orderBy('title'); if($request->user()->hasRole('project_manager'))$projects->whereHas('managers',fn($q)=>$q->whereKey($request->user()->person_id)); $types=$request->user()->hasRole('admin')?ReportService::TYPES:array_intersect_key(ReportService::TYPES,array_flip(['financial','projects','payroll'])); return view('reports.index',['type'=>$type,'types'=>$types,'rows'=>$this->reports->rows($type,$request),'projects'=>$projects->pluck('title','id')]); }
    public function export(Request $request,string $type): StreamedResponse
    {
        $rows=$this->reports->rows($type,$request); app(AuditService::class)->record('report_exported',auth()->user(),[],['report'=>$type,'filters'=>$request->query()]);
        $name='asarangosht-'.$type.'-'.now()->format('Ymd-His').'.csv';
        return response()->streamDownload(function()use($rows,$type,$request){$out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,['گزارش',ReportService::TYPES[$type]]); fputcsv($out,['تولیدکننده',auth()->user()->name,'زمان',now()->format('Y/m/d H:i')]); if($rows->isNotEmpty()){fputcsv($out,array_keys($rows->first()));foreach($rows as $row)fputcsv($out,$row);} fclose($out);},$name,['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function print(Request $request,string $type): View { app(AuditService::class)->record('report_printed',auth()->user(),[],['report'=>$type,'filters'=>$request->query()]); return view('reports.print',['title'=>ReportService::TYPES[$type]??$type,'rows'=>$this->reports->rows($type,$request)]); }
    public function audit(): View { return view('reports.audit',['logs'=>AuditLog::with('user')->latest('created_at')->paginate(50)]); }
}

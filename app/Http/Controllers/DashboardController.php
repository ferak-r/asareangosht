<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\FinancialDocument;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user=$request->user();
        if ($user->hasRole('customer')) return view('dashboard',['customerProjects'=>Project::where('customer_person_id',$user->person_id)->get()]);
        $projects=Project::query(); $tasks=Task::query();
        if ($user->hasRole('project_manager')) { $projects->whereHas('managers',fn($q)=>$q->whereKey($user->person_id)); $tasks->whereHas('projectSubitem.projectItem.project.managers',fn($q)=>$q->whereKey($user->person_id)); }
        if ($user->hasRole('employee')) $tasks->whereHas('assignees',fn($q)=>$q->whereKey($user->person_id));
        $documents=FinancialDocument::where('status','!=','voided'); if($user->hasRole('project_manager'))$documents->whereHas('project.managers',fn($q)=>$q->whereKey($user->person_id));
        return view('dashboard',['projectCount'=>(clone $projects)->where('is_active',true)->count(),'taskCount'=>(clone $tasks)->whereNotIn('status',['completed','cancelled'])->count(),'overdueTasks'=>(clone $tasks)->whereDate('due_date','<',today())->whereNotIn('status',['completed','cancelled'])->count(),'expenseTotal'=>$user->hasAnyRole(['admin','project_manager'])?(clone $documents)->where('type','expense')->sum('net_amount'):null,'incomeTotal'=>$user->hasAnyRole(['admin','project_manager'])?(clone $documents)->where('type','income')->sum('net_amount'):null,'dueChecks'=>$user->hasRole('admin')?Check::whereBetween('due_date',[today(),today()->addDays(7)])->whereNotIn('status',['cleared','voided'])->count():null]);
    }
}

<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskStatusHistory;

class TaskProgressObserver
{
    public function updating(Task $task): void
    {
        if ($task->status === 'completed') { $task->progress_percent=100; $task->completed_at ??= now(); $task->completed_by ??= auth()->id(); }
        if ($task->isDirty('status') && $task->getOriginal('status')==='completed' && $task->status==='in_progress') { $task->returned_at=now(); $task->returned_by=auth()->id(); }
    }
    public function updated(Task $task): void
    {
        if ($task->wasChanged(['status','progress_percent'])) TaskStatusHistory::create(['task_id'=>$task->id,'old_status'=>$task->getOriginal('status'),'new_status'=>$task->status,'old_progress'=>$task->getOriginal('progress_percent'),'new_progress'=>$task->progress_percent,'note'=>$task->return_reason,'changed_by'=>auth()->id()]);
        $subitem=$task->projectSubitem()->with('tasks')->first(); if(!$subitem)return; $active=$subitem->tasks->where('status','!=','cancelled'); $weight=$active->sum(fn($t)=>(int)($t->estimated_days?:1)); $progress=$weight?round($active->sum(fn($t)=>(int)$t->progress_percent*(int)($t->estimated_days?:1))/$weight):0; $subitem->update(['progress_percent'=>$progress,'status'=>$active->isNotEmpty()&&$active->every(fn($t)=>$t->status==='completed')?'completed':$subitem->status]);
        $item=$subitem->projectItem()->with('subitems')->first(); if(!$item)return; $children=$item->subitems->where('status','!=','cancelled'); $item->update(['progress_percent'=>$children->count()?round($children->avg('progress_percent')):0,'status'=>$children->isNotEmpty()&&$children->every(fn($s)=>$s->status==='completed')?'completed':$item->status]);
    }
}

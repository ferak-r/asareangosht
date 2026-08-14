<?php

namespace App\Http\Controllers;

use App\Models\FinancialDocument;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectOverviewController extends Controller
{
    public function __invoke(Request $request, Project $project): View
    {
        $this->authorize('view', $project);
        $project->load([
            'customer', 'departments', 'managers', 'contracts',
            'items' => fn ($query) => $query->orderBy('sort_order'),
            'items.subitems' => fn ($query) => $query->orderBy('sort_order'),
            'items.subitems.tasks.assignees',
        ]);

        $tasks = $project->items->flatMap->subitems->flatMap->tasks;
        $financial = FinancialDocument::where('project_id', $project->id)
            ->where('status', '!=', 'voided')
            ->selectRaw('type, SUM(net_amount) AS total')
            ->groupBy('type')->pluck('total', 'type');

        return view('projects.show', [
            'project' => $project,
            'tasks' => $tasks,
            'progress' => $tasks->isEmpty() ? (int) $project->items->avg('progress_percent') : (int) round($tasks->avg('progress_percent')),
            'financial' => $financial,
            'overdueCount' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->filter(fn ($task) => $task->due_date?->isPast())->count(),
        ]);
    }
}

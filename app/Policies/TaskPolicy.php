<?php
namespace App\Policies;
use App\Models\Task;
use App\Models\User;
class TaskPolicy { public function view(User $user, Task $task): bool { if ($user->hasRole('employee')) return $task->assignees()->whereKey($user->person_id)->exists(); return $user->can('view', $task->projectSubitem->projectItem->project); } public function update(User $user, Task $task): bool { if ($user->hasRole('employee')) return $task->assignees()->whereKey($user->person_id)->exists(); return $user->can('update', $task->projectSubitem->projectItem->project); } }

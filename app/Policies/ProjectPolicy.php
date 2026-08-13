<?php
namespace App\Policies;
use App\Models\Project;
use App\Models\User;
class ProjectPolicy { public function view(User $user, Project $project): bool { if ($user->hasRole('customer')) return $user->person_id === $project->customer_person_id; if ($user->hasRole('project_manager')) return $project->managers()->whereKey($user->person_id)->exists(); if ($user->hasRole('department_manager')) return $project->departments()->where('manager_person_id',$user->person_id)->exists(); return false; } public function update(User $user, Project $project): bool { return $user->hasRole('project_manager') && $project->managers()->whereKey($user->person_id)->exists(); } }

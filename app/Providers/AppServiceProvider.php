<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use App\Observers\AuditObserver;
use App\Observers\TaskProgressObserver;
use App\Models\FinancialDocument;
use App\Models\FinancialEntry;
use App\Models\Check;
use App\Models\Person;
use App\Models\Department;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('admin') ? true : null);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        foreach ([Project::class, Task::class, FinancialDocument::class, FinancialEntry::class, Check::class, Person::class, Department::class] as $model) {
            $model::observe(AuditObserver::class);
        }
        Task::observe(TaskProgressObserver::class);
    }
}

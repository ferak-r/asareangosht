<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectSubitem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_tasks_roll_progress_up_and_return_creates_history(): void
    {
        Role::findOrCreate('project_manager','web'); $manager=User::factory()->create(['is_active'=>true]); $manager->assignRole('project_manager'); $this->actingAs($manager);
        $project=Project::create(['code'=>'P-TASK','title'=>'تست','status'=>'draft','budget_amount'=>0,'contract_total_amount'=>0]);
        $item=ProjectItem::create(['project_id'=>$project->id,'title'=>'آیتم','budget_amount'=>0,'progress_percent'=>0,'status'=>'new','sort_order'=>0]);
        $sub=ProjectSubitem::create(['project_item_id'=>$item->id,'title'=>'زیرآیتم','budget_amount'=>0,'progress_percent'=>0,'status'=>'new','sort_order'=>0]);
        $first=Task::create(['project_subitem_id'=>$sub->id,'title'=>'اول','status'=>'new','priority'=>'normal','progress_percent'=>0,'estimated_days'=>1]);
        $second=Task::create(['project_subitem_id'=>$sub->id,'title'=>'دوم','status'=>'new','priority'=>'normal','progress_percent'=>0,'estimated_days'=>1]);
        $first->update(['status'=>'completed']); $second->update(['status'=>'completed']);
        $this->assertSame(100,(int)$sub->fresh()->progress_percent); $this->assertSame('completed',$sub->fresh()->status); $this->assertSame(100,(int)$item->fresh()->progress_percent);
        $first->update(['status'=>'in_progress','progress_percent'=>60,'return_reason'=>'نیازمند اصلاح']);
        $this->assertDatabaseHas('task_status_histories',['task_id'=>$first->id,'old_status'=>'completed','new_status'=>'in_progress','note'=>'نیازمند اصلاح']);
    }
}

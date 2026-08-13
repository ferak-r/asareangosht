<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Project;
use App\Models\Contract;
use App\Models\ProjectCustomerReportPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach(['admin','employee','project_manager','department_manager','customer'] as $role) Role::findOrCreate($role,'web');
    }

    public function test_only_admin_can_open_user_creation(): void
    {
        $admin=User::factory()->create(['is_active'=>true]); $admin->assignRole('admin');
        $employee=User::factory()->create(['is_active'=>true]); $employee->assignRole('employee');
        $customer=User::factory()->create(['is_active'=>true]); $customer->assignRole('customer');
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->actingAs($employee)->get(route('users.create'))->assertForbidden();
        $this->actingAs($customer)->get(route('users.create'))->assertForbidden();
    }

    public function test_customer_sees_only_own_project_and_enabled_report(): void
    {
        $person=Person::create(['type'=>'individual','full_name'=>'مشتری','mobile'=>'09120000001']);
        $other=Person::create(['type'=>'individual','full_name'=>'دیگری','mobile'=>'09120000002']);
        $user=User::factory()->create(['person_id'=>$person->id,'is_active'=>true]); $user->assignRole('customer');
        $own=Project::create(['code'=>'OWN','title'=>'پروژه خودم','customer_person_id'=>$person->id,'status'=>'draft','budget_amount'=>0,'contract_total_amount'=>0]);
        $foreign=Project::create(['code'=>'OTHER','title'=>'پروژه دیگر','customer_person_id'=>$other->id,'status'=>'draft','budget_amount'=>0,'contract_total_amount'=>0]);
        ProjectCustomerReportPermission::create(['project_id'=>$own->id,'report_key'=>'progress','is_enabled'=>true]);
        $this->actingAs($user)->get(route('customer.show',$own))->assertOk()->assertSee('پیشرفت');
        $this->actingAs($user)->get(route('customer.show',$foreign))->assertForbidden();
        $this->actingAs($user)->get(route('financial.index'))->assertForbidden();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user=User::factory()->create(['is_active'=>false]); $user->assignRole('employee');
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_project_manager_cannot_list_contracts_for_other_projects(): void
    {
        $managerPerson=Person::create(['type'=>'individual','full_name'=>'مدیر پروژه','mobile'=>'09120000003']);
        $otherPerson=Person::create(['type'=>'individual','full_name'=>'مدیر دیگر','mobile'=>'09120000004']);
        $manager=User::factory()->create(['person_id'=>$managerPerson->id,'is_active'=>true]); $manager->assignRole('project_manager');
        $own=Project::create(['code'=>'OWN-C','title'=>'پروژه مجاز','customer_person_id'=>null,'status'=>'draft','budget_amount'=>0,'contract_total_amount'=>0]);
        $foreign=Project::create(['code'=>'OTHER-C','title'=>'پروژه غیرمجاز','customer_person_id'=>null,'status'=>'draft','budget_amount'=>0,'contract_total_amount'=>0]);
        $own->managers()->attach($managerPerson->id, ['is_primary'=>true]);
        $foreign->managers()->attach($otherPerson->id, ['is_primary'=>true]);
        Contract::create(['project_id'=>$own->id,'title'=>'قرارداد مجاز','type'=>'main','amount'=>100,'status'=>'draft']);
        Contract::create(['project_id'=>$foreign->id,'title'=>'قرارداد غیرمجاز','type'=>'main','amount'=>100,'status'=>'draft']);

        $this->actingAs($manager)->get(route('management.index','contracts'))
            ->assertOk()->assertSee('قرارداد مجاز')->assertDontSee('قرارداد غیرمجاز');
    }
}

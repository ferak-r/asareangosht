<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['admin', 'employee', 'department_manager', 'project_manager', 'customer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        DB::table('departments')->upsert([
            ['name' => 'کارگاه', 'code' => 'workshop', 'is_active' => true],
            ['name' => 'طراحی', 'code' => 'design', 'is_active' => true],
            ['name' => 'اجرا/ساخت', 'code' => 'production', 'is_active' => true],
            ['name' => 'نصب', 'code' => 'installation', 'is_active' => true],
        ], ['code'], ['name', 'is_active']);

        DB::table('payment_methods')->upsert([
            ['name' => 'نقدی', 'code' => 'cash', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'کارت به کارت', 'code' => 'card_to_card', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'انتقال بانکی', 'code' => 'bank_transfer', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'چک', 'code' => 'check', 'sort_order' => 4, 'is_active' => true],
            ['name' => 'حواله', 'code' => 'remittance', 'sort_order' => 5, 'is_active' => true],
            ['name' => 'سایر', 'code' => 'other', 'sort_order' => 6, 'is_active' => true],
        ], ['code'], ['name', 'sort_order', 'is_active']);

        $expenseCategories = [
            ['scope' => 'expense', 'title' => 'خدمات', 'sort_order' => 1, 'is_active' => true],
            ['scope' => 'expense', 'title' => 'کالا', 'sort_order' => 2, 'is_active' => true],
            ['scope' => 'expense', 'title' => 'پشتیبانی', 'sort_order' => 3, 'is_active' => true],
            ['scope' => 'expense', 'title' => 'غذا', 'sort_order' => 4, 'is_active' => true],
            ['scope' => 'expense', 'title' => 'حمل‌ونقل', 'sort_order' => 5, 'is_active' => true],
        ];

        foreach ($expenseCategories as $category) {
            DB::table('financial_categories')->updateOrInsert(
                ['scope' => $category['scope'], 'parent_id' => null, 'title' => $category['title']],
                $category,
            );
        }

        DB::table('financial_categories')->updateOrInsert(
            ['scope' => 'income', 'parent_id' => null, 'title' => 'دریافت قرارداد'],
            ['scope' => 'income', 'title' => 'دریافت قرارداد', 'sort_order' => 1, 'is_active' => true],
        );
        DB::table('financial_categories')->updateOrInsert(
            ['scope' => 'income', 'parent_id' => null, 'title' => 'فروش کارگاه'],
            ['scope' => 'income', 'title' => 'فروش کارگاه', 'sort_order' => 2, 'is_active' => true],
        );

        if (env('INITIAL_ADMIN_EMAIL') && env('INITIAL_ADMIN_PASSWORD')) {
            $admin = User::firstOrCreate(['email' => env('INITIAL_ADMIN_EMAIL')], [
                'name' => env('INITIAL_ADMIN_NAME', 'مدیر سامانه'),
                'mobile' => env('INITIAL_ADMIN_MOBILE') ?: null,
                'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD')),
                'is_active' => true,
            ]);
            $admin->syncRoles(['admin']);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Root Accounts
        $assets = Account::create(['account_code' => '1', 'name' => 'الأصول', 'type' => 'asset', 'is_postable' => false]);
        $liabilities = Account::create(['account_code' => '2', 'name' => 'الخصوم', 'type' => 'liability', 'is_postable' => false]);
        $equity = Account::create(['account_code' => '3', 'name' => 'حقوق الملكية', 'type' => 'equity', 'is_postable' => false]);
        $revenue = Account::create(['account_code' => '4', 'name' => 'الإيرادات', 'type' => 'revenue', 'is_postable' => false]);
        $expenses = Account::create(['account_code' => '5', 'name' => 'المصروفات', 'type' => 'expense', 'is_postable' => false]);

        // 2. Assets Children
        Account::create(['account_code' => '1101', 'name' => 'الصندوق الرئيسي', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);
        Account::create(['account_code' => '1102', 'name' => 'البنك', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);
        Account::create(['account_code' => '1201', 'name' => 'ذمم العملاء', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);
        Account::create(['account_code' => '1202', 'name' => 'سلف الموظفين', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);
        Account::create(['account_code' => '1203', 'name' => 'عهد مندوبي مبيعات', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);
        Account::create(['account_code' => '1301', 'name' => 'مخزون بضائع', 'type' => 'asset', 'parent_id' => $assets->id, 'is_postable' => true]);

        // 3. Liabilities Children
        Account::create(['account_code' => '2101', 'name' => 'ذمم الموردين', 'type' => 'liability', 'parent_id' => $liabilities->id, 'is_postable' => true]);
        Account::create(['account_code' => '2201', 'name' => 'رواتب مستحقة', 'type' => 'liability', 'parent_id' => $liabilities->id, 'is_postable' => true]);
        Account::create(['account_code' => '2202', 'name' => 'مكافآت مندوبي مستحقة', 'type' => 'liability', 'parent_id' => $liabilities->id, 'is_postable' => true]);

        // 4. Equity Children
        Account::create(['account_code' => '3101', 'name' => 'رأس المال', 'type' => 'equity', 'parent_id' => $equity->id, 'is_postable' => true]);
        Account::create(['account_code' => '3201', 'name' => 'أرباح محتجزة', 'type' => 'equity', 'parent_id' => $equity->id, 'is_postable' => true]);

        // 5. Revenue Children
        Account::create(['account_code' => '4101', 'name' => 'مبيعات', 'type' => 'revenue', 'parent_id' => $revenue->id, 'is_postable' => true]);

        // 6. Expenses Children
        Account::create(['account_code' => '5101', 'name' => 'رواتب موظفين', 'type' => 'expense', 'parent_id' => $expenses->id, 'is_postable' => true]);
        Account::create(['account_code' => '5102', 'name' => 'رواتب مندوبي مبيعات', 'type' => 'expense', 'parent_id' => $expenses->id, 'is_postable' => true]);
        Account::create(['account_code' => '5103', 'name' => 'عمولات/مكافآت مندوبي', 'type' => 'expense', 'parent_id' => $expenses->id, 'is_postable' => true]);
        Account::create(['account_code' => '5201', 'name' => 'مصاريف تشغيل', 'type' => 'expense', 'parent_id' => $expenses->id, 'is_postable' => true]);
        Account::create(['account_code' => '5202', 'name' => 'مصاريف إدارية', 'type' => 'expense', 'parent_id' => $expenses->id, 'is_postable' => true]);
    }
}

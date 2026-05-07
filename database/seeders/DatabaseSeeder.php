<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\MenuEntry;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage members',
            'manage expenses',
            'manage menu',
            'manage payments',
            'manage users',
        ];

        $permissionModels = collect($permissions)->mapWithKeys(function ($permission) {
            $model = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            return [$permission => $model];
        });

        $adminRole = Role::findOrCreate('admin', 'web');
        $managerRole = Role::findOrCreate('manager', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');

        $adminRole->syncPermissions($permissionModels->values());
        $managerRole->syncPermissions($permissionModels->values());
        $staffRole->syncPermissions([
            $permissionModels['view dashboard'],
        ]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminEmail = env('SEED_ADMIN_EMAIL');
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        $admin = $adminEmail
            ? User::query()->firstOrNew(['email' => $adminEmail])
            : User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->first();

        if (! $admin) {
            if (! $adminEmail || ! $adminPassword) {
                throw new \RuntimeException('SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD are required when creating the initial admin user.');
            }

            $admin = new User(['email' => $adminEmail]);
        }

        $admin->fill([
            'name' => $admin->name ?: 'Mess Admin',
            'phone' => $admin->phone ?: '0300-0000000',
        ]);

        if (! $admin->exists && ! $adminPassword) {
            throw new \RuntimeException('SEED_ADMIN_PASSWORD is required when creating the initial admin user.');
        }

        if ($adminPassword) {
            $admin->password = Hash::make($adminPassword);
        }

        $admin->save();
        $admin->syncRoles([$adminRole]);

        $members = collect([
            ['name' => 'A. Waheed', 'room_no' => 'G-101', 'bed_no' => '1', 'monthly_fee' => 20000, 'opening_balance' => 3622, 'joined_on' => now()->subMonths(8)],
            ['name' => 'Ghaffar', 'room_no' => 'G-102', 'bed_no' => '2', 'monthly_fee' => 20000, 'opening_balance' => -6028, 'joined_on' => now()->subMonths(5)],
            ['name' => 'Talha', 'room_no' => 'G-103', 'bed_no' => '1', 'monthly_fee' => 20000, 'opening_balance' => -1805, 'joined_on' => now()->subMonths(6)],
            ['name' => 'Haris', 'room_no' => 'G-104', 'bed_no' => '1', 'monthly_fee' => 10000, 'opening_balance' => 25000, 'joined_on' => now()->subMonths(4)],
            ['name' => 'Ali Akbar', 'room_no' => 'G-105', 'bed_no' => '2', 'monthly_fee' => 20000, 'opening_balance' => 1172, 'joined_on' => now()->subMonths(7)],
            ['name' => 'Usama', 'room_no' => 'G-106', 'bed_no' => '1', 'monthly_fee' => 20000, 'opening_balance' => 36512, 'joined_on' => now()->subMonths(10)],
            ['name' => 'Faraz', 'room_no' => 'G-107', 'bed_no' => '2', 'monthly_fee' => 0, 'opening_balance' => 6288, 'joined_on' => now()->subMonths(3)],
            ['name' => 'Shahzaib', 'room_no' => 'G-108', 'bed_no' => '1', 'monthly_fee' => 20000, 'opening_balance' => 5275, 'joined_on' => now()->subMonths(4)],
            ['name' => 'A. Sattar', 'room_no' => 'G-109', 'bed_no' => '2', 'monthly_fee' => 20000, 'opening_balance' => -281, 'joined_on' => now()->subMonths(9)],
            ['name' => 'Muneeb', 'room_no' => 'G-110', 'bed_no' => '1', 'monthly_fee' => 20000, 'opening_balance' => 3487, 'joined_on' => now()->subMonths(11)],
        ])->map(fn ($member) => Member::query()->updateOrCreate(
            ['name' => $member['name']],
            $member + ['is_active' => true]
        ));

        $categories = collect([
            ['name' => 'Rent', 'type' => 'fixed', 'color' => '#264653'],
            ['name' => 'Arbab Salary', 'type' => 'fixed', 'color' => '#2a9d8f'],
            ['name' => 'Internet Bill', 'type' => 'fixed', 'color' => '#287271'],
            ['name' => 'Electricity Bill', 'type' => 'fixed', 'color' => '#8ab17d'],
            ['name' => 'Gas Bill', 'type' => 'fixed', 'color' => '#e9c46a'],
            ['name' => 'Chicken', 'type' => 'daily', 'color' => '#f4a261'],
            ['name' => 'Maintenance', 'type' => 'fixed', 'color' => '#577590'],
            ['name' => 'Water', 'type' => 'daily', 'color' => '#277da1'],
            ['name' => 'Milk', 'type' => 'daily', 'color' => '#8d99ae'],
            ['name' => 'Eggs', 'type' => 'daily', 'color' => '#ef476f'],
            ['name' => 'Yogurt', 'type' => 'daily', 'color' => '#4361ee'],
            ['name' => 'Zeera', 'type' => 'daily', 'color' => '#bc6c25'],
        ])->mapWithKeys(fn ($category) => [
            $category['name'] => ExpenseCategory::query()->updateOrCreate(['name' => $category['name']], $category),
        ]);

        $monthStart = Carbon::now()->startOfMonth();

        foreach ([
            ['Chicken', 'Alu Bukhare', 0, 1],
            ['Water', 'Water', 2900, 2],
            ['Yogurt', 'Yogurt', 2300, 3],
            ['Zeera', 'Zeera', 0, 4],
            ['Rent', 'Monthly Rent', 35000, 5],
            ['Internet Bill', 'Internet Bill', 3300, 6],
            ['Chicken', 'Chicken', 3880, 7],
            ['Water', 'Water Tanker', 2900, 8],
            ['Milk', 'Milk', 2600, 9],
            ['Eggs', 'Eggs', 1190, 10],
        ] as [$categoryName, $itemName, $amount, $day]) {
            Expense::query()->updateOrCreate(
                ['item_name' => $itemName, 'spent_on' => $monthStart->copy()->addDays($day)->toDateString()],
                [
                    'expense_category_id' => $categories[$categoryName]->id,
                    'created_by' => $admin->id,
                    'amount' => $amount,
                    'notes' => 'Seeded from manual sheet sample.',
                ]
            );
        }

        foreach (range(0, 9) as $offset) {
            MenuEntry::query()->updateOrCreate(
                ['served_on' => $monthStart->copy()->addDays($offset)->toDateString()],
                [
                    'meal_slot' => 'dinner',
                    'dish_name' => $offset % 3 === 0 ? 'Alu Bukhare' : ($offset % 3 === 1 ? 'Daal Chawal' : 'Chicken Karahi'),
                    'estimated_cost' => 1200 + ($offset * 50),
                    'notes' => 'Weekly planned menu',
                ]
            );
        }

        foreach ($members as $index => $member) {
            Payment::query()->updateOrCreate(
                ['member_id' => $member->id, 'paid_on' => $monthStart->copy()->addDays($index + 1)->toDateString()],
                [
                    'received_by' => $admin->id,
                    'amount' => $member->monthly_fee > 0 ? max(8000, $member->monthly_fee - 1000) : 0,
                    'payment_method' => $index % 2 === 0 ? 'bank' : 'cash',
                    'notes' => 'Monthly payment',
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'View assets', 'slug' => 'assets.view', 'module' => 'assets'],
            ['name' => 'Create assets', 'slug' => 'assets.create', 'module' => 'assets'],
            ['name' => 'Update assets', 'slug' => 'assets.update', 'module' => 'assets'],
            ['name' => 'Assign assets', 'slug' => 'assets.assign', 'module' => 'assets'],
            ['name' => 'View assignments', 'slug' => 'assignments.view', 'module' => 'assignments'],
            ['name' => 'Transfer assets', 'slug' => 'assets.transfer', 'module' => 'assets'],
            ['name' => 'Print asset labels', 'slug' => 'assets.labels.print', 'module' => 'assets'],
            ['name' => 'Submit requests', 'slug' => 'requests.create', 'module' => 'requests'],
            ['name' => 'Approve requests', 'slug' => 'requests.approve', 'module' => 'requests'],
            ['name' => 'Allocate request items', 'slug' => 'requests.allocate', 'module' => 'requests'],
            ['name' => 'Confirm handovers', 'slug' => 'handovers.confirm', 'module' => 'handovers'],
            ['name' => 'Report incidents', 'slug' => 'incidents.create', 'module' => 'incidents'],
            ['name' => 'Manage maintenance', 'slug' => 'maintenance.manage', 'module' => 'maintenance'],
            ['name' => 'Review maintenance outcomes', 'slug' => 'maintenance.review', 'module' => 'maintenance'],
            ['name' => 'Process approved spare requisitions', 'slug' => 'maintenance.spares.procure', 'module' => 'maintenance'],
            ['name' => 'Run inspections', 'slug' => 'inspections.manage', 'module' => 'inspections'],
            ['name' => 'Propose asset retirement', 'slug' => 'disposals.propose', 'module' => 'disposals'],
            ['name' => 'Review asset retirement', 'slug' => 'disposals.review', 'module' => 'disposals'],
            ['name' => 'Approve asset disposal', 'slug' => 'disposals.approve', 'module' => 'disposals'],
            ['name' => 'Finalize asset disposal', 'slug' => 'disposals.finalize', 'module' => 'disposals'],
            ['name' => 'View reports', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Manage classifications', 'slug' => 'classifications.manage', 'module' => 'administration'],
            ['name' => 'Manage organization', 'slug' => 'organization.manage', 'module' => 'administration'],
            ['name' => 'Manage users and access', 'slug' => 'access.manage', 'module' => 'administration'],
            ['name' => 'View audit trail', 'slug' => 'audit.view', 'module' => 'administration'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [...$permission, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $roles = [
            ['name' => 'System Administrator', 'slug' => 'system-administrator', 'description' => 'Full EIMS configuration and access control.', 'is_system' => true],
            ['name' => 'Procurement Officer', 'slug' => 'procurement-officer', 'description' => 'Handles sourcing, allocation and acquisition records.', 'is_system' => true],
            ['name' => 'Maintenance Officer', 'slug' => 'maintenance-officer', 'description' => 'Handles incidents, inspections and maintenance jobs.', 'is_system' => true],
            ['name' => 'Maintenance Review Officer', 'slug' => 'maintenance-review-officer', 'description' => 'Reviews maintenance work and spare requirements for assigned asset categories.', 'is_system' => true],
            ['name' => 'Staff Member', 'slug' => 'staff-member', 'description' => 'Requests property and manages assigned property.', 'is_system' => true],
            ['name' => 'Auditor', 'slug' => 'auditor', 'description' => 'Read-only inventory, report and audit access.', 'is_system' => true],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [...$role, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $rolePermissions = [
            'system-administrator' => [
                'dashboard.view', 'assets.view', 'assets.create', 'assets.update', 'assets.labels.print',
                'reports.view', 'classifications.manage', 'organization.manage', 'access.manage', 'audit.view',
            ],
            'procurement-officer' => [
                'dashboard.view', 'assets.view', 'assets.create', 'assets.update', 'assets.assign',
                'assignments.view', 'assets.transfer', 'assets.labels.print', 'requests.approve',
                'requests.allocate', 'maintenance.spares.procure', 'inspections.manage', 'reports.view',
                'disposals.propose', 'disposals.approve', 'disposals.finalize',
            ],
            'maintenance-officer' => ['dashboard.view', 'assets.view', 'incidents.create', 'maintenance.manage', 'disposals.propose'],
            'maintenance-review-officer' => ['dashboard.view', 'assets.view', 'maintenance.review', 'inspections.manage', 'disposals.propose', 'disposals.review', 'reports.view'],
            'staff-member' => ['dashboard.view', 'assets.view', 'requests.create', 'handovers.confirm', 'incidents.create', 'disposals.propose'],
            'auditor' => ['dashboard.view', 'assets.view', 'assignments.view', 'reports.view', 'audit.view'],
        ];

        $systemRoleIds = DB::table('roles')->whereIn('slug', array_keys($rolePermissions))->pluck('id');
        DB::table('permission_role')->whereIn('role_id', $systemRoleIds)->delete();
        foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            foreach (DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id') as $permissionId) {
                DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }

        DB::table('permissions')->whereIn('slug', ['suppliers.view', 'suppliers.manage'])->delete();

        DB::table('roles')->whereIn('slug', ['head-ict', 'approving-officer', 'asset-manager'])->delete();

        $adminRoleId = DB::table('roles')->where('slug', 'system-administrator')->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin@eims.local'],
            [
                'public_id' => (string) Str::ulid(),
                'staff_number' => 'EIMS-ADMIN',
                'name' => 'EIMS Administrator',
                'phone' => null,
                'status' => 'active',
                'email_verified_at' => $now,
                'password' => Hash::make(env('EIMS_INITIAL_ADMIN_PASSWORD', 'Eims@2026!ChangeMe')),
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $adminUserId = DB::table('users')->where('email', 'admin@eims.local')->value('id');
        DB::table('role_user')->updateOrInsert(
            ['role_id' => $adminRoleId, 'user_id' => $adminUserId],
            ['assigned_by' => $adminUserId, 'assigned_at' => $now],
        );
    }
}

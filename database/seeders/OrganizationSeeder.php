<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('organizational_units')->updateOrInsert(
            ['code' => 'UNIVERSITY'],
            [
                'public_id' => (string) Str::ulid(),
                'name' => 'University',
                'type' => 'university',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $universityId = DB::table('organizational_units')->where('code', 'UNIVERSITY')->value('id');
        DB::table('organizational_units')->updateOrInsert(
            ['code' => 'MRCU'],
            [
                'public_id' => (string) Str::ulid(),
                'parent_id' => $universityId,
                'name' => 'Maintenance Review and Control Unit',
                'type' => 'unit',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('locations')->updateOrInsert(
            ['code' => 'MAIN-CAMPUS'],
            [
                'public_id' => (string) Str::ulid(),
                'organizational_unit_id' => $universityId,
                'name' => 'Main Campus',
                'type' => 'campus',
                'description' => 'Default campus; administrators can rename or extend this location hierarchy.',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('users')->where('email', 'admin@eims.local')->update([
            'organizational_unit_id' => $universityId,
            'primary_location_id' => DB::table('locations')->where('code', 'MAIN-CAMPUS')->value('id'),
        ]);
    }
}

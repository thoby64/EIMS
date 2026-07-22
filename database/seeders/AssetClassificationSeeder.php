<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $groups = [
            ['ICT', 'ICT and Electronic Equipment', 'computer', '#5E72E4'],
            ['FUR', 'Furniture and Fittings', 'chair', '#8965E0'],
            ['CIV', 'Buildings and Civil Infrastructure', 'building', '#11CDEF'],
            ['ELE', 'Electrical Infrastructure', 'bolt', '#FB6340'],
            ['WAT', 'Water and Sanitation Infrastructure', 'water', '#2DCE89'],
            ['VEH', 'Vehicles and Transport Equipment', 'vehicle', '#F5365C'],
            ['LAB', 'Laboratory and Research Equipment', 'flask', '#5603AD'],
            ['TEA', 'Teaching and Classroom Equipment', 'education', '#5E72E4'],
            ['MED', 'Medical and Health Equipment', 'medical', '#2DCE89'],
            ['SEC', 'Security and Safety Equipment', 'shield', '#F5365C'],
            ['SPO', 'Sports and Recreation Equipment', 'sports', '#11CDEF'],
            ['TLS', 'Tools and Workshop Equipment', 'tools', '#FB6340'],
            ['KIT', 'Kitchen and Catering Equipment', 'kitchen', '#8965E0'],
            ['LIB', 'Library Assets', 'library', '#5E72E4'],
            ['AGR', 'Grounds and Agricultural Equipment', 'leaf', '#2DCE89'],
            ['INT', 'Intangible Assets and Software Licences', 'license', '#5603AD'],
        ];

        foreach ($groups as $index => [$code, $name, $icon, $color]) {
            DB::table('asset_groups')->updateOrInsert(
                ['code' => $code],
                [
                    'public_id' => (string) Str::ulid(),
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $color,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $categories = [
            ['ICT', 'LAP', 'Laptop', 'individual', true],
            ['ICT', 'DSK', 'Desktop Computer', 'individual', true],
            ['ICT', 'PRN', 'Printer', 'individual', true],
            ['ICT', 'PRJ', 'Projector', 'individual', true],
            ['ICT', 'CAM', 'Camera', 'individual', true],
            ['FUR', 'CHR', 'Office Chair', 'individual', true],
            ['FUR', 'TBL', 'Office Desk and Table', 'individual', true],
            ['FUR', 'CAB', 'Cabinet and Storage Unit', 'individual', false],
            ['CIV', 'BLD', 'Building', 'infrastructure', true],
            ['CIV', 'RDS', 'Road and Walkway', 'infrastructure', true],
            ['ELE', 'GEN', 'Generator', 'individual', true],
            ['ELE', 'PAN', 'Electrical Panel', 'infrastructure', true],
            ['WAT', 'TNK', 'Water Tank', 'infrastructure', true],
            ['WAT', 'PMP', 'Water Pump', 'individual', true],
            ['VEH', 'CAR', 'Motor Vehicle', 'individual', true],
            ['VEH', 'MTC', 'Motorcycle', 'individual', true],
            ['LAB', 'LBE', 'Laboratory Instrument', 'individual', true],
            ['LAB', 'REA', 'Laboratory Reagent', 'consumable', false],
            ['TEA', 'BRD', 'Teaching Board', 'individual', true],
            ['TEA', 'TDA', 'Teaching Aid', 'batch', false],
            ['MED', 'MDE', 'Medical Device', 'individual', true],
            ['MED', 'MDS', 'Medical Supply', 'consumable', false],
            ['SEC', 'CCTV', 'CCTV Equipment', 'individual', true],
            ['SEC', 'FEX', 'Fire Extinguisher', 'individual', true],
            ['SPO', 'SGE', 'Sports and Gym Equipment', 'individual', true],
            ['TLS', 'WTO', 'Workshop Tool', 'individual', true],
            ['KIT', 'KAP', 'Kitchen Appliance', 'individual', true],
            ['LIB', 'BOK', 'Book and Publication', 'batch', false],
            ['LIB', 'LBEQ', 'Library Equipment', 'individual', true],
            ['AGR', 'AGE', 'Agricultural Equipment', 'individual', true],
            ['AGR', 'GRD', 'Grounds Equipment', 'individual', true],
            ['INT', 'SWL', 'Software Licence', 'intangible', false],
            ['INT', 'SUB', 'Digital Subscription', 'intangible', false],
        ];

        foreach ($categories as $index => [$groupCode, $code, $name, $trackingMode, $maintainable]) {
            DB::table('asset_categories')->updateOrInsert(
                ['code' => $code],
                [
                    'public_id' => (string) Str::ulid(),
                    'asset_group_id' => DB::table('asset_groups')->where('code', $groupCode)->value('id'),
                    'name' => $name,
                    'tracking_mode' => $trackingMode,
                    'requires_asset_tag' => ! in_array($trackingMode, ['consumable'], true),
                    'is_maintainable' => $maintainable,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $attributes = [
            ['Processor', 'processor', 'text', null, true],
            ['Memory (RAM)', 'ram_gb', 'number', 'GB', true],
            ['Storage Capacity', 'storage_gb', 'number', 'GB', true],
            ['Operating System', 'operating_system', 'text', null, true],
            ['Material', 'material', 'text', null, true],
            ['Colour', 'colour', 'text', null, true],
            ['Dimensions', 'dimensions', 'text', null, false],
            ['Floor Area', 'floor_area', 'number', 'm²', true],
            ['Construction Year', 'construction_year', 'number', null, true],
            ['Output Capacity', 'output_capacity', 'text', null, true],
            ['Fuel Type', 'fuel_type', 'select', null, true],
            ['Registration Number', 'registration_number', 'text', null, true],
            ['Chassis Number', 'chassis_number', 'text', null, true],
            ['Engine Number', 'engine_number', 'text', null, true],
            ['Calibration Due Date', 'calibration_due_date', 'date', null, true],
            ['Expiry Date', 'expiry_date', 'date', null, true],
            ['Licence Key', 'licence_key', 'text', null, false],
            ['Licensed Seats', 'licensed_seats', 'number', 'seats', true],
            ['Renewal Date', 'renewal_date', 'date', null, true],
            ['Tank Capacity', 'tank_capacity', 'number', 'litres', true],
        ];

        foreach ($attributes as [$name, $slug, $type, $unit, $searchable]) {
            DB::table('attribute_definitions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'data_type' => $type,
                    'unit' => $unit,
                    'options' => $slug === 'fuel_type' ? json_encode(['petrol', 'diesel', 'electric', 'hybrid', 'gas']) : null,
                    'is_searchable' => $searchable,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $templates = [
            'LAP' => ['processor', 'ram_gb', 'storage_gb', 'operating_system'],
            'DSK' => ['processor', 'ram_gb', 'storage_gb', 'operating_system'],
            'CHR' => ['material', 'colour', 'dimensions'],
            'TBL' => ['material', 'colour', 'dimensions'],
            'BLD' => ['floor_area', 'construction_year'],
            'GEN' => ['output_capacity', 'fuel_type'],
            'TNK' => ['tank_capacity', 'material'],
            'CAR' => ['registration_number', 'chassis_number', 'engine_number', 'fuel_type'],
            'MTC' => ['registration_number', 'chassis_number', 'engine_number', 'fuel_type'],
            'LBE' => ['calibration_due_date'],
            'REA' => ['expiry_date'],
            'MDE' => ['calibration_due_date'],
            'FEX' => ['expiry_date'],
            'SWL' => ['licence_key', 'licensed_seats', 'renewal_date'],
            'SUB' => ['licensed_seats', 'renewal_date'],
        ];

        foreach ($templates as $categoryCode => $slugs) {
            $categoryId = DB::table('asset_categories')->where('code', $categoryCode)->value('id');
            foreach ($slugs as $order => $slug) {
                DB::table('asset_category_attribute')->updateOrInsert(
                    [
                        'asset_category_id' => $categoryId,
                        'attribute_definition_id' => DB::table('attribute_definitions')->where('slug', $slug)->value('id'),
                    ],
                    ['is_required' => false, 'is_unique' => in_array($slug, ['registration_number', 'chassis_number'], true), 'sort_order' => $order + 1],
                );
            }
        }
    }
}

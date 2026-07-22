<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class DepartmentSeeder extends Seeder {public function run(): void {$now=now();foreach([['ADMIN','Administrator','System administration and governance.'],['PROC','Procurement','Asset procurement, allocation and assignment.'],['MAINT','Maintenance','Inspection and repair operations.'],['MRC','Maintenance Review and Control','Independent maintenance review and quality control.']] as [$code,$name,$description]){DB::table('departments')->updateOrInsert(['code'=>$code],['public_id'=>(string)Str::ulid(),'name'=>$name,'description'=>$description,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]);}DB::table('users')->where('email','admin@eims.local')->update(['department_id'=>DB::table('departments')->where('code','ADMIN')->value('id')]);}}

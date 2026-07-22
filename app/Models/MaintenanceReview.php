<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceReview extends Model {protected $fillable=['public_id','maintenance_report_id','reviewed_by_user_id','decision','comments','reviewed_at']; protected function casts(): array{return ['reviewed_at'=>'datetime'];}}

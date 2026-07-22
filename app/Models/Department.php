<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Department extends Model {protected $fillable=['public_id','name','code','description','is_active'];protected function casts(): array{return ['is_active'=>'boolean'];}public function users(): HasMany{return $this->hasMany(User::class);}public function assets(): HasMany{return $this->hasMany(Asset::class,'custodian_department_id');}}

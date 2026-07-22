<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    protected $fillable = ['public_id', 'actor_user_id', 'actor_identity', 'event_type', 'action', 'module', 'auditable_type', 'auditable_id', 'auditable_public_id', 'route_name', 'http_method', 'path', 'ip_address', 'user_agent', 'outcome', 'http_status', 'old_values', 'new_values', 'context', 'occurred_at'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'context' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit logs cannot be deleted.'));
    }
}

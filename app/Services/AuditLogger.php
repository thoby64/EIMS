<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class AuditLogger
{
    private const SENSITIVE = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token', '_token', 'authorization', 'cookie'];

    public function write(string $event, string $action, array $data = [], ?Request $request = null): void
    {
        try {
            $request ??= request();
            $context = $this->redact($data['context'] ?? []);
            AuditLog::create(['public_id' => (string) Str::ulid(), 'actor_user_id' => $data['actor_user_id'] ?? auth()->id(), 'actor_identity' => $data['actor_identity'] ?? auth()->user()?->email, 'event_type' => $event, 'action' => $action, 'module' => $data['module'] ?? $this->module($request?->route()?->getName()), 'auditable_type' => $data['auditable_type'] ?? null, 'auditable_id' => $data['auditable_id'] ?? null, 'auditable_public_id' => $data['auditable_public_id'] ?? null, 'route_name' => $request?->route()?->getName(), 'http_method' => $request?->method(), 'path' => $request?->path(), 'ip_address' => $request?->ip(), 'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''), 'outcome' => $data['outcome'] ?? 'success', 'http_status' => $data['http_status'] ?? null, 'old_values' => $this->redact($data['old_values'] ?? null), 'new_values' => $this->redact($data['new_values'] ?? null), 'context' => $context ?: null, 'occurred_at' => now()]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function redactedInput(Request $r): array
    {
        return $this->redact($r->except(self::SENSITIVE)) ?? [];
    }

    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

return collect($values)->mapWithKeys(function ($v, $k) {
            if (in_array(Str::lower((string) $k), self::SENSITIVE, true) || Str::contains(Str::lower((string) $k), ['password', 'secret', 'token'])) {
                return [$k => '[REDACTED]'];
            }

return [$k => is_array($v) ? $this->redact($v) : $v];
        })->all();
    }

    private function module(?string $route): ?string
    {
        return $route ? Str::before($route, '.') : null;
    }
}

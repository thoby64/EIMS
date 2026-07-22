<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableModelObserver
{
    public function created(Model $m): void
    {
        $this->log($m, 'created', null, $m->getAttributes());
    }

    public function updated(Model $m): void
    {
        $changes = $m->getChanges();
        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $m->getOriginal($key);
        }$this->log($m, 'updated', $old, $changes);
    }

    public function deleted(Model $m): void
    {
        $this->log($m, 'deleted', $m->getOriginal(), null);
    }

    public function restored(Model $m): void
    {
        $this->log($m, 'restored', null, $m->getAttributes());
    }

    private function log(Model $m, string $action, ?array $old, ?array $new): void
    {
        app(AuditLogger::class)->write('model', $action, ['module' => str($m->getTable())->before('_')->toString(), 'auditable_type' => $m::class, 'auditable_id' => $m->getKey(), 'auditable_public_id' => $m->getAttribute('public_id'), 'old_values' => $old, 'new_values' => $new]);
    }
}

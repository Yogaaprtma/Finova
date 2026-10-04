<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('create', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->logAudit('update', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->logAudit('delete', $model->getOriginal(), null);
        });
    }

    protected function logAudit(string $action, ?array $oldValues, ?array $newValues)
    {
        if (!config('finance.audit_enabled', true)) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id() ?? request()->user()?->id,
            'action' => $action,
            'entity_type' => get_class($this),
            'entity_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

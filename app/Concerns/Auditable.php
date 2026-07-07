<?php

namespace App\Concerns;

use App\Support\AuditLogger;
use Illuminate\Support\Arr;

/**
 * Drop this trait onto any Eloquent model to automatically write
 * create/update/delete rows to audit_logs. Sensitive attributes (see
 * auditHidden()) are never written to old_values/new_values, even when
 * they're the only thing that changed — the update is still logged, just
 * without exposing the value.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLogger::log(
                action: 'create',
                module: $model->auditModule(),
                auditable: $model,
                newValues: $model->auditableAttributes(),
                description: $model->auditLabel(),
            );
        });

        static::updated(function ($model) {
            $changes = Arr::except($model->getChanges(), ['updated_at']);

            if (empty($changes)) {
                return;
            }

            $hidden = $model->auditHidden();
            $sensitiveChanged = array_intersect(array_keys($changes), $hidden) !== [];
            $visibleChanges = Arr::except($changes, $hidden);

            if (empty($visibleChanges) && ! $sensitiveChanged) {
                return;
            }

            $old = Arr::only($model->getOriginal(), array_keys($visibleChanges));
            $description = $model->auditLabel();

            if ($sensitiveChanged) {
                $description .= ' (มีการเปลี่ยนรหัสผ่าน)';
            }

            AuditLogger::log(
                action: 'update',
                module: $model->auditModule(),
                auditable: $model,
                oldValues: $old ?: null,
                newValues: $visibleChanges ?: null,
                description: $description,
            );
        });

        static::deleted(function ($model) {
            AuditLogger::log(
                action: 'delete',
                module: $model->auditModule(),
                auditable: $model,
                oldValues: $model->auditableAttributes(),
                description: $model->auditLabel(),
            );
        });
    }

    /**
     * The module label shown in the audit log UI. Override per model.
     */
    public function auditModule(): string
    {
        return class_basename($this);
    }

    /**
     * The human-readable identifier for this row shown in the audit log
     * description column (e.g. student name + code). Override per model.
     */
    public function auditLabel(): string
    {
        return class_basename($this).' #'.$this->getKey();
    }

    /**
     * Attributes that must never be written to old_values/new_values.
     */
    protected function auditHidden(): array
    {
        return ['password', 'remember_token'];
    }

    protected function auditableAttributes(): array
    {
        return Arr::except($this->attributesToArray(), $this->auditHidden());
    }
}

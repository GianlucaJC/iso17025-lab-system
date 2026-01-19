<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

trait Auditable
{
    /**
     * The "booting" method of the trait.
     */
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('created', $model);
        });

        static::updated(function (Model $model) {
            self::audit('updated', $model);
        });

        static::deleted(function (Model $model) {
            self::audit('deleted', $model);
        });
    }

    /**
     * Generate an audit log for the model.
     */
    protected static function audit(string $event, Model $model)
    {
        $user = Session::get('user');
        $userId = $user['id'] ?? null;
        $userName = $user['operatore'] ?? 'System';

        $oldValues = [];
        $newValues = [];
        $modificationReason = null;

        switch ($event) {
            case 'created':
                $newValues = $model->getAttributes();
                break;

            case 'updated':
                $oldValues = $model->getOriginal();
                $newValues = $model->getAttributes();

                // Non registrare il log se l'unica cosa cambiata è il timestamp 'updated_at'
                unset($oldValues['updated_at'], $newValues['updated_at']);
                if ($oldValues == $newValues) {
                    return;
                }

                $modificationReason = $model->modification_reason ?? null;
                break;

            case 'deleted':
                $oldValues = $model->getAttributes();
                break;
        }

        // Non salvare la motivazione due volte
        unset($oldValues['modification_reason'], $newValues['modification_reason']);

        AuditLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'event' => $event,
            'auditable_id' => $model->getKey(),
            'auditable_type' => get_class($model),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'modification_reason' => $modificationReason,
        ]);
    }
}
<?php

namespace App\Models\Concerns;

use App\Services\AuditoriaService;

trait Auditavel
{
    public static function bootAuditavel(): void
    {
        static::created(function ($model) {
            app(AuditoriaService::class)->registrar(
                'created',
                $model,
                null,
                $model->getAttributes(),
                'Criou '.class_basename($model).' #'.$model->id
            );
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            if (empty($changes)) return;
            $original = array_intersect_key($model->getOriginal(), $changes);

            app(AuditoriaService::class)->registrar(
                'updated',
                $model,
                $original,
                $changes,
                'Atualizou '.class_basename($model).' #'.$model->id
            );
        });

        static::deleted(function ($model) {
            app(AuditoriaService::class)->registrar(
                'deleted',
                $model,
                $model->getOriginal(),
                null,
                'Excluiu '.class_basename($model).' #'.$model->id
            );
        });
    }
}

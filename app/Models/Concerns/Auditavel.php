<?php

namespace App\Models\Concerns;

use App\Services\AuditoriaService;
use Throwable;

trait Auditavel
{
    /**
     * Audit log nunca pode derrubar a operação principal. Sem try/catch,
     * falha no INSERT em auditoria_logs (FK quebrada, deadlock, schema
     * drift, disco cheio) sobe pelo event listener e quebra o save do
     * model — criar Empresa/User/Plano/Resgate falha mesmo quando o save
     * em si funcionou (cascata: EmpresaObserver::created cria Assinatura
     * trial e ambas as audits podem falhar). report() preserva o sinal
     * pra monitoramento sem propagar.
     */
    public static function bootAuditavel(): void
    {
        static::created(function ($model) {
            try {
                app(AuditoriaService::class)->registrar(
                    'created', $model, null, $model->getAttributes(),
                    'Criou '.class_basename($model).' #'.$model->id
                );
            } catch (Throwable $e) {
                report($e);
            }
        });

        static::updated(function ($model) {
            try {
                $changes = $model->getChanges();
                if (empty($changes)) return;
                $original = array_intersect_key($model->getOriginal(), $changes);

                app(AuditoriaService::class)->registrar(
                    'updated', $model, $original, $changes,
                    'Atualizou '.class_basename($model).' #'.$model->id
                );
            } catch (Throwable $e) {
                report($e);
            }
        });

        static::deleted(function ($model) {
            try {
                app(AuditoriaService::class)->registrar(
                    'deleted', $model, $model->getOriginal(), null,
                    'Excluiu '.class_basename($model).' #'.$model->id
                );
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}

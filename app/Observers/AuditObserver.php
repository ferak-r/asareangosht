<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void { app(AuditService::class)->record('created',$model,[],$model->getAttributes()); }
    public function updated(Model $model): void { app(AuditService::class)->record('updated',$model,$model->getOriginal(),$model->getChanges()); }
    public function deleted(Model $model): void { app(AuditService::class)->record('deleted',$model,$model->getOriginal(),[]); }
}

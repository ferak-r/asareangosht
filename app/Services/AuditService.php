<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $event, Model $model, array $old = [], array $new = []): AuditLog
    {
        $request = request();
        return AuditLog::create(['user_id'=>auth()->id(),'event'=>$event,'auditable_type'=>$model::class,'auditable_id'=>$model->getKey(),'old_values'=>$this->safe($old),'new_values'=>$this->safe($new),'ip_address'=>$request?->ip(),'user_agent'=>mb_substr((string)$request?->userAgent(),0,1000),'created_at'=>now()]);
    }
    private function safe(array $values): array { unset($values['password'],$values['remember_token']); return $values; }
}

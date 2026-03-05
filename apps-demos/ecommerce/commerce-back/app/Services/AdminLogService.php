<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\User;

class AdminLogService
{
    public function log(User $user, string $action, string $entity, int $entityId): AdminLog
    {
        return AdminLog::query()->create([
            'user_id' => $user->id,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'created_at' => now(),
        ]);
    }
}

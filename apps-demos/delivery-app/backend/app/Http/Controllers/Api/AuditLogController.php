<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->limit(300)
            ->get();

        return response()->json($logs);
    }
}


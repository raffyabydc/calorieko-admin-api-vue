<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SystemLogController extends Controller
{
    /**
     * Admin: List all system logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SystemLog::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('admin_email', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        if ($request->boolean('nopaginate')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(15));
    }
}

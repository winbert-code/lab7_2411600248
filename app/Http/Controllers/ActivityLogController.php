<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $module = $request->string('module')->toString();
        $action = $request->string('action')->toString();
        $userId = $request->integer('user_id');

        if (! in_array($module, ActivityLog::MODULES, true)) {
            $module = '';
        }

        if (! in_array($action, ActivityLog::ACTIONS, true)) {
            $action = '';
        }

        if (! User::whereKey($userId)->exists()) {
            $userId = 0;
        }

        $logs = ActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($userId > 0, fn ($query) => $query->where('user_id', $userId))
            ->latestFirst()
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'search' => $search,
            'module' => $module,
            'action' => $action,
            'userId' => $userId,
            'modules' => ActivityLog::MODULES,
            'actions' => ActivityLog::ACTIONS,
            'users' => User::orderBy('name')->orderBy('id')->pluck('name', 'id'),
            'totalActions' => ActivityLog::count(),
            'todayActions' => ActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'activeUsers' => ActivityLog::whereNotNull('user_id')->distinct()->count('user_id'),
        ]);
    }
}

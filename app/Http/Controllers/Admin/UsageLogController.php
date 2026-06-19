<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsageLog;
use Illuminate\Http\Request;

class UsageLogController extends Controller
{
    /**
     * Display usage logs for a user
     */
    public function index(User $user)
    {
        $logs = $user->usageLogs()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $stats = UsageLog::getUserStats($user->id);

        return view('admin.usage-logs.index', compact('user', 'logs', 'stats'));
    }

    /**
     * Display all usage logs
     */
    public function all()
    {
        $logs = UsageLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return view('admin.usage-logs.all', compact('logs'));
    }
}

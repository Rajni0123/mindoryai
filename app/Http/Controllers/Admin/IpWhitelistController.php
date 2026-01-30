<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIpWhitelist;
use Illuminate\Http\Request;

class IpWhitelistController extends Controller
{
    /**
     * Display IP whitelist for a user
     */
    public function index(User $user)
    {
        $ipWhitelist = $user->ipWhitelist()->orderBy('created_at', 'desc')->get();

        return view('admin.ip-whitelist.index', compact('user', 'ipWhitelist'));
    }

    /**
     * Add IP to whitelist
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $user->id;
        $validated['is_active'] = $request->has('is_active');

        UserIpWhitelist::create($validated);

        return redirect()->route('admin.users.ip-whitelist', $user)
            ->with('success', 'IP address added to whitelist successfully!');
    }

    /**
     * Toggle IP whitelist status
     */
    public function toggle(User $user, UserIpWhitelist $ipWhitelist)
    {
        $ipWhitelist->update([
            'is_active' => !$ipWhitelist->is_active,
        ]);

        $status = $ipWhitelist->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.users.ip-whitelist', $user)
            ->with('success', "IP address {$status} successfully!");
    }

    /**
     * Remove IP from whitelist
     */
    public function destroy(User $user, UserIpWhitelist $ipWhitelist)
    {
        $ipWhitelist->delete();

        return redirect()->route('admin.users.ip-whitelist', $user)
            ->with('success', 'IP address removed from whitelist successfully!');
    }
}

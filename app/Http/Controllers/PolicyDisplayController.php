<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyDisplayController extends Controller
{
    /**
     * Display a listing of all policies for users
     */
    public function index()
    {
        $policies = Policy::enabled()
            ->ordered()
            ->get();

        return view('policies.index', compact('policies'));
    }

    /**
     * Display a specific policy by key
     */
    public function show($key)
    {
        $policy = Policy::where('key', $key)
            ->where('is_enabled', true)
            ->firstOrFail();

        return view('policies.show', compact('policy'));
    }

    /**
     * Display privacy policy
     */
    public function privacy()
    {
        return $this->show('privacy_policy');
    }

    /**
     * Display terms of service
     */
    public function terms()
    {
        return $this->show('terms_of_service');
    }

    /**
     * Display refund policy
     */
    public function refund()
    {
        return $this->show('refund_policy');
    }

    /**
     * Display cancellation policy
     */
    public function cancellation()
    {
        return $this->show('cancellation_policy');
    }

    /**
     * Display cookie policy
     */
    public function cookie()
    {
        return $this->show('cookie_policy');
    }

    /**
     * Display about page
     */
    public function about()
    {
        return $this->show('about');
    }

    /**
     * Display support page
     */
    public function support()
    {
        return $this->show('support');
    }
}

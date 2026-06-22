<?php

namespace App\Http\Controllers;

use App\Services\WebDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebDashboardController extends Controller
{
    public function show(Request $request, WebDashboardService $dashboard): JsonResponse
    {
        return response()->json($dashboard->build($request->user()));
    }
}

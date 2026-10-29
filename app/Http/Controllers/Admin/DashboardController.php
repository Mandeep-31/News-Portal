<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): View|JsonResponse
    {
        $counts = $this->dashboard->counts();
        if (request()->expectsJson()) {
            return ApiResponse::success('Admin dashboard retrieved successfully.', $counts);
        }

        return view('admin.dashboard', compact('counts'));
    }
}

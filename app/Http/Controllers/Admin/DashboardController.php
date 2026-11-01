<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): JsonResponse
    {
        $counts = $this->dashboard->counts();

        return ApiResponse::success(
            'Admin dashboard retrieved successfully.',
            $counts
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            [
                'status' => 'healthy',
                'application' => config('app.name'),
                'version' => '1.0.0',
            ],
            'A&A Resort API is running.'
        );
    }
}

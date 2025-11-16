<?php

use bitoliveira\Approval\Http\Controllers\ApprovalApiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('approval.api.prefix', 'approvals'),
    'middleware' => config('approval.api.middleware', ['api']),
], function () {
    Route::get('/', [ApprovalApiController::class, 'index'])->name('api.approval.index');
    Route::get('/{approval}', [ApprovalApiController::class, 'show'])->name('api.approval.show');
    Route::post('/{approval}/approve', [ApprovalApiController::class, 'approve'])->name('api.approval.approve');
    Route::post('/{approval}/reject', [ApprovalApiController::class, 'reject'])->name('api.approval.reject');
});

// Test routes (public - no authentication required)
Route::get('api/approval-test/routes', function () {
    $routes = collect(Route::getRoutes())
        ->filter(function ($route) {
            return str_contains($route->uri(), 'approval');
        })
        ->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->gatherMiddleware(),
            ];
        })
        ->values();

    return response()->json([
        'success' => true,
        'message' => 'Approval routes registered',
        'config' => [
            'prefix' => config('approval.api.prefix'),
            'middleware' => config('approval.api.middleware'),
        ],
        'routes' => $routes,
    ], 200);
})->name('api.approval.test.routes');

Route::get('api/approval-test/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'Approval API is working!',
        'timestamp' => now()->toIso8601String(),
        'config' => [
            'prefix' => config('approval.api.prefix'),
            'middleware' => config('approval.api.middleware'),
        ],
    ], 200);
})->name('api.approval.test.ping');

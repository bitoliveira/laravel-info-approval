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

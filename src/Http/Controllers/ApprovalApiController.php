<?php

namespace bitoliveira\Approval\Http\Controllers;

use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApprovalApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Approval::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($approvableType = $request->query('approvable_type')) {
            $query->where('approvable_type', $approvableType);
        }

        if ($approvableId = $request->query('approvable_id')) {
            $query->where('approvable_id', $approvableId);
        }

        $approvals = $query->latest()->paginate(15);

        return response()->json($approvals);
    }

    public function show(Approval $approval): JsonResponse
    {
        return response()->json($approval);
    }

    public function approve(Request $request, Approval $approval, ApprovalService $service): JsonResponse
    {
        $data = $request->validate([
            'approver_id' => ['required', 'integer'],
        ]);

        try {
            $service->approve($approval, (int) $data['approver_id']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Approval updated successfully.',
            'approval' => $approval->fresh(),
        ]);
    }

    public function reject(Request $request, Approval $approval, ApprovalService $service): JsonResponse
    {
        $data = $request->validate([
            'approver_id' => ['required', 'integer'],
        ]);

        try {
            $service->reject($approval, (int) $data['approver_id']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Approval updated successfully.',
            'approval' => $approval->fresh(),
        ]);
    }
}

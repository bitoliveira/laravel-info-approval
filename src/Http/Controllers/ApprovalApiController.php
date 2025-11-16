<?php

namespace bitoliveira\Approval\Http\Controllers;

use bitoliveira\Approval\Exceptions\ApprovalException;
use bitoliveira\Approval\Http\Requests\ApproveApprovalRequest;
use bitoliveira\Approval\Http\Requests\RejectApprovalRequest;
use bitoliveira\Approval\Http\Resources\ApprovalCollection;
use bitoliveira\Approval\Http\Resources\ApprovalResource;
use bitoliveira\Approval\Models\Approval;
use bitoliveira\Approval\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApprovalApiController extends Controller
{
    public function index(Request $request)
    {
        return [];
        $query = Approval::query();

        if ($status = $request->query('status')) {
            $query->status($status);
        }

        if ($approvableType = $request->query('approvable_type')) {
            $query->forType($approvableType);
        }

        if ($approvableId = $request->query('approvable_id')) {
            $query->where('approvable_id', $approvableId);
        }

        $approvals = $query->recent()->paginate(15);

        return new ApprovalCollection($approvals);
    }

    public function show(Approval $approval): ApprovalResource
    {
        return new ApprovalResource($approval);
    }

    public function approve(ApproveApprovalRequest $request, Approval $approval, ApprovalService $service): JsonResponse
    {
        try {
            $service->approve($approval, (int) $request->validated('approver_id'));
        } catch (ApprovalException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'message' => 'Approval updated successfully.',
            'approval' => new ApprovalResource($approval->fresh()),
        ]);
    }

    public function reject(RejectApprovalRequest $request, Approval $approval, ApprovalService $service): JsonResponse
    {
        try {
            $service->reject($approval, (int) $request->validated('approver_id'));
        } catch (ApprovalException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'message' => 'Approval updated successfully.',
            'approval' => new ApprovalResource($approval->fresh()),
        ]);
    }
}

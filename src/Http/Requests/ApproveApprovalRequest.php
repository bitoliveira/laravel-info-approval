<?php

namespace bitoliveira\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the approver ID from the authenticated user.
     * Falls back to the request input for testing scenarios.
     */
    public function getApproverId(): ?int
    {
        if ($this->user()) {
            return (int) $this->user()->id;
        }

        // Fallback for testing scenarios without authentication
        return $this->input('approver_id') ? (int) $this->input('approver_id') : null;
    }
}

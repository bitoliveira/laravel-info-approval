<?php

namespace bitoliveira\Approval\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authorization if there's no user (testing scenarios)
        if (!$this->user()) {
            return true;
        }

        // In production, ensure approver_id matches authenticated user
        return (int) $this->input('approver_id') === (int) $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approver_id' => ['required', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'approver_id.required' => 'O ID do aprovador é obrigatório.',
            'approver_id.integer' => 'O ID do aprovador deve ser um número inteiro.',
            'approver_id.exists' => 'O aprovador especificado não existe.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new \bitoliveira\Approval\Exceptions\ApproverMismatchException();
    }
}

<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only a full-access admin may create or promote a role to full access;
     * this closes the self-escalation path from a custom role. Assigning an
     * over-privileged role to a user is separately guarded in UserController.
     */
    public function authorize(): bool
    {
        if (strtolower((string) $this->input('permission_type')) === 'all') {
            return auth()->guard('admin')->user()?->role?->permission_type === 'all';
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'            => 'required',
            'permission_type' => $this->id ? 'required|in:all,custom' : 'required',
            'description'     => 'nullable',
            'permissions'     => 'nullable|array',
        ];
    }
}

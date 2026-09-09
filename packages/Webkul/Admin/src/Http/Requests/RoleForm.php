<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class RoleForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * A non full-access admin may neither promote a role to full access nor
     * grant a permission it does not itself hold. Both guard the same
     * self-escalation path — a delegated role manager editing its own role —
     * and mirror the assignment guard in UserController.
     */
    public function authorize(): bool
    {
        $actingRole = auth()->guard('admin')->user()?->role;

        if ($actingRole?->permission_type === 'all') {
            return true;
        }

        if (strtolower((string) $this->input('permission_type')) === 'all') {
            return false;
        }

        $submitted = (array) $this->input('permissions', []);

        if (array_filter($submitted, is_string(...)) !== $submitted) {
            return false;
        }

        return array_diff($submitted, $actingRole?->permissions ?? []) === [];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('admin::app.settings.roles.cannot-grant-unheld-permissions'));
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
            'permissions.*'   => 'string',
        ];
    }
}

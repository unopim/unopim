<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\User\Services\RoleAccessGuard;

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
    public function authorize(RoleAccessGuard $roleAccessGuard): bool
    {
        $actor = auth()->guard('admin')->user();

        if (! $actor) {
            return false;
        }

        return $roleAccessGuard->grantError(
            $actor,
            $this->input('permission_type'),
            (array) $this->input('permissions', [])
        ) === null;
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
            'permission_type' => ['required', Rule::in([RoleAccessGuard::FULL_ACCESS, RoleAccessGuard::CUSTOM_ACCESS])],
            'description'     => 'nullable',
            'permissions'     => 'nullable|array',
            'permissions.*'   => 'string',
        ];
    }
}

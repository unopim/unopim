<?php

namespace Webkul\AdminApi\Http\Controllers\API;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Webkul\AdminApi\Http\Middleware\ScopeMiddleware;
use Webkul\AdminApi\Traits\ApiResponse;
use Webkul\Core\Rules\Code;
use Webkul\Core\Traits\HtmlPurifier;

class ApiController extends BaseController
{
    use ApiResponse, DispatchesJobs, HtmlPurifier, ValidatesRequests;

    /**
     * Determine whether the authenticated API key holds the given permission.
     */
    protected function apiKeyAllows(string $permission): bool
    {
        return app(ScopeMiddleware::class)->hasPermission($permission);
    }

    /**
     * Refuse child entries of a parent update, such as options or fields, that the key may not create or delete.
     *
     * The child routes gate adds and removals on the create and delete keys, so the parent update must too.
     *
     * @param  (\Closure(array<string, mixed>, int|string): bool)|null  $createsEntry  whether an entry adds a child; defaults to its `isNew` flag
     */
    protected function deniedChildWrites(mixed $entries, string $permissionPrefix, string $errorKey, ?\Closure $createsEntry = null): ?JsonResponse
    {
        $isFlagged = fn (array $entry, string $flag): bool => filter_var($entry[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);

        $createsEntry ??= fn (array $entry): bool => $isFlagged($entry, 'isNew');

        foreach (is_array($entries) ? $entries : [] as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $required = array_filter([
                $createsEntry($entry, $key) ? $permissionPrefix.'.create' : null,
                $isFlagged($entry, 'isDelete') ? $permissionPrefix.'.delete' : null,
            ]);

            foreach ($required as $permission) {
                if (! $this->apiKeyAllows($permission)) {
                    return $this->validateErrorResponse([$errorKey => [trans('admin::app.common.unauthorized')]]);
                }
            }
        }

        return null;
    }

    /**
     * This function processes and transforms the 'labels' key in the given request data.
     *
     * @return array
     */
    protected function setLabels(array $requestData, string $labelKey = 'name')
    {
        if (! isset($requestData['labels']) || ! is_array($requestData['labels'])) {
            return $requestData;
        }

        $labels = [];
        foreach ($requestData['labels'] as $key => $value) {
            $labels[$key] = [$labelKey => $value];
        }

        unset($requestData['labels']);
        $requestData = array_merge($requestData, $labels);

        return $requestData;
    }

    /**
     * Normalizes an options payload so a single option object is treated the
     * same as a list containing one option.
     *
     * @param  array<mixed>  $requestData
     * @return array<int, mixed>
     */
    protected function normalizeOptionsPayload(array $requestData): array
    {
        if (empty($requestData) || array_is_list($requestData)) {
            return $requestData;
        }

        return [$requestData];
    }

    /**
     * Returns a validation error response when any item of a normalized options payload is not an object.
     *
     * @param  array<int, mixed>  $options
     */
    protected function validateOptionsPayload(array $options): ?JsonResponse
    {
        $validator = Validator::make($options, ['*' => 'array']);

        return $validator->fails() ? $this->validateErrorResponse($validator) : null;
    }

    /**
     * This function creates a validator instance for a given table with unique 'code' requirement.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function codeRequireWithUniqueValidator(string $table, array $newRules = [])
    {
        $rules = [
            'code' => [
                'required',
                sprintf('unique:%s,code', $table),
                new Code,
            ],
        ];

        $validator = Validator::make(
            request()->all(),
            array_merge($rules, $newRules)
        );

        return $validator;
    }

    /**
     * This function creates a validator instance for the given rules.
     *
     * @return \Illuminate\Contracts\Validation\Validator The validator instance.
     */
    protected function validator(array $Rules = [])
    {
        $validator = Validator::make(
            request()->all(),
            $Rules
        );

        return $validator;
    }
}

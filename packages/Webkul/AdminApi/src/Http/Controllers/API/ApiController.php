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
     * Refuse child entries of a parent update, such as options or fields, that the key may not create, edit or delete.
     *
     * The child routes gate adds and removals on the create and delete keys, so the parent update must too. With
     * `$gateEdits`, an entry that rewrites an existing child also needs the edit key of the child routes.
     *
     * @param  (\Closure(array<string, mixed>, int|string): bool)|null  $createsEntry  whether an entry adds a child; defaults to its `isNew` flag
     */
    protected function deniedChildWrites(
        mixed $entries,
        string $permissionPrefix,
        string $errorKey,
        ?\Closure $createsEntry = null,
        bool $gateEdits = false,
    ): ?JsonResponse {
        $isFlagged = fn (array $entry, string $flag): bool => filter_var($entry[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);

        $createsEntry ??= fn (array $entry): bool => $isFlagged($entry, 'isNew');

        foreach (is_array($entries) ? $entries : [] as $key => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $creates = $createsEntry($entry, $key);
            $deletes = $isFlagged($entry, 'isDelete');

            $required = array_filter([
                $creates ? $permissionPrefix.'.create' : null,
                $deletes ? $permissionPrefix.'.delete' : null,
                $gateEdits && ! $creates && ! $deletes ? $permissionPrefix.'.edit' : null,
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
     * Validate the options of a parent update with the rules of the option routes.
     *
     * A new option with a code goes through the option store validator; any other entry must still
     * carry a valid code when it sends one, since the repository and this check may read its flags differently.
     *
     * @param  \Closure(array<string, mixed>): \Illuminate\Contracts\Validation\Validator  $newOptionValidator
     * @param  array<string, array<int, mixed>>  $existingOptionRules
     */
    protected function invalidOptionEntries(mixed $options, \Closure $newOptionValidator, array $existingOptionRules = []): ?JsonResponse
    {
        foreach (is_array($options) ? $options : [] as $option) {
            if (! is_array($option)) {
                return $this->validateErrorResponse(['options' => [trans('validation.array', ['attribute' => 'options'])]]);
            }

            $isNew = filter_var($option['isNew'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $validator = $isNew && filled($option['code'] ?? null)
                ? $newOptionValidator($option)
                : Validator::make($option, ['code' => ['sometimes', 'required', new Code], ...$existingOptionRules]);

            if ($validator->fails()) {
                return $this->validateErrorResponse($validator);
            }
        }

        return null;
    }

    /**
     * Hoist the `labels` wrapper into per-locale keys. Only active locale codes are hoisted, so the
     * wrapper can never inject a field the caller could not send at the top level.
     *
     * @return array
     */
    protected function setLabels(array $requestData, string $labelKey = 'name')
    {
        if (! isset($requestData['labels']) || ! is_array($requestData['labels'])) {
            return $requestData;
        }

        $localeCodes = core()->getAllActiveLocales()->pluck('code')->all();

        $labels = [];
        foreach ($requestData['labels'] as $key => $value) {
            if (in_array($key, $localeCodes, true)) {
                $labels[$key] = [$labelKey => $value];
            }
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

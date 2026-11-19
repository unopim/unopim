<?php

namespace Webkul\Admin\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait RejectsUndeclaredUploads
{
    /**
     * Fail the save when a file is posted at a config code not declared as a `file`
     * or `image` field. CoreConfigRepository::create() stores any upload it finds at
     * a config code, and Request::all() lets a text value shadow that upload during
     * validation, so a file could otherwise replace a validated scalar.
     *
     * @param  array<int, string>  $fileFieldCodes
     *
     * @throws ValidationException
     */
    protected function rejectUndeclaredUploads(Request $request, array $fileFieldCodes): void
    {
        $errors = [];

        foreach (array_keys(Arr::dot($request->allFiles())) as $code) {
            if (! in_array($code, $fileFieldCodes, true)) {
                $errors[$code] = trans('validation.prohibited', ['attribute' => $code]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Config codes of the `file` and `image` fields a settings group declares.
     *
     * @param  array<string, mixed>  $group
     * @return array<int, string>
     */
    protected function fileFieldCodes(array $group): array
    {
        return collect((array) ($group['fields'] ?? []))
            ->filter(fn (array $field): bool => in_array($field['type'] ?? null, ['file', 'image'], true))
            ->map(fn (array $field): string => ((string) $group['key']).'.'.((string) $field['name']))
            ->values()
            ->all();
    }
}

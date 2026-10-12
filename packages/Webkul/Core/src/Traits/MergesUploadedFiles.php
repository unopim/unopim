<?php

namespace Webkul\Core\Traits;

use Illuminate\Http\UploadedFile;

trait MergesUploadedFiles
{
    protected function mergeUploadedFilesIntoInput(): void
    {
        $files = $this->allFiles();

        if ($files === []) {
            return;
        }

        $this->merge($this->overlayUploadedFiles($this->getInputSource()->all(), $files));
    }

    protected function overlayUploadedFiles(array $input, array $files): array
    {
        foreach ($files as $key => $file) {
            $current = $input[$key] ?? null;

            if (is_array($file)) {
                if (is_array($current)) {
                    $input[$key] = $this->overlayUploadedFiles($current, $file);

                    continue;
                }

                $uploaded = $this->onlyUploadedFiles($file);

                if ($uploaded !== [] && ($current === null || $current === '')) {
                    $input[$key] = $uploaded;
                }

                continue;
            }

            if ($file instanceof UploadedFile && ($current === null || $current === '')) {
                $input[$key] = $file;
            }
        }

        return $input;
    }

    protected function onlyUploadedFiles(array $files): array
    {
        $uploaded = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $nested = $this->onlyUploadedFiles($file);

                if ($nested !== []) {
                    $uploaded[$key] = $nested;
                }

                continue;
            }

            if ($file instanceof UploadedFile) {
                $uploaded[$key] = $file;
            }
        }

        return $uploaded;
    }
}

<?php

namespace Webkul\Core\View\Compilers;

use Illuminate\View\Compilers\BladeCompiler as BaseBladeCompiler;

class BladeCompiler extends BaseBladeCompiler
{
    /**
     * Append the file path to the compiled string.
     *
     * @param  string  $contents
     * @return string
     */
    protected function appendFilePath($contents)
    {
        $tokens = $this->getOpenAndClosingPhpTokens($contents);

        if (
            config('view.tracer')
            && ! str_contains($this->getPath(), 'tracer/style.blade.php')
            && ! str_contains($this->getPath(), 'master.blade.php')
        ) {
            $finalPath = str_replace('/Providers/..', '', str_replace(base_path(), '', $this->getPath()));

            $contents = '<div class="path-hint" data-toggle="tooltip" data-title="'.$finalPath.'" data-id="'.uniqid().'"><span class="testing"></span>'.$contents.'</div>';
        }

        if (
            $tokens->isNotEmpty()
            && $tokens->last() !== T_CLOSE_TAG
        ) {
            $contents .= ' ?>';
        }

        return $contents."<?php /**PATH {$this->getPath()} ENDPATH**/ ?>";
    }
}

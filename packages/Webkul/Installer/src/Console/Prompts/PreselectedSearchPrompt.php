<?php

namespace Webkul\Installer\Console\Prompts;

use Closure;
use Laravel\Prompts\SearchPrompt;

/**
 * A searchable prompt that pre-selects an existing value so the user can keep
 * it by pressing Enter, while the normal search still works on any key press.
 */
class PreselectedSearchPrompt extends SearchPrompt
{
    public function __construct(
        string $label,
        Closure $options,
        string $placeholder = '',
        int $scroll = 5,
        mixed $validate = null,
        string $hint = '',
        bool|string $required = true,
        ?Closure $transform = null,
        protected int|string|null $defaultValue = null,
    ) {
        parent::__construct(
            label: $label,
            options: $options,
            placeholder: $placeholder,
            scroll: $scroll,
            validate: $validate,
            hint: $hint,
            required: $required,
            transform: $transform,
        );

        $this->preselectDefault();
    }

    protected function preselectDefault(): void
    {
        if ($this->defaultValue === null || $this->defaultValue === '') {
            return;
        }

        $query = (string) $this->defaultValue;

        $matches = ($this->options)($query);

        $keys = array_is_list($matches) ? $matches : array_keys($matches);

        $index = array_search($this->defaultValue, $keys, true);

        if ($index === false) {
            return;
        }

        $this->typedValue = $query;
        $this->cursorPosition = mb_strlen($query);
        $this->matches = $matches;

        $this->highlight($index);
    }

    /**
     * Prompts keys its renderer registry on the exact class name with no parent
     * fallback, so reuse the renderer registered for the parent SearchPrompt.
     */
    protected function getRenderer(): callable
    {
        $renderer = static::$themes[static::$theme][SearchPrompt::class]
            ?? static::$themes['default'][SearchPrompt::class];

        return new $renderer($this);
    }
}

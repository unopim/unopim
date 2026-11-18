<?php

declare(strict_types=1);

namespace Webkul\Product\Templates\Filters;

use Illuminate\Contracts\Container\Container;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Templates\Expression;
use Webkul\Product\Templates\FilterCall;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateValueFormatter;

class FilterRegistry
{
    public const string SUGGESTED_ARGUMENT = 'd.m.Y';

    protected array $filters = [
        'capitalize'  => CapitalizeFilter::class,
        'format_date' => FormatDateFilter::class,
        'lower'       => LowerFilter::class,
        'slug'        => SlugFilter::class,
        'title'       => TitleFilter::class,
        'upper'       => UpperFilter::class,
    ];

    protected array $instances = [];

    /**
     * Create a new filter registry instance.
     */
    public function __construct(protected Container $app) {}

    /**
     * Determine if a filter with the given name is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    /**
     * Get the filter instance for the given name, resolving it from the container only once.
     */
    public function get(string $name): TemplateFilter
    {
        return $this->instances[$name] ??= $this->app->make($this->filters[$name]);
    }

    /**
     * Get the names of all registered filters.
     */
    public function names(): array
    {
        return array_keys($this->filters);
    }

    /**
     * Get each filter's name, suggested call and supported attribute types.
     */
    public function suggestions(): array
    {
        return array_map(fn (string $name): array => [
            'name'  => $name,
            'value' => $this->suggestedCall($name),
            'types' => array_values(array_filter(TemplateValueFormatter::SUPPORTED_TYPES, $this->get($name)->supports(...))),
        ], $this->names());
    }

    /**
     * Return the first problem found in the expression's filter chain, or null when it is valid.
     */
    public function inspect(Expression $expression, string $attributeType): ?TemplateMessage
    {
        foreach ($expression->filters as $position => $call) {
            $problem = $this->inspectCall($call, $position, $expression, $attributeType);

            if ($problem !== null) {
                return $problem;
            }
        }

        return null;
    }

    private function suggestedCall(string $name): string
    {
        $filter = $this->get($name);

        return match (true) {
            $filter->acceptsArgument(null)                     => $name,
            $filter->acceptsArgument(self::SUGGESTED_ARGUMENT) => sprintf("%s('%s')", $name, self::SUGGESTED_ARGUMENT),
            default                                            => "{$name}('')",
        };
    }

    private function inspectCall(FilterCall $call, int $position, Expression $expression, string $attributeType): ?TemplateMessage
    {
        if (! $this->has($call->name)) {
            return new TemplateMessage('unknown-filter', ['filter' => $call->name, 'filters' => implode(', ', $this->names())]);
        }

        $filter = $this->get($call->name);

        $inputType = $position === 0 ? $attributeType : Attribute::TEXT_TYPE;

        return match (true) {
            ! $filter->supports($attributeType)         => new TemplateMessage('filter-not-supported', ['filter' => $call->name, 'attribute' => $expression->attributeCode]),
            ! $filter->supports($inputType)             => new TemplateMessage('format-date-position', ['filter' => $call->name, 'expression' => $expression->label]),
            ! $filter->acceptsArgument($call->argument) => new TemplateMessage('invalid-filter-argument', ['filter' => $call->name, 'expression' => $expression->label]),
            default                                     => null,
        };
    }
}

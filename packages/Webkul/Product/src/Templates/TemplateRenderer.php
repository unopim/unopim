<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\Attribute as AttributeModel;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Traits\HtmlPurifier;
use Webkul\Product\Contracts\TemplateRenderer as TemplateRendererContract;
use Webkul\Product\Models\Product;
use Webkul\Product\Templates\Filters\FilterRegistry;
use Webkul\Product\Templates\Results\RenderResult;

class TemplateRenderer implements TemplateRendererContract
{
    use HtmlPurifier;

    public const int MAX_DEPTH = 5;

    public const int MAX_RENDERED_BYTES = 1_000_000;

    /**
     * Create a new template renderer instance.
     */
    public function __construct(
        protected TemplateParser $parser,
        protected FilterRegistry $filters,
        protected TemplateValueFormatter $formatter,
        protected AttributeService $attributeService,
    ) {}

    /**
     * Render the template source with the given values, or return it unchanged when it holds no template.
     */
    public function render(string $source, Attribute $attribute, array $values, string $channel, string $locale): RenderResult
    {
        if (! $attribute->canHoldTemplate() || ! $this->parser->containsTemplate($source)) {
            return RenderResult::unchanged($source);
        }

        $context = new RenderContext($values, $channel, $locale);

        $output = $this->renderSource($source, $attribute, $context);

        return new RenderResult($source, $output, $context->errors(), $context->warnings());
    }

    /**
     * Render the attribute's template stored on the product for the given channel and locale.
     */
    public function renderProduct(Product $product, Attribute $attribute, string $channel, string $locale): RenderResult
    {
        $values = $product->resolvedValues();

        $source = $attribute->getValueFromProductValues($values, $channel, $locale);

        return $this->render(is_string($source) ? $source : '', $attribute, $values, $channel, $locale);
    }

    private function renderSource(string $source, Attribute $attribute, RenderContext $context): string
    {
        $template = $this->parser->parse($source, $attribute->isRichText());

        if ($template->hasErrors()) {
            $this->reportInvalidTemplate($template, $attribute, $context);

            return $this->finalize($source, $attribute);
        }

        $context->enter($attribute->code);

        $variables = $this->attributeService->findByCodes($template->attributeCodes());

        $output = '';

        foreach ($template->segments as $segment) {
            $piece = $segment instanceof Expression
                ? $this->escape($this->evaluate($segment, $variables[$segment->attributeCode] ?? null, $context) ?? '', $attribute)
                : $segment;

            if ($context->consumeOutput(strlen($piece)) > self::MAX_RENDERED_BYTES) {
                $context->warn(new TemplateMessage('output-too-large', ['max' => self::MAX_RENDERED_BYTES]));

                break;
            }

            $output .= $piece;
        }

        $context->leave();

        return $this->finalize($output, $attribute);
    }

    private function evaluate(Expression $expression, ?Attribute $variable, RenderContext $context): ?string
    {
        $problem = $this->variableProblem($expression, $variable, $context);

        if ($problem !== null) {
            $context->warn($problem);

            return null;
        }

        $value = $variable->canHoldTemplate()
            ? $this->renderReference($variable, $context)
            : $this->resolveValue($expression, $variable, $context);

        return $value === null ? null : $this->applyFilters($expression, $variable, $value, $context);
    }

    private function variableProblem(Expression $expression, ?Attribute $variable, RenderContext $context): ?TemplateMessage
    {
        return match (true) {
            $variable === null                      => new TemplateMessage('unknown-attribute', ['attribute' => $expression->attributeCode]),
            $context->isVisiting($variable->code)   => new TemplateMessage('circular-reference', ['path' => $context->pathTo($variable->code)]),
            ! $this->formatter->supports($variable) => new TemplateMessage('unsupported-attribute', ['attribute' => $variable->code]),
            default                                 => $this->currencyProblem($expression, $variable, $context)
                ?? $this->filters->inspect($expression, $variable->type),
        };
    }

    private function currencyProblem(Expression $expression, Attribute $variable, RenderContext $context): ?TemplateMessage
    {
        if ($expression->currency === null) {
            return null;
        }

        if ($variable->type !== AttributeModel::PRICE_FIELD_TYPE) {
            return new TemplateMessage('currency-not-allowed', ['attribute' => $variable->code]);
        }

        if (! in_array($expression->currency, $this->formatter->channelCurrencies($context->channel), true)) {
            return new TemplateMessage('currency-not-in-channel', ['currency' => $expression->currency, 'channel' => $context->channel]);
        }

        return null;
    }

    private function resolveValue(Expression $expression, Attribute $variable, RenderContext $context): ?string
    {
        $value = $this->formatter->format(
            $variable,
            $variable->getValueFromProductValues($context->values, $context->channel, $context->locale),
            $context->channel,
            $context->locale,
            $expression->currency,
        );

        if ($value === null) {
            $context->warn(new TemplateMessage('missing-value', ['attribute' => $variable->code]));
        }

        return $value;
    }

    private function renderReference(Attribute $variable, RenderContext $context): ?string
    {
        if ($context->hasRendered($variable->code)) {
            return $context->rendered($variable->code);
        }

        if ($context->depth() >= self::MAX_DEPTH) {
            $context->warn(new TemplateMessage('max-depth', ['max' => self::MAX_DEPTH, 'attribute' => $variable->code]));

            return null;
        }

        $source = $variable->getValueFromProductValues($context->values, $context->channel, $context->locale);

        if (! is_string($source) || ! is_filled_value($source)) {
            $context->warn(new TemplateMessage('missing-value', ['attribute' => $variable->code]));

            return $context->remember($variable->code, null);
        }

        $output = $this->renderSource($source, $variable, $context);

        $text = $variable->isRichText() ? $this->formatter->toPlainText($output) : $output;

        return $context->remember($variable->code, is_filled_value($text) ? $text : null);
    }

    private function applyFilters(Expression $expression, Attribute $variable, string $value, RenderContext $context): ?string
    {
        foreach ($expression->filters as $call) {
            $value = $this->filters->get($call->name)->apply($value, $call->argument, $context->locale);

            if ($value === null) {
                $context->warn(new TemplateMessage('filter-failed', ['filter' => $call->name, 'attribute' => $variable->code]));

                return null;
            }
        }

        return $value;
    }

    private function reportInvalidTemplate(ParsedTemplate $template, Attribute $attribute, RenderContext $context): void
    {
        if ($context->depth() > 0) {
            $context->warn(new TemplateMessage('invalid-reference', ['attribute' => $attribute->code]));

            return;
        }

        foreach ($template->errors as $error) {
            $context->error($error);
        }
    }

    private function escape(string $value, Attribute $attribute): string
    {
        return $attribute->isRichText() ? e($value) : $value;
    }

    private function finalize(string $output, Attribute $attribute): string
    {
        return $attribute->isRichText() ? $this->purifyRenderedHtml($output) : $output;
    }
}

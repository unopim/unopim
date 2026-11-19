<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\Attribute as AttributeModel;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Product\Contracts\TemplateValidator as TemplateValidatorContract;
use Webkul\Product\Templates\Filters\FilterRegistry;
use Webkul\Product\Templates\Results\ValidationResult;

class TemplateValidator implements TemplateValidatorContract
{
    private const string TAG_EXPRESSION_PATTERN = '/<[a-zA-Z\/!?][^<>]*\{\{/';

    private const string SINGLE_BRACE_PATTERN = '/(?<!\{)\{\s*(\w+)\s*\}(?!\})/';

    private const int MAX_SINGLE_BRACES = 100;

    private ?array $currencies = null;

    /**
     * Create a new template validator instance.
     */
    public function __construct(
        protected TemplateParser $parser,
        protected FilterRegistry $filters,
        protected TemplateValueFormatter $formatter,
        protected AttributeService $attributeService,
        protected CurrencyRepository $currencyRepository,
    ) {}

    /**
     * Validate the attribute's template source and collect its errors and single brace warnings.
     */
    public function validate(string $source, Attribute $attribute): ValidationResult
    {
        if (! $attribute->canHoldTemplate() || ! str_contains($source, '{')) {
            return new ValidationResult;
        }

        $template = $this->parser->parse($source, $attribute->isRichText());

        $variables = $this->attributeService->findByCodes($template->attributeCodes());

        $errors = [...$template->errors, ...$this->markupErrors($source, $attribute)];

        foreach ($template->expressions() as $expression) {
            $errors[] = $this->expressionProblem($expression, $variables[$expression->attributeCode] ?? null, $attribute);
        }

        return new ValidationResult(
            collect($errors)->filter()->unique(fn (TemplateMessage $error): string => $error->identity())->values()->all(),
            $this->singleBraceWarnings($template),
        );
    }

    private function expressionProblem(Expression $expression, ?Attribute $variable, Attribute $attribute): ?TemplateMessage
    {
        return match (true) {
            $variable === null                      => new TemplateMessage('unknown-attribute', ['attribute' => $expression->attributeCode]),
            $variable->code === $attribute->code    => new TemplateMessage('self-reference', ['attribute' => $attribute->code]),
            ! $this->formatter->supports($variable) => new TemplateMessage('unsupported-attribute', ['attribute' => $variable->code]),
            default                                 => $this->currencyProblem($expression, $variable)
                ?? $this->filters->inspect($expression, $variable->type),
        };
    }

    private function currencyProblem(Expression $expression, Attribute $variable): ?TemplateMessage
    {
        return match (true) {
            $expression->currency === null                                 => null,
            $variable->type !== AttributeModel::PRICE_FIELD_TYPE           => new TemplateMessage('currency-not-allowed', ['attribute' => $variable->code]),
            ! array_key_exists($expression->currency, $this->currencies()) => new TemplateMessage('unknown-currency', ['currency' => $expression->currency]),
            ! $this->currencies()[$expression->currency]                   => new TemplateMessage('currency-not-enabled', ['currency' => $expression->currency]),
            default                                                        => null,
        };
    }

    private function markupErrors(string $source, Attribute $attribute): array
    {
        if (! $attribute->isRichText()) {
            return [];
        }

        return match (preg_match(self::TAG_EXPRESSION_PATTERN, $source)) {
            1       => [new TemplateMessage('inside-html-tag')],
            false   => [new TemplateMessage('too-complex')],
            default => [],
        };
    }

    private function singleBraceWarnings(ParsedTemplate $template): array
    {
        $literalText = implode(' ', array_filter($template->segments, is_string(...)));

        $words = [];
        $offset = 0;

        while (count($words) < self::MAX_SINGLE_BRACES && preg_match(self::SINGLE_BRACE_PATTERN, $literalText, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $words[] = $match[1][0];
            $offset = $match[0][1] + strlen($match[0][0]);
        }

        return array_map(
            fn (string $word): TemplateMessage => new TemplateMessage('single-brace', ['text' => $word]),
            array_values(array_unique($words)),
        );
    }

    private function currencies(): array
    {
        return $this->currencies ??= $this->currencyRepository->getModel()->newQuery()
            ->withExists('channel')
            ->pluck('channel_exists', 'code')
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace Webkul\Product\Validator;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Contracts\Channel;
use Webkul\Core\Contracts\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Templates\TemplateDependencyGraph;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateParser;
use Webkul\Product\Type\AbstractType;

class ProductTemplatesValidator
{
    private const array VALUE_SECTIONS = [
        AbstractType::COMMON_VALUES_KEY,
        AbstractType::LOCALE_VALUES_KEY,
        AbstractType::CHANNEL_VALUES_KEY,
        AbstractType::CHANNEL_LOCALE_VALUES_KEY,
    ];

    private ?Collection $templateAttributes = null;

    private ?array $scopes = null;

    /**
     * Create a new product templates validator instance.
     */
    public function __construct(
        protected TemplateParser $parser,
        protected AttributeRepository $attributeRepository,
        protected ChannelRepository $channelRepository,
        protected ProductRepository $productRepository,
        protected VariantValueResolver $variantValueResolver,
    ) {}

    /**
     * Validate submitted templates for family and circular reference errors across all channels and locales.
     */
    public function validate(mixed $data, ?int $productId = null): void
    {
        if (! is_array($data) || ! $this->submitsTemplates($data) || $this->templateAttributes()->isEmpty()) {
            return;
        }

        $product = $productId ? $this->productRepository->find($productId) : null;

        $values = $product
            ? $this->variantValueResolver->mergeChain([$product->resolvedValues(), $data])
            : $data;

        $familyCodes = $this->familyAttributeCodes($product);

        $messages = [];

        foreach ($this->scopes() as [$channel, $locale]) {
            $messages = array_merge_recursive(
                $messages,
                $this->scopeMessages($this->references($values, $channel, $locale), $familyCodes, $channel, $locale),
            );
        }

        $messages = array_filter(
            $messages,
            fn (string $path): bool => Arr::has($data, $path),
            ARRAY_FILTER_USE_KEY,
        );

        if ($messages !== []) {
            throw ValidationException::withMessages(array_map(
                fn (array $pathMessages): array => array_values(array_unique($pathMessages)),
                $messages,
            ));
        }
    }

    /**
     * Get the family and circular reference errors for the attribute's template in the given scope.
     */
    public function attributeErrors(Attribute $attribute, array $values, ?Product $product, string $channel, string $locale): array
    {
        if (! $this->templateAttributes()->has($attribute->code)) {
            return [];
        }

        $messages = $this->scopeMessages(
            $this->reachableReferences($attribute->code, $values, $channel, $locale),
            $this->familyAttributeCodes($product),
            $channel,
            $locale,
        );

        return array_values(array_unique($messages[$this->path($attribute->code, $channel, $locale)] ?? []));
    }

    /**
     * Get the family and circular reference errors for the given template sources, keyed by attribute code.
     */
    public function sourceErrors(array $sources, array $familyCodes): array
    {
        $references = [];

        foreach ($sources as $code => $source) {
            $attribute = $this->templateAttributes()->get($code);

            $codes = $attribute ? $this->referencedCodes($attribute, $source) : null;

            if ($codes !== null) {
                $references[$code] = $codes;
            }
        }

        return array_map(
            fn (array $messages): array => array_values(array_unique($messages)),
            $this->codeMessages($references, $familyCodes),
        );
    }

    private function scopeMessages(array $references, ?array $familyCodes, string $channel, string $locale): array
    {
        return collect($this->codeMessages($references, $familyCodes))
            ->mapWithKeys(fn (array $messages, string|int $code): array => [$this->path($code, $channel, $locale) => $messages])
            ->all();
    }

    private function codeMessages(array $references, ?array $familyCodes): array
    {
        $messages = [];

        foreach ([...$this->familyMessages($references, $familyCodes), ...$this->circularMessages($references)] as [$code, $message]) {
            $messages[$code][] = $message;
        }

        return $messages;
    }

    private function referencedCodes(Attribute $attribute, mixed $source): ?array
    {
        return $this->parser->containsTemplate($source)
            ? $this->parser->parse($source, $attribute->isRichText())->attributeCodes()
            : null;
    }

    private function submitsTemplates(array $data): bool
    {
        return collect(Arr::dot(Arr::only($data, self::VALUE_SECTIONS)))
            ->contains(fn (mixed $value): bool => $this->parser->containsTemplate($value));
    }

    private function references(array $values, string $channel, string $locale): array
    {
        $references = [];

        foreach ($this->templateAttributes() as $code => $attribute) {
            $codes = $this->referencedCodes($attribute, $attribute->getValueFromProductValues($values, $channel, $locale));

            if ($codes !== null) {
                $references[$code] = $codes;
            }
        }

        return $references;
    }

    private function reachableReferences(string $code, array $values, string $channel, string $locale): array
    {
        $references = [];

        $visited = [];

        $pending = [$code];

        while ($pending !== []) {
            $current = array_shift($pending);

            if (isset($visited[$current]) || ! $this->templateAttributes()->has($current)) {
                continue;
            }

            $visited[$current] = true;

            $attribute = $this->templateAttributes()->get($current);

            $codes = $this->referencedCodes($attribute, $attribute->getValueFromProductValues($values, $channel, $locale));

            if ($codes !== null) {
                $references[$current] = $codes;

                array_push($pending, ...$codes);
            }
        }

        return $references;
    }

    private function familyMessages(array $references, ?array $familyCodes): array
    {
        if ($familyCodes === null) {
            return [];
        }

        $messages = [];

        foreach ($references as $code => $referencedCodes) {
            foreach (array_diff($referencedCodes, $familyCodes) as $referencedCode) {
                $messages[] = [$code, (new TemplateMessage('not-in-family', ['attribute' => $referencedCode]))->message()];
            }
        }

        return $messages;
    }

    private function circularMessages(array $references): array
    {
        $messages = [];

        foreach ((new TemplateDependencyGraph($references))->cycles() as $cycle) {
            $message = (new TemplateMessage('circular-reference', ['path' => implode(' → ', $cycle)]))->message();

            foreach (array_unique($cycle) as $code) {
                $messages[] = [$code, $message];
            }
        }

        return $messages;
    }

    private function familyAttributeCodes(?Product $product): ?array
    {
        return $product?->attribute_family
            ?->customAttributes()
            ->pluck('attributes.code')
            ->all();
    }

    private function path(string|int $code, string $channel, string $locale): string
    {
        return ltrim($this->templateAttributes()->get($code)->getFlatAttributeName($channel, $locale), '.');
    }

    private function templateAttributes(): Collection
    {
        return $this->templateAttributes ??= $this->attributeRepository
            ->findWhere(['enable_templates' => true])
            ->filter(fn (Attribute $attribute): bool => $attribute->canHoldTemplate())
            ->keyBy('code');
    }

    private function scopes(): array
    {
        return $this->scopes ??= $this->channelRepository
            ->with('locales')
            ->all()
            ->flatMap(fn (Channel $channel): array => $channel->locales
                ->map(fn (Locale $locale): array => [$channel->code, $locale->code])
                ->all())
            ->all();
    }
}

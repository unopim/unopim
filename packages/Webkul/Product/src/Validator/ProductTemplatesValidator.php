<?php

declare(strict_types=1);

namespace Webkul\Product\Validator;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
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

    public function __construct(
        protected TemplateParser $parser,
        protected AttributeRepository $attributeRepository,
        protected ChannelRepository $channelRepository,
        protected ProductRepository $productRepository,
        protected VariantValueResolver $variantValueResolver,
    ) {}

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

        if ($messages !== []) {
            throw ValidationException::withMessages(array_map(
                fn (array $pathMessages): array => array_values(array_unique($pathMessages)),
                $messages,
            ));
        }
    }

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

    private function scopeMessages(array $references, ?array $familyCodes, string $channel, string $locale): array
    {
        return array_merge_recursive(
            $this->familyMessages($references, $familyCodes, $channel, $locale),
            $this->circularMessages($references, $channel, $locale),
        );
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
            $source = $attribute->getValueFromProductValues($values, $channel, $locale);

            if ($this->parser->containsTemplate($source)) {
                $references[$code] = $this->parser->parse($source, $attribute->isRichText())->attributeCodes();
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

            $source = $attribute->getValueFromProductValues($values, $channel, $locale);

            if ($this->parser->containsTemplate($source)) {
                $references[$current] = $this->parser->parse($source, $attribute->isRichText())->attributeCodes();

                array_push($pending, ...$references[$current]);
            }
        }

        return $references;
    }

    private function familyMessages(array $references, ?array $familyCodes, string $channel, string $locale): array
    {
        if ($familyCodes === null) {
            return [];
        }

        $messages = [];

        foreach ($references as $code => $referencedCodes) {
            foreach (array_diff($referencedCodes, $familyCodes) as $referencedCode) {
                $messages[$this->path($code, $channel, $locale)][] = (new TemplateMessage('not-in-family', ['attribute' => $referencedCode]))->message();
            }
        }

        return $messages;
    }

    private function circularMessages(array $references, string $channel, string $locale): array
    {
        $messages = [];

        foreach ((new TemplateDependencyGraph($references))->cycles() as $cycle) {
            $message = (new TemplateMessage('circular-reference', ['path' => implode(' → ', $cycle)]))->message();

            foreach (array_unique($cycle) as $code) {
                $messages[$this->path($code, $channel, $locale)][] = $message;
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
            ->findWhere(['enable_templates' => 1])
            ->filter(fn (Attribute $attribute): bool => $attribute->canHoldTemplate())
            ->keyBy('code');
    }

    private function scopes(): array
    {
        return $this->scopes ??= $this->channelRepository
            ->with('locales')
            ->all()
            ->flatMap(fn ($channel): array => $channel->locales
                ->map(fn ($locale): array => [$channel->code, $locale->code])
                ->all())
            ->all();
    }
}

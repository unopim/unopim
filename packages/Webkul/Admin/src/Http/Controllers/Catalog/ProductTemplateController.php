<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Contracts\TemplateRenderer;
use Webkul\Product\Contracts\TemplateValidator;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Templates\TemplateValueFormatter;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductTemplatesValidator;

class ProductTemplateController extends Controller
{
    private const int SUGGESTION_LIMIT = 20;

    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository,
        protected TemplateRenderer $renderer,
        protected TemplateValidator $validator,
        protected ProductTemplatesValidator $productTemplatesValidator,
        protected VariantValueResolver $variantValueResolver,
    ) {}

    public function suggestions(int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        $search = trim((string) request()->query('query', ''));

        $locale = core()->getRequestedLocaleCode();

        $attributes = $this->attributeRepository->getModel()->newQuery()
            ->with('translations')
            ->whereIn('id', $product->attribute_family->customAttributes()->select('attributes.id'))
            ->whereIn('type', TemplateValueFormatter::SUPPORTED_TYPES)
            ->where('code', '!=', (string) request()->query('attribute', ''))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('code', "%{$search}%")
                ->orWhereHas('translations', fn ($query) => $query
                    ->where('locale', $locale)
                    ->whereLike('name', "%{$search}%"))))
            ->orderBy('code')
            ->limit(self::SUGGESTION_LIMIT)
            ->get();

        return new JsonResponse($attributes->map(fn (Attribute $attribute): array => [
            'code' => $attribute->code,
            'name' => $attribute->translate($locale)?->name ?: '['.$attribute->code.']',
            'type' => $attribute->type,
        ])->all());
    }

    public function preview(int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        $data = $this->validate(request(), [
            'attribute' => ['required', 'string', 'exists:attributes,code'],
            'values'    => ['nullable', 'array'],

            'values.'.AbstractType::COMMON_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::LOCALE_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_VALUES_KEY        => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_LOCALE_VALUES_KEY => ['nullable', 'array'],
        ]);

        $attribute = $this->attributeRepository->findOneByField('code', $data['attribute']);

        $submitted = $data['values'] ?? [];

        $values = $this->variantValueResolver->mergeChain([$product->resolvedValues(), $submitted]);

        $channel = core()->getRequestedChannelCode();

        $locale = core()->getRequestedLocaleCode();

        $source = $attribute->getValueFromProductValues($values, $channel, $locale);

        $source = is_string($source) ? $source : '';

        $validation = $this->validator->validate($source, $attribute);

        $result = $this->renderer->render($source, $attribute, $values, $channel, $locale);

        $valid = $validation->passes();

        $errors = array_values(array_unique([
            ...$validation->errorMessages(),
            ...($valid ? $this->productErrors($product, $attribute, $submitted, $channel, $locale) : []),
            ...$result->errorMessages(),
        ]));

        $warnings = array_values(array_diff(array_unique([
            ...$validation->warningMessages(),
            ...($valid ? $result->warningMessages() : []),
        ]), $errors));

        return new JsonResponse([
            'attribute' => $attribute->code,
            'rich_text' => $attribute->isRichText(),
            'source'    => $source,
            'output'    => $result->output,
            'errors'    => $errors,
            'warnings'  => $warnings,
        ]);
    }

    private function productErrors(Product $product, Attribute $attribute, array $submitted, string $channel, string $locale): array
    {
        try {
            $this->productTemplatesValidator->validate($submitted, $product->id);
        } catch (ValidationException $exception) {
            return $exception->errors()[ltrim($attribute->getFlatAttributeName($channel, $locale), '.')] ?? [];
        }

        return [];
    }
}

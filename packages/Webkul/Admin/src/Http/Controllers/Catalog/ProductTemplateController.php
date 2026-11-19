<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\ProductTemplatePreviewForm;
use Webkul\Admin\Http\Requests\ProductTemplateSuggestionsForm;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Contracts\TemplateRenderer;
use Webkul\Product\Contracts\TemplateValidator;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Templates\Results\RenderResult;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateValueFormatter;
use Webkul\Product\Validator\ProductTemplatesValidator;

class ProductTemplateController extends Controller
{
    public const int SUGGESTION_LIMIT = 50;

    private const array DATA_WARNINGS = ['missing-value'];

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository,
        protected TemplateRenderer $renderer,
        protected TemplateValidator $validator,
        protected ProductTemplatesValidator $productTemplatesValidator,
        protected VariantValueResolver $variantValueResolver,
    ) {}

    /**
     * Return the product family attributes usable as template variables, filtered by the query.
     */
    public function suggestions(ProductTemplateSuggestionsForm $request, int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        $search = trim((string) $request->validated('query'));

        $code = trim((string) $request->validated('code'));

        $locale = core()->getRequestedLocaleCode();

        $attributes = $this->attributeRepository->getModel()->newQuery()
            ->with('translations')
            ->whereIn('id', $product->attribute_family->customAttributes()->select('attributes.id'))
            ->whereIn('type', TemplateValueFormatter::SUPPORTED_TYPES)
            ->where('code', '!=', (string) $request->validated('attribute'))
            ->when($code !== '', fn (Builder $query): Builder => $query->where('code', $code))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                ->whereLike('code', "%{$search}%")
                ->orWhereHas('translations', fn (Builder $query): Builder => $query
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

    /**
     * Render the attribute template for the product and return the output with its errors and warnings.
     */
    public function preview(ProductTemplatePreviewForm $request, int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        $data = $request->validated();

        $attribute = $this->attributeRepository->findOneByField('code', $data['attribute']);

        $values = $this->variantValueResolver->mergeChain([$product->resolvedValues(), $data['values'] ?? []]);

        $channel = core()->getRequestedChannelCode();

        $locale = core()->getRequestedLocaleCode();

        $source = $attribute->getValueFromProductValues($values, $channel, $locale);

        $source = is_string($source) ? $source : '';

        $validation = $this->validator->validate($source, $attribute);

        $result = $this->renderer->render($source, $attribute, $values, $channel, $locale);

        $valid = $validation->passes();

        $errors = array_values(array_unique([
            ...$validation->errorMessages(),
            ...($valid ? $this->productTemplatesValidator->attributeErrors($attribute, $values, $product, $channel, $locale) : []),
            ...$result->errorMessages(),
        ]));

        $warnings = array_values(array_diff(array_unique([
            ...$validation->warningMessages(),
            ...$this->renderWarnings($result, $valid),
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

    private function renderWarnings(RenderResult $result, bool $valid): array
    {
        if ($valid) {
            return $result->warningMessages();
        }

        return array_map(
            fn (TemplateMessage $warning): string => $warning->message(),
            array_values(array_filter(
                $result->warnings,
                fn (TemplateMessage $warning): bool => in_array($warning->key, self::DATA_WARNINGS, true),
            )),
        );
    }
}

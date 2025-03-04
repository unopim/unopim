<?php

namespace Webkul\MagicAI\Services\Prompt;

use Illuminate\Support\Str;
use Webkul\Category\Facades\CategoryAdditionalDataMapper as CategoryAdditionalDataMapperFacade;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;

class CategoryPrompt extends AbstractPrompt
{
    private static $instance;

    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository
    ) {}

    /**
     * Gets the singleton instance of AIModel.
     */
    public static function getInstance(): CategoryPrompt
    {
        if (self::$instance === null) {
            self::$instance = new self(app(CategoryRepository::class), app(CategoryFieldRepository::class));
        }

        return self::$instance;
    }

    /**
     * Replaces placeholders in the prompt with category field values.
     */
    public function updatePrompt(string $prompt, int $categoryId): string
    {
        $categoryFields = $this->searchStringWithAt($prompt);
        $category = $this->getCategoryById($categoryId);
        $categoryData = $category->toArray();
        $locale = core()->getRequestedLocaleCode();

        $values = array_merge(
            CategoryAdditionalDataMapperFacade::getLocaleSpecificFields($categoryData, $locale),
            CategoryAdditionalDataMapperFacade::getCommonFields($categoryData)
        );

        foreach ($categoryFields as $fieldCodeWithAt) {
            $fieldCode = Str::replaceFirst('@', '', $fieldCodeWithAt);
            $categoryField = $this->findCategoryFieldByCode($fieldCode);

            if (! $categoryField) {
                continue;
            }

            $value = $this->getValue($values, $fieldCode);
            $prompt = Str::replaceFirst($fieldCodeWithAt, $value, $prompt);
        }

        return $prompt;
    }

    public function getCategoryById($categoryId)
    {
        return $this->categoryRepository->find($categoryId);
    }

    public function findCategoryFieldByCode($code)
    {
        return $this->categoryFieldRepository->findOneByField('code', $code);
    }
}

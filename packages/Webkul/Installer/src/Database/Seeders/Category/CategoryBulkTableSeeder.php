<?php

namespace Webkul\Installer\Database\Seeders\Category;

use Faker\Generator as Faker;
use Illuminate\Database\Seeder;
use Webkul\Category\Repositories\CategoryRepository;

/*
 * Category bulk table seeder.
 *
 * Command: php artisan db:seed --class=Webkul\\Installer\\Database\\Seeders\\Category\\CategoryBulkTableSeeder
 *
 * This seeder has not included anywhere just for development purpose.
 */
class CategoryBulkTableSeeder extends Seeder
{
    /**
     * @var Faker
     */
    public $faker;

    /**
     * @var CategoryRepository
     */
    public $categoryRepository;

    private int $numberOfParentCategories = 10;

    private int $numberOfChildCategories = 50;

    public function __construct(
        Faker $faker,
        CategoryRepository $categoryRepository
    ) {
        $this->faker = $faker;
        $this->categoryRepository = $categoryRepository;
    }

    public function run(): void
    {
        for ($i = 0; $i < $this->numberOfParentCategories; $i++) {
            $createdCategory = $this->categoryRepository->create([
                'code'        => $this->faker->firstName.random_int(1, 1000),
                'parent_id'   => 1,
            ]);

            if ($createdCategory) {
                for ($j = 0; $j < $this->numberOfChildCategories; $j++) {

                    $this->categoryRepository->create([
                        'code'        => $this->faker->firstName.random_int(1000, 10000),
                        'parent_id'   => $createdCategory->id,
                    ]);
                }
            }
        }
    }
}

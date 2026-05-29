<?php

declare(strict_types=1);

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
    private int $numberOfParentCategories = 10;

    private int $numberOfChildCategories = 50;

    public function __construct(public Faker $faker, public CategoryRepository $categoryRepository) {}

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

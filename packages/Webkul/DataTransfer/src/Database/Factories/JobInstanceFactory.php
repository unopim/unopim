<?php

namespace Webkul\DataTransfer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;

class JobInstanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JobInstances::class;

    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        return [
            'code'            => fake()->unique()->word,
            'entity_type'     => 'products',
            'field_separator' => ',',
        ];
    }

    public function importJob($action = null, $validation = null): JobInstanceFactory
    {
        Storage::fake();

        if ($action !== 'delete' || $action !== 'append') {
            $action = 'append';
        }

        if ($validation !== 'skip-erros' || $validation !== 'stop-on-errors') {
            $validation = 'skip-erros';
        }

        return $this->state(function () use ($action, $validation) {
            return [
                'type'                  => 'import',
                'images_directory_path' => '',
                'allowed_errors'        => fake()->numberBetween(0, 20),
                'file_path'             => UploadedFile::fake()->create('product.csv')->path(),
                'action'                => $action,
                'validation_strategy'   => $validation,
            ];
        });
    }

    public function entityProduct(): JobInstanceFactory
    {
        return $this->state(function () {
            return [
                'entity_type' => 'products',
            ];
        });
    }

    public function entityCategory(): JobInstanceFactory
    {
        return $this->state(function () {
            return [
                'entity_type' => 'categories',
            ];
        });
    }

    public function exportJob(): JobInstanceFactory
    {
        return $this->state(function () {
            return [
                'type'    => 'export',
                'filters' => [
                    'file_format' => 'Csv',
                    'with_media'  => 1,
                ],
            ];
        });
    }
}

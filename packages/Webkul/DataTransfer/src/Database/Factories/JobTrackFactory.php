<?php

namespace Webkul\DataTransfer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

class JobTrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JobTrack::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $jobInstance = JobInstances::factory()->importJob()->create();

        return [
            'state'                 => 'pending',
            'type'                  => 'import',
            'action'                => 'append',
            'validation_strategy'   => 'skip-erros',
            'allowed_errors'        => 0,
            'processed_rows_count'  => 0,
            'invalid_rows_count'    => 0,
            'errors_count'          => 0,
            'errors'                => null,
            'field_separator'       => ',',
            'file_path'             => null,
            'images_directory_path' => null,
            'error_file_path'       => null,
            'summary'               => null,
            'meta'                  => ['batch_count' => 0],
            'job_instances_id'      => $jobInstance->id,
            'user_id'               => null,
            'started_at'            => null,
            'completed_at'          => null,
        ];
    }

    /**
     * Set the state to completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'state'        => 'completed',
            'started_at'   => now()->subMinutes(5),
            'completed_at' => now(),
            'summary'      => ['total' => 100, 'created' => 80, 'updated' => 20],
        ]);
    }

    /**
     * Set the type to export.
     */
    public function export(): static
    {
        $jobInstance = JobInstances::factory()->exportJob()->create();

        return $this->state(fn () => [
            'type'             => 'export',
            'action'           => 'export',
            'job_instances_id' => $jobInstance->id,
        ]);
    }
}

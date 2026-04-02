<?php

namespace Webkul\DataTransfer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

class JobTrackBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = JobTrackBatch::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'state'        => 'pending',
            'data'         => [['sku' => 'test-sku-'.fake()->unique()->word]],
            'summary'      => null,
            'job_track_id' => JobTrack::factory(),
        ];
    }

    /**
     * Set the state to processed.
     */
    public function processed(): static
    {
        return $this->state(fn () => [
            'state'   => 'processed',
            'summary' => ['created' => 10, 'updated' => 5],
        ]);
    }

    /**
     * Set the state to failed.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'state' => 'failed',
        ]);
    }
}

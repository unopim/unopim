<?php

namespace Webkul\Measurement\Tests;

use Tests\TestCase;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\User\Tests\Concerns\UserAssertions;

class MeasurementTestCase extends TestCase
{
    use UserAssertions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Channel::query()->exists()) {
            if (! Category::whereIsRoot()->exists()) {
                $rootCategory = Category::create([
                    'code' => 'root',
                ]);

                $rootCategory->additional_data = [
                    'locale_specific' => [
                        config('app.locale') => [
                            'name' => 'Root',
                        ],
                    ],
                ];

                $rootCategory->save();
            }

            Channel::factory()->create();
        }

        $channel = Channel::first();
        config()->set('app.channel', $channel->code);
        core()->setDefaultChannel($channel);
    }
}

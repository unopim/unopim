<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Core\Repositories\ChannelRepository;

class Channel extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
    ) {}

    /**
     * This method calculates and returns the total number of channels in the system.
     *
     * @return int The total number of channels.
     */
    public function getTotalChannels(): int
    {
        return $this->channelRepository
            ->resetModel()
            ->count();
    }

    /**
     * Translated channel display label (falling back to the code when the
     * current locale has no translation), keyed by channel code.
     *
     * @return array<string, string>
     */
    public function getLabelsByCode(): array
    {
        return $this->channelRepository
            ->resetModel()
            ->getChannelAsOptions()
            ->pluck('label', 'code')
            ->all();
    }
}

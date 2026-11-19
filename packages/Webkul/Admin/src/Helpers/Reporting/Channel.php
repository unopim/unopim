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
     * Channel display labels for the current locale, keyed by channel code.
     * Channels without a name fall back to "[code]".
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

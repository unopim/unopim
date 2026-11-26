<?php

namespace Webkul\Notification\Mail;

use Illuminate\Contracts\Config\Repository;

/**
 * Whether the install has an outgoing mail transport set up, so notifications may also go out by e-mail.
 */
class MailConfiguration
{
    public function __construct(protected Repository $config) {}

    /**
     * Whether a default mailer and an SMTP host and port are configured.
     */
    public function isConfigured(): bool
    {
        return (bool) $this->config->get('mail.default')
            && (bool) $this->config->get('mail.mailers.smtp.host')
            && (bool) $this->config->get('mail.mailers.smtp.port');
    }
}

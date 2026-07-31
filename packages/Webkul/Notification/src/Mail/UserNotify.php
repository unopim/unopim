<?php

namespace Webkul\Notification\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserNotify extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected array $recipients,
        protected string $emailSubject,
        protected string $emailTemplate,
        protected array $templateData = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->recipients,
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->emailTemplate,
            with: $this->templateData,
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Queued notification mail failed', [
            'template'   => $this->emailTemplate,
            'subject'    => $this->emailSubject,
            'recipients' => $this->recipients,
            'exception'  => $exception->getMessage(),
        ]);
    }
}

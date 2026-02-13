<?php

namespace Webkul\Tenant\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Tenant\Models\Tenant;

class TenantWelcomeMail extends Mailable
{
    public function __construct(
        public Tenant $tenant,
        public string $email,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to {$this->tenant->name} on UnoPim",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        return "<h1>Welcome to {$this->tenant->name}</h1>"
            ."<p>Your account has been created on UnoPim.</p>"
            ."<p><strong>Email:</strong> {$this->email}</p>"
            ."<p><strong>Password:</strong> {$this->password}</p>"
            ."<p><strong>Domain:</strong> {$this->tenant->domain}</p>"
            ."<p>Please change your password after first login.</p>";
    }
}

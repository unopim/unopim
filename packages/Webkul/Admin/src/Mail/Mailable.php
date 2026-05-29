<?php

namespace Webkul\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Mail\Message;
use Illuminate\Queue\SerializesModels;

class Mailable extends BaseMailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Add the sender to the message.
     *
     * @param  Message  $message
     */
    #[\Override]
    protected function buildFrom($message): Mailable
    {
        empty($this->from)
            ? $message->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
            : $message->from($this->from[0]['address'], $this->from[0]['name']);

        return $this;
    }
}

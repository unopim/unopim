<?php

namespace Webkul\Shopify\Exceptions;

use Exception;

/**
 * Class InvalidCredential
 *
 * Exception thrown when an invalid credential is provided.
 * This may occur if the credential is disabled or incorrect.
 */
class InvalidCredential extends Exception
{
    /**
     * @var string The error message for the exception.
     */
    protected $message = 'Invalid Credential: The credential is either disabled, incorrect, or does not exist';
}

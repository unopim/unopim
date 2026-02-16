<?php

namespace Webkul\Shopify\Exceptions;

use Exception;

/**
 * Class Invalidlocale
 *
 * Exception thrown when an invalid locale is provided.
 * This may occur if the locale is disabled or incorrect.
 */
class InvalidLocale extends Exception
{
    /**
     * @var string The error message for the exception.
     */
    protected $message = 'Invalid Locale: The Locale is not mapped to a valid';
}

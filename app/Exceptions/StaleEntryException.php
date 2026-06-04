<?php

namespace App\Exceptions;

use RuntimeException;

class StaleEntryException extends RuntimeException
{
    public function __construct(string $message = 'This entry was changed elsewhere. Reload and try again.')
    {
        parent::__construct($message);
    }
}

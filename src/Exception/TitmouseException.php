<?php

declare(strict_types = 1);

namespace NestboxPHP\Titmouse\Exception;

use Exception;
use NestboxPHP\Nestbox\Exception\NestboxException;

class TitmouseException extends NestboxException
{
    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

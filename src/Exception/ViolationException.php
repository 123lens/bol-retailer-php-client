<?php

namespace Picqer\BolRetailerV10\Exception;

use Throwable;

class ViolationException extends Exception
{
    private $violations = [];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $this->setViolations($message);

        parent::__construct("Validation Failed, See violations", $code, $previous);
    }

    public function setViolations($violations)
    {
        $this->violations = is_array($violations) ? $violations : [];
    }

    public function getViolations()
    {
        return $this->violations;
    }
}

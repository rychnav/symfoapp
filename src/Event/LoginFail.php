<?php

namespace App\Event;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginFail
{
    public const NAME = 'login.fail';

    private $exception;

    public function __construct(AuthenticationException $exception)
    {
        $this->exception = $exception;
    }

    public function getThrowable(): AuthenticationException
    {
        return $this->exception;
    }
}

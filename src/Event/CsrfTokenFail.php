<?php

namespace App\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class CsrfTokenFail extends Event
{
    public const NAME = 'security.csrf.fail';

    private $flashText;
    private $request;

    public function __construct(
        Request $request,
        string $flashText = 'Invalid CSRF token.'
    ) {
        $this->flashText = $flashText;
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFlashText(): string
    {
        return $this->flashText;
    }
}

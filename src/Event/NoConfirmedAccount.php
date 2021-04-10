<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class NoConfirmedAccount extends Event
{
    public const NAME = 'account.no_confirmed';

    private $message;

    public function __construct(string $message = 'Your account has not been verified yet')
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}

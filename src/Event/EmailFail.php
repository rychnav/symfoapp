<?php

namespace App\Event;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailFail
{
    public const NAME = 'email.fail';

    private $exception;
    private $actionRoute;
    private $actionText;
    private $flashText;

    public function __construct(
        TransportExceptionInterface $exception,
        string $actionRoute,
        string $flashText = 'The email was not sent',
        string $actionText = 'Try again'
    ) {
        $this->exception = $exception;
        $this->actionRoute = $actionRoute;
        $this->flashText = $flashText;
        $this->actionText = $actionText;
    }

    public function getThrowable(): TransportExceptionInterface
    {
        return $this->exception;
    }

    public function getActionRoute(): string
    {
        return $this->actionRoute;
    }

    public function getFlashText(): string
    {
        return $this->flashText;
    }

    public function getActionText(): string
    {
        return $this->actionText;
    }
}

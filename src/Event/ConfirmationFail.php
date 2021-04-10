<?php

namespace App\Event;

class ConfirmationFail
{
    public const NAME = 'confirmation.fail';
    public const EMAIL_NOT_FOUND_MESSAGE = 'User with email could not be found';

    private $message;
    private $email;
    private $transDomain;

    public function __construct(string $email, string $message, string $transDomain = 'messages')
    {
        $this->message = $message;
        $this->email = $email;
        $this->transDomain = $transDomain;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTransDomain(): string
    {
        return $this->transDomain;
    }
}

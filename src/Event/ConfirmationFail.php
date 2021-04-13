<?php

namespace App\Event;

class ConfirmationFail
{
    public const NAME = 'confirmation.fail';
    public const EMAIL_NOT_FOUND_MESSAGE = 'User with email could not be found';
    public const ID_NOT_FOUND_MESSAGE = 'Such user does not exist';
    public const INVALID_RESET_TOKEN_MESSAGE = 'Reset password link is invalid';
    public const EXPIRED_RESET_TOKEN_MESSAGE = 'Reset password link is expired';

    private $message;
    private $email;
    private $transDomain;

    public function __construct(?string $email, string $message, string $transDomain = 'messages')
    {
        $this->message = $message;
        $this->email = $email;
        $this->transDomain = $transDomain;
    }

    public function getEmail(): ?string
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

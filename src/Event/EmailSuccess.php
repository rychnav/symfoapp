<?php

namespace App\Event;

class EmailSuccess
{
    public const NAME = 'email.success';

    private $flashText;

    public function __construct(
        string $flashText = 'The email was successfully sent'
    ) {
        $this->flashText = $flashText;
    }

    public function getFlashText(): string
    {
        return $this->flashText;
    }
}

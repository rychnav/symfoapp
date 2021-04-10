<?php

namespace App\Event;

use App\Entity\User;
use DateTime;

class ShouldWait
{
    public const NAME = 'token.should_wait';

    private $endDate;
    private $message;
    private $startDate;
    private $user;

    public function __construct(
        User $user,
        DateTime $startDate,
        DateTime $endDate,
        $message = 'Please, wait a little, then try again'
    ) {
        $this->user = $user;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->message = $message;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}

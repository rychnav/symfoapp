<?php

namespace App\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ProfileUpdateSuccess extends Event
{
    public const NAME = 'profile.update.success';

    private $user;
    private $oldData;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
        $this->oldData = (array) $user;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getOldData(): array
    {
        return $this->oldData;
    }
}

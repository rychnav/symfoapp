<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserUpdateSuccess extends Event
{
    public const NAME = 'user.update.success';

    private $user;
    private $oldData;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->oldData = (array) $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOldData(): array
    {
        return $this->oldData;
    }
}

<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserProfileData
{
    /**
     * @Assert\NotBlank(payload={"severity"="error"})
     * @Assert\Length(min=2, max=50, payload={"severity"="error"})
     */
    public $firstName;

    /**
     * @Assert\NotBlank(payload={"severity"="error"})
     * @Assert\Email(payload={"severity"="error"})
     */
    public $email;
}

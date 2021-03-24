<?php

namespace App\DTO;

use App\Entity\User;
use App\Validator\Constraints\UniqueValue;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @UniqueValue(field="email", class="App\Entity\User", payload={"severity"="error"})
 */
class UserEntityData extends AbstractEntityData
{
    /**
     * @Assert\NotBlank(payload={"severity"="error"})
     * @Assert\Email(payload={"severity"="error"})
     */
    public $email;

    /**
     * @Assert\Choice(choices={{"ROLE_USER"}, {"ROLE_ADMIN"}}, payload={"severity"="error"})
     */
    public $roles;

    /**
     * @Assert\NotBlank(payload={"severity"="error"})
     * @Assert\Length(min=6, max=50, payload={"severity"="error"})
     */
    public $password;

    public function fromEntity(User $user): self
    {
        $this->id = $user->getId();
        $this->email = $user->getEmail();
        $this->roles = $user->getRealRoles();
        $this->password = $user->getPassword();

        return $this;
    }

    public function toEntity(UserEntityData $data, User $user, UserPasswordEncoderInterface $encoder): User
    {
        $user->setEmail($data->email);
        $user->setRoles($data->roles);
        $user->setPassword($encoder->encodePassword($user, $data->password));

        return $user;
    }
}

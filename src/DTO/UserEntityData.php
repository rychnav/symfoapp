<?php

namespace App\DTO;

use App\Entity\User;
use App\Validator\Constraints\UniqueValue;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Avoid creating the new entity with existing email and entering the existing email during updating entity.
 * @UniqueValue(field="email", class="App\Entity\User", groups={"create", "update", "register"}, payload={"severity"="error"})
 */
class UserEntityData extends AbstractEntityData
{
    /**
     * @Assert\NotBlank(groups={"create", "update", "register"}, payload={"severity"="error"})
     * @Assert\Email(groups={"create", "update", "register"}, payload={"severity"="error"})
     */
    public $email;

    /**
     * @Assert\Choice(groups={"create", "update"}, choices={{"ROLE_USER"}, {"ROLE_ADMIN"}}, payload={"severity"="error"})
     */
    public $roles;

    /**
     * @Assert\NotBlank(groups={"create", "register"}, payload={"severity"="error"})
     * @Assert\Length(groups={"create", "update", "register"}, min=6, max=50, payload={"severity"="error"})
     *
     * Allow updating user without entering the password.
     * @Assert\NotBlank(groups={"update"}, allowNull=true, payload={"severity"="error"})
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
        $user->setRoles($data->roles ?? ['ROLE_USER']);

        if ($data->password) {
            $user->setPassword($encoder->encodePassword($user, $data->password));
        }

        return $user;
    }
}

<?php

namespace App\DTO;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserSearchData
{
    public $firstName;
    public $email;
    public $roles;

    /**
     * @Assert\Expression(
     *     expression="this.firstName || this.email || this.roles || value",
     *     message="At least one of the fields must be filled",
     *     payload={"severity"="error"}
     * )
     */
    public $authType;

    public function fromForm(FormInterface $form): self
    {
        $data = new UserSearchData();

        $data->firstName = $form->get('firstName')->getData() ?: 'null';
        $data->email = $form->get('email')->getData() ?: 'null';
        $data->roles = $form->get('roles')->getData() ?: 'null';
        $data->authType = $form->get('authType')->getData() ?: 'null';

        return $data;
    }
}

<?php

namespace App\DTO;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserSearchData
{
    public $firstName;
    public $email;
    public $roles;
    public $authType;

    /**
     * @Assert\Expression(
     *     expression="this.firstName || this.email || this.roles || this.authType || value['from'] !== null || value['to'] !== null",
     *     message="At least one of the fields must be filled",
     *     payload={"severity"="error"}
     * )
     */
    public $registerRange;

    public function fromForm(FormInterface $form): self
    {
        $data = new UserSearchData();

        $from = $form->get('registerRange')->getData()['from'];
        $to = $form->get('registerRange')->getData()['to'];

        $data->firstName = $form->get('firstName')->getData() ?: 'null';
        $data->email = $form->get('email')->getData() ?: 'null';
        $data->roles = $form->get('roles')->getData() ?: 'null';
        $data->authType = $form->get('authType')->getData() ?: 'null';
        $data->registerRange['from'] = is_object($from) ? $from->format('Y-m-d') : 'null';
        $data->registerRange['to'] = is_object($to) ? $to->format('Y-m-d') : 'null';

        return $data;
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class ConfirmEmailType extends ModalFormType
{
    public $security;

    public function __construct(RequestStack $requestStack, Security $security)
    {
        parent::__construct($requestStack);

        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->setMethod('POST')

            ->add('email', EmailType::class, [
                'data' => $user ? $user->getEmail() : null,
                'label' => 'Email',
                'help' => 'Your email',
            ])
        ;
    }
}

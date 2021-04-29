<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;

class ProfileType extends AbstractType
{
    private $security;

    public function __construct(Security $security) {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $appUser = $this->security->getUser();

        $builder
            ->setAction($options['action'])
            ->setMethod('POST')

            ->add('firstName', TextType::class, [
                'label' => 'Name',
                'help' => 'Your name',
                'data' => $appUser->getFirstName(),
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'help' => 'Your email',
                'data' => $appUser->getEmail(),
            ])
        ;
    }
}

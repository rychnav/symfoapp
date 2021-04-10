<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;

class UserSearchType extends ModalFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($options['action'])
            ->setMethod('POST')

            ->add('firstName', SearchType::class, [
                'required' => false,
                'attr' => ['name' => 'firstName'],
                'label' => 'Name',
                'help' => 'User name',
            ])
            ->add('email', SearchType::class, [
                'required' => false,
                'attr' => ['name' => 'email'],
                'label' => 'Email',
                'help' => 'User email',
            ])
            ->add('roles', ChoiceType::class, [
                'required' => false,
                'attr' => ['name' => 'roles'],
                'placeholder' => 'Choose the role',
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Administrator' => 'ROLE_ADMIN',
                ],
                'choice_attr' => [
                    'Choose the role' => [
                        'disabled' => true,
                        'selected' => true
                    ],
                ],
            ])
            ->add('authType', ChoiceType::class, [
                'required' => false,
                'attr' => ['name' => 'authType'],
                'label' => 'Authentication type',
                'placeholder' => 'Choose the authentication type',
                'choices' => [
                    'Email' => 'email',
                    'Google' => 'google',
                ],
                'choice_attr' => [
                    'Choose the authentication type' => [
                        'disabled' => true,
                        'selected' => true
                    ],
                ],
            ])
            ->add('registerRange', FromToDateType::class, [
                'required' => false,
                'from_label' => 'Registration date from',
                'to_label' => 'Registration date to',
            ])
        ;
    }
}

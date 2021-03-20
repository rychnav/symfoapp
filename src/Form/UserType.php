<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($options['action'])
            ->setMethod('POST')

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'help' => 'User email',
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'help' => 'User password',
            ])
            ->add('roles', ChoiceType::class, [
                'help' => 'User role',
                'placeholder' => 'Choose the role',
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Administrator' => 'ROLE_ADMIN',
                ],
                'choice_attr' => [
                    'User role' => [
                        'disabled' => true,
                        'selected' => true
                    ],
                ],
            ])
        ;

        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    return $rolesArray[0] ?? null;
                },
                function ($rolesString) {
                    return [$rolesString];
                }
            ))
        ;
    }
}

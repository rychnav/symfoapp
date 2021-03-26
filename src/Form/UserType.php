<?php

namespace App\Form;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends ModalFormType
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

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getData()->id) {
            $view['password']->vars['required'] = false;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('validation_groups', function (FormInterface $form) {
            $data = $form->getData();

            if ($data && $data->id !== null) {
                return ['update'];
            }

            return ['create'];
        });
    }
}

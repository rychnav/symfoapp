<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $ajaxAttrs = ['data-role' => 'modal-form'];
        $formAttrs = [];

        $attrs = $this->requestStack->getCurrentRequest()->isXmlHttpRequest()
            ? array_merge($formAttrs, $ajaxAttrs )
            : $formAttrs
        ;

        $resolver->setDefaults([
            'attr' => $attrs,
        ]);
    }
}

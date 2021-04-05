<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends ModalFormType
{
    private $utils;

    public function __construct(AuthenticationUtils $authenticationUtils, RequestStack $requestStack)
    {
        parent::__construct($requestStack);

        $this->utils = $authenticationUtils;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $request = $this->requestStack->getMasterRequest();
        $isRemembered = isset($request->request->all('login')['rememberMe']);

        $builder
            ->setAction($request->getUri())
            ->setMethod('POST')

            ->add('email', EmailType::class, [
                'help' => 'Your email',
                'data' => $this->utils->getLastUsername(),
                'constraints' => [
                    new Email(['payload' => ['severity' => 'error']]),
                    new NotBlank(['payload' => ['severity' => 'error']]),
                ],
            ])

            ->add('password', PasswordType::class, [
                'help' => 'Your password',
                'constraints' => [
                    new NotBlank(['payload' => ['severity' => 'error']]),
                    new Length(['min' => 6, 'max' => 50, 'payload' => ['severity' => 'error']]),
                ],
            ])

            ->add('rememberMe', CheckboxType::class, [
                'label' => 'Remember me',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'name' => '_remember_me',
                    'checked' => (bool) $isRemembered,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('csrf_token_id', 'authenticate');
    }
}

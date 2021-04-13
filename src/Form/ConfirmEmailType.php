<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class ConfirmEmailType extends ModalFormType
{
    private $security;

    public function __construct(RequestStack $requestStack, Security $security, UrlGeneratorInterface $urlGenerator)
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
                'data' => $user ? $user->getEmail() : $options['email'],
                'label' => 'Email',
                'help' => 'Your email',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'email' => null,
        ]);
    }
}

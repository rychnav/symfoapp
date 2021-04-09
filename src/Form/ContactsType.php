<?php

namespace App\Form;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactsType extends ModalFormType
{
    private $security;

    public function __construct(
        RequestStack $requestStack,
        Security $security
    ) {
        parent::__construct($requestStack);

        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->setAction($this->requestStack->getMasterRequest()->getUri())
            ->setMethod('POST')

            ->add('name', TextType::class, [
                'data' => $user ? $user->getFirstName() : null,
                'help' => 'Your name',
                'constraints' => [
                    new NotBlank(['payload' => ['severity' => 'error']]),
                    new Length(['min' => 3, 'max' => 50, 'payload' => ['severity' => 'error']]),
                ],
            ])
            ->add('email', EmailType::class, [
                'data' => $user ? $user->getEmail() : null,
                'help' => 'Your email',
                'constraints' => [
                    new Email(['payload' => ['severity' => 'error']]),
                    new Length(['min' => 3, 'max' => 50, 'payload' => ['severity' => 'error']]),
                    new NotBlank(['payload' => ['severity' => 'error']]),
                ],
            ])
            ->add('subject', TextType::class, [
                'help' => 'Email subject',
                'constraints' => [
                    new NotBlank(['payload' => ['severity' => 'error']]),
                    new Length(['min' => 3, 'max' => 100, 'payload' => ['severity' => 'error']]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'help' => 'Message text',
                'constraints' => [
                    new NotBlank(['payload' => ['severity' => 'error']]),
                ],
            ])
            ->add('captcha', CaptchaType::class, [
                'help' => 'Enter the captcha',
                'background_color' => [250, 250, 250],
            ]);
        ;
    }
}

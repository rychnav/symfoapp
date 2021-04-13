<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordType extends ModalFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($this->requestStack->getMasterRequest()->getUri())
            ->setMethod('POST')

            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'constraints' => [
                    new NotBlank(['payload' => ['severity' => 'error']]),
                    new Length(['min' => 6, 'max' => 50, 'payload' => ['severity' => 'error']]),
                ],
                'first_options' => [
                    'label' => 'Password',
                    'help' => 'Your password',
                ],
                'second_options' => [
                    'label' => 'Repeat password',
                    'help' => 'Repeat your password, please',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefined(['token', 'user']);
    }
}

<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

/**
 * Class RegisterType.
 */
class RegisterType extends ModalFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $request = $this->requestStack->getMasterRequest();

        $builder
            ->setAction($request->getUri())
            ->setMethod('POST')

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'help' => 'Your email',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'help' => 'Your password',
                ],
                'second_options' => [
                    'label' => 'Repeat password',
                    'help' => 'Repeat your password, please',
                ],
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'label' => 'Terms accepted',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'groups' => ['register']
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('validation_groups', 'register');
    }
}

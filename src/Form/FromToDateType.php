<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

class FromToDateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'from_label' => null,
            'to_label' => null,
            'from_help' => null,
            'to_help' => null,
            'error_mapping' => [
                '.' => 'to',
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('from', DateType::class, [
                'widget' => 'single_text',
                'html5'  => false,
                'label' => $options['from_label'],
                'help' => $options['from_help'] ?? 'Start date',
            ])
            ->add('to', DateType::class, [
                'html5'  => false,
                'widget' => 'single_text',
                'label' => $options['to_label'],
                'help' => $options['to_help'] ?? 'End date',
                'constraints' => [
                    new GreaterThan([
                        'propertyPath' => 'parent.all[from].data',
                        'payload' => ['severity' => 'error']
                    ]),
                ],
            ])
        ;
    }
}

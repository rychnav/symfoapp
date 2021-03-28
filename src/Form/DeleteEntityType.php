<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class DeleteEntityType extends ModalFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($options['action'])
            ->setMethod('POST')

            ->add('delete_permanently', CheckboxType::class, [
                'label' => 'I want to permanently delete an entity',
                'label_translation_parameters' => [
                    'entity' => $options['entity'],
                    'entity_title' => $options['entity_title'],
                ],
                'constraints' => [
                    new IsTrue()
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => null,
            'entity' => '',
            'entity_title' => '',
        ]);
    }
}

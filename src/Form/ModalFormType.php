<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModalFormType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $formAttrs = $this->requestStack->getCurrentRequest()->isXmlHttpRequest()
            ? ['data-role' => 'modal-form']
            : []
        ;

        $resolver->setDefaults([
            'attr' => $formAttrs,
        ]);
    }
}

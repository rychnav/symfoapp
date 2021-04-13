<?php

namespace App\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetConfirmation
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function fillEmail(User $user, string $noReplyAddress, string $subject = 'Resetting password'): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address($noReplyAddress))
            ->to(new Address($user->getEmail(), $user->getFirstName()))
            ->replyTo($noReplyAddress)
            ->priority(TemplatedEmail::PRIORITY_HIGH)
            ->subject($this->translator->trans($subject))
            ->htmlTemplate('parts/messages/email/_reset-confirmation.html.twig')
            ->context([
                'user' => $user,
            ])
        ;
    }
}

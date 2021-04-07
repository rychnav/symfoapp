<?php

namespace App\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class Feedback
{
    public $name;
    public $email;
    public $subject;
    public $message;

    public function fillEmail(
        string $emailTo,
        string $nameTo = 'Administrator',
        array $context = []
    ): TemplatedEmail {
        return (new TemplatedEmail())
            ->from(new Address($this->email, $this->name))
            ->to(new Address($emailTo, $nameTo))
            ->replyTo($this->email)
            ->priority(TemplatedEmail::PRIORITY_HIGH)
            ->subject($this->subject)
            ->htmlTemplate('parts/messages/email/_feedback.html.twig')
            ->context($context)
        ;
    }
}

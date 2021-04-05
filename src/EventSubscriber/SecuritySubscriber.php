<?php

namespace App\EventSubscriber;

use App\DTO\FlashMessage;
use App\Event\LoginFail;
use App\Event\LoginSuccess;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SecuritySubscriber implements EventSubscriberInterface
{
    private $logger;
    private $session;

    public function __construct(
        LoggerInterface $logger,
        SessionInterface $session
    ) {
        $this->logger = $logger;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccess::NAME => 'onLoginSuccess',
            LoginFail::NAME => 'onLoginFail',
        ];
    }

    public function onLoginSuccess(LoginSuccess $event): void
    {
        $user = $event->getUser();

        $flashMessage = new FlashMessage(
            'success',
            'Hi! Welcome here!',
            null,
            ['username' => $user->getEmail()]
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('The user was logged in', [
            'email' => $user->getEmail(),
        ]);
    }

    public function onLoginFail(LoginFail $event): void
    {
        $exception = $event->getThrowable();

        $flashMessage = new FlashMessage(
            'error',
            $exception->getMessage(),
            null,
            [],
            'security'
        );

        $this->session->getBag('flashes')->add($flashMessage->type, $flashMessage);
        $this->logger->info('Login was failed', [
            'exception' => $event->getThrowable()->getMessage(),
        ]);
    }
}

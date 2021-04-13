<?php

namespace App\EventSubscriber;

use App\DTO\FlashAction;
use App\DTO\FlashMessage;
use App\Event\ConfirmationFail;
use App\Event\InvalidToken;
use App\Event\LoginFail;
use App\Event\LoginSuccess;
use App\Event\NoConfirmedAccount;
use App\Event\NotYetReset;
use App\Event\RegisterSuccess;
use App\Event\ShouldWait;
use DateTime;
use App\Event\TokenExpired;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecuritySubscriber implements EventSubscriberInterface
{
    private $logger;
    private $session;
    private $urlGenerator;

    public function __construct(
        LoggerInterface $logger,
        SessionInterface $session,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->logger = $logger;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfirmationFail::NAME => 'onConfirmationFail',
            LoginSuccess::NAME => 'onLoginSuccess',
            LoginFail::NAME => 'onLoginFail',
            RegisterSuccess::NAME => 'onRegisterSuccess',
            NoConfirmedAccount::NAME => 'onNoConfirmedAccount',
            ShouldWait::NAME => 'onShouldWait',
        ];
    }

    public function onLoginSuccess(LoginSuccess $event): void
    {
        $user = $event->getUser();

        $flashMessage = new FlashMessage(
            'success',
            'Hi! Welcome here!',
            null,
            ['username' => $user->getFirstName()]
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('The user was logged in', [
            'email' => $user->getEmail(),
        ]);
    }

    public function onLoginFail(LoginFail $event): void
    {
        $exception = $event->getThrowable();
        $transDomain = $exception->getMessageData()['transDomain'] ?? 'security';
        $transParams = $exception->getMessageData()['transParams'] ?? [];

        $flashMessage = new FlashMessage(
            'error',
            $exception->getMessage(),
            null,
            $transParams,
            $transDomain
        );

        $this->session->getBag('flashes')->add($flashMessage->type, $flashMessage);
        $this->logger->info('Login was failed', [
            'exception' => $event->getThrowable()->getMessage(),
        ]);
    }

    public function onRegisterSuccess(RegisterSuccess $event): void
    {
        $this->logger->info('The new user was register', [
            'email' => $event->getUser()->getEmail(),
        ]);
    }

    public function onConfirmationFail(ConfirmationFail $event): void
    {
        $email = $event->getEmail() ?? 'NULL';
        $message = $event->getMessage();

        $flashMessage = new FlashMessage(
            'error',
            $event->getMessage(),
            null,
            ['email' => $email],
            $event->getTransDomain()
        );

        $this->session->getBag('flashes')->add($flashMessage->type, $flashMessage);
        $this->logger->info($message, [
            'email' => $email,
        ]);
    }

    public function onNoConfirmedAccount(NoConfirmedAccount $event): void
    {
        $flashAction = new FlashAction(
            'Confirm',
            $this->urlGenerator->generate('register_confirm')
        );

        $flashMessage = new FlashMessage(
            'warning',
            $event->getMessage(),
            $flashAction,
            []
        );

        $this->session->getBag('flashes')->add($flashMessage->type, $flashMessage);
    }

    public function onShouldWait(ShouldWait $event): void
    {
        $interval = $event->getStartDate()->diff($event->getEndDate());

        $flashMessage = new FlashMessage(
            'error',
            $event->getMessage(),
            null,
            ['hours' => $interval->h, 'minutes' => $interval->i]
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info($event->getMessage(), [
            'email' => $event->getUser()->getEmail(),
        ]);
    }
}

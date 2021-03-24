<?php

namespace App\EventSubscriber;

use App\DTO\FlashAction;
use App\DTO\FlashMessage;
use App\Event\UserCreateSuccess;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserSubscriber implements EventSubscriberInterface
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
            UserCreateSuccess::NAME => 'onCreateSuccess',
        ];
    }

    public function onCreateSuccess(UserCreateSuccess $event): void
    {
        $user = $event->getUser();

        $flashAction = new FlashAction(
            'Add more',
            $this->urlGenerator->generate('user_create')
        );

        $flashMessage = new FlashMessage(
            'success',
            'The new user was created successfully',
            $flashAction,
            ['email' => $user->getEmail()]
        );

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('New user was created', [
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}

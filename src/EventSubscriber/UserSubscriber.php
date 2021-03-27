<?php

namespace App\EventSubscriber;

use App\DTO\FlashAction;
use App\DTO\FlashMessage;
use App\Event\UserCreateSuccess;
use App\Event\UserUpdateSuccess;
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
            UserUpdateSuccess::NAME => 'onUpdateSuccess',
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

    public function onUpdateSuccess(UserUpdateSuccess $event): void
    {
        $updatedUser = $event->getUser();

        $flashAction = new FlashAction(
            'Show details',
            $this->urlGenerator->generate('user_details', [
                'id' => $event->getUser()->getId()
            ])
        );

        $flashMessage = new FlashMessage(
            'success',
            'The user was updated successfully',
            $flashAction,
            ['email' => $updatedUser->getEmail()]
        );

        // TODO: Replace to DiffComparator compare($oldEntity, $newDto);

        $diff = array_diff_assoc(
            array_map('json_encode', $event->getOldData()),
            array_map('json_encode',(array) $updatedUser)
        );

        $res = [];
        foreach($diff as $key => $value) {
            // From: `{"\u0000App\\Entity\\User\u0000roles":"[\"ROLE_ADMIN\"]"}}`
            // To: [{"email":"rempel.hassie@gmail.com"},{"roles":"[ROLE_USER]"},{"password":"$argon2...3U"}]
            $tmpKey = explode("\0", $key);
            $res[] = [end($tmpKey) => str_replace('"', "", trim($value))];
        }

        $this->session->getFlashBag()->add($flashMessage->type, $flashMessage);
        $this->logger->info('User was updated', [
            'email' => $event->getUser()->getEmail(),
            'old_values' => $res,
        ]);
    }
}

<?php

namespace App\EventSubscriber;

use App\DTO\FlashMessage;
use App\Event\ProfileUpdateSuccess;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProfileSubscriber implements EventSubscriberInterface
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
            ProfileUpdateSuccess::NAME => 'onUpdateSuccess',
        ];
    }

    public function onUpdateSuccess(ProfileUpdateSuccess $event): void
    {
        $updatedUser = $event->getUser();

        $flashMessage = new FlashMessage(
            'success',
            'Your account was updated successfully',
            null,
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
        $this->logger->info('Profile was updated', [
            'email' => $event->getUser()->getEmail(),
            'old_values' => $res,
        ]);
    }
}

<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class IdBag
{
    public const USER_BAG_KEY = 'user_ids';

    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function hasIds(string $sessionKey): bool
    {
        return $this->session->has($sessionKey);
    }

    public function getAll(string $sessionKey): ?array
    {
        return $this->session->get($sessionKey);
    }

    public function saveFromQuery(Query $query, string $sessionKey): void
    {
        $results = $query->getResult();

        $newIds = [];
        if (count($results) > 0) {
            foreach ($results as $entity) {
                $newIds[] = $entity->getId();
            }

            $this->session->set($sessionKey, $newIds);
        }
    }

    public function clear(string $sessionKey): void
    {
        $this->session->remove($sessionKey);
    }
}

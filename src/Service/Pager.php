<?php

namespace App\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

class Pager
{
    public function paginate($query, Request $request, int $limit): Paginator
    {
        $currentPage = $request->attributes->getInt('page') ?: 1;
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($currentPage - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    public function lastPage(Paginator $paginator): int
    {
        return ceil($paginator->count() / $paginator->getQuery()->getMaxResults());
    }
}

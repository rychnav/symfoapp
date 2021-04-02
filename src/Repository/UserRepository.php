<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\IdBag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private const USER_BAG_KEY = IdBag::USER_BAG_KEY;

    private $idBag;

    public function __construct(ManagerRegistry $registry, IdBag $idBag)
    {
        parent::__construct($registry, User::class);

        $this->idBag = $idBag;
    }

    private function getFromSession(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if ($this->idBag->hasIds(self::USER_BAG_KEY)) {
            $qb->andWhere('u.id IN (:ids)')
                ->setParameter('ids', $this->idBag->getAll(self::USER_BAG_KEY));
        } else {
            return $this->createQueryBuilder('u');
        }

        return $qb;
    }

    public function sort(string $property, string $order): Query
    {
        $qb = $this->getFromSession();

        return $qb->orderBy("u.$property", $order)
            ->getQuery();
    }

    public function search(string $email, string $roles): Query
    {
        $qb = $this->getFromSession();

        if ($email !== 'null') {
            $qb->andWhere("u.email LIKE :email_val")
                ->setParameter('email_val', "%$email%");
        }

        if ($roles !== 'null') {
            $qb->andWhere("u.roles LIKE :roles_val")
                ->setParameter('roles_val', "%$roles%");
        }

        return $qb->getQuery();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * @return User[] Returns an array of User objects
     *
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }/

    /*public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }*/
}

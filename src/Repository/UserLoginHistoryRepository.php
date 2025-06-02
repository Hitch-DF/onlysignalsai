<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserLoginHistory;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLoginHistory>
 */
class UserLoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLoginHistory::class);
    }

    /**
     * Retourne la date de dernière connexion d'un utilisateur.
     *
     * @param User $user
     * @return DateTimeInterface|null
     */
    public function findLastLoginDateForUser(User $user): ?DateTimeInterface
    {
        return $this->createQueryBuilder('h')
            ->select('h.loginAt')
            ->where('h.user = :user')
            ->setParameter('user', $user)
            ->orderBy('h.loginAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()['loginAt'] ?? null;
    }
}

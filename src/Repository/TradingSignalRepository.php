<?php

namespace App\Repository;

use App\Entity\TradingSignal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TradingSignal>
 */
class TradingSignalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradingSignal::class);
    }

    public function findActiveSignals(): array
    {
        return $this->findBy(
            ['status' => true, 
            'fake' => false],
            ['createdAt' => 'DESC']
        );
    }

    public function findHistoricalSignals(): array
    {
        return $this->findBy(
            ['status' => false, 'fake' => false],
            ['createdAt' => 'DESC']
        );
    }

    public function findFakeSignals(int $limit = 3): array
    {
        return $this->findBy(
            ['fake' => true],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}

<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Card>
 *
 * @method Card|null find($id, $lockMode = null, $lockVersion = null)
 * @method Card|null findOneBy(array $criteria, array $orderBy = null)
 * @method Card[]    findAll()
 * @method Card[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CardRepository extends ServiceEntityRepository
{
    public const CARDS_PER_PAGE = 100;

    public function __construct(
        ManagerRegistry $registry,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($registry, Card::class);
    }

    public function getAllUuids(): array
    {
        $this->logger->debug('Fetching all card UUIDs');
        $result = $this->createQueryBuilder('c')
            ->select('c.uuid')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
        
        $uuids = array_column($result, 'uuid');
        $this->logger->info('Found {count} card UUIDs', ['count' => count($uuids)]);
        
        return $uuids;
    }

    /**
     * Get paginated list of all cards
     */
    public function getPaginatedCards(int $page = 1, ?string $setCode = null): array
    {
        $this->logger->debug('Fetching paginated cards', [
            'page' => $page,
            'setCode' => $setCode
        ]);

        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($setCode) {
            $qb->andWhere('c.setCode = :setCode')
               ->setParameter('setCode', $setCode);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query);
        
        $totalItems = count($paginator);
        $lastPage = ceil($totalItems / self::CARDS_PER_PAGE);
        $page = max(1, min($page, $lastPage));

        $paginator
            ->getQuery()
            ->setFirstResult(self::CARDS_PER_PAGE * ($page - 1))
            ->setMaxResults(self::CARDS_PER_PAGE);

        $items = iterator_to_array($paginator);
        
        $this->logger->info('Retrieved paginated cards', [
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $lastPage,
            'items_returned' => count($items),
            'set_code' => $setCode
        ]);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'items_per_page' => self::CARDS_PER_PAGE,
            'total_pages' => $lastPage,
            'current_page' => $page
        ];
    }

    /**
     * Search cards by name with pagination
     */
    public function searchByName(string $name, ?string $setCode = null, int $page = 1): array
    {
        $this->logger->debug('Searching cards by name', [
            'name' => $name,
            'setCode' => $setCode,
            'page' => $page
        ]);

        $qb = $this->createQueryBuilder('c')
            ->where('LOWER(c.name) LIKE LOWER(:name)')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('c.name', 'ASC');

        if ($setCode) {
            $qb->andWhere('c.setCode = :setCode')
               ->setParameter('setCode', $setCode);
        }

        $query = $qb->getQuery();
        $paginator = new Paginator($query);
        
        $totalItems = count($paginator);
        $lastPage = max(1, ceil($totalItems / self::CARDS_PER_PAGE));
        $page = max(1, min($page, $lastPage));

        $paginator
            ->getQuery()
            ->setFirstResult(self::CARDS_PER_PAGE * ($page - 1))
            ->setMaxResults(self::CARDS_PER_PAGE);

        $items = iterator_to_array($paginator);
        
        $this->logger->info('Search results', [
            'query' => $name,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $lastPage,
            'items_returned' => count($items),
            'set_code' => $setCode
        ]);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'items_per_page' => self::CARDS_PER_PAGE,
            'total_pages' => $lastPage,
            'current_page' => $page
        ];
    }

    /**
     * Get all unique setCodes with their count
     */
    public function getAllSetCodes(): array
    {
        $this->logger->debug('Fetching all set codes');
        
        $result = $this->createQueryBuilder('c')
            ->select('c.setCode, COUNT(c.id) as cardCount')
            ->groupBy('c.setCode')
            ->orderBy('c.setCode', 'ASC')
            ->getQuery()
            ->getResult();

        $this->logger->info('Found {count} unique set codes', [
            'count' => count($result)
        ]);

        return $result;
    }
}

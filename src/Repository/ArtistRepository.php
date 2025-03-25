<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Artist>
 *
 * @method Artist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artist[]    findAll()
 * @method Artist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistRepository extends ServiceEntityRepository
{
    public const ARTISTS_PER_PAGE = 50;

    public function __construct(
        ManagerRegistry $registry,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($registry, Artist::class);
    }

    /**
     * Get paginated list of all artists
     */
    public function getPaginatedArtists(int $page = 1): array
    {
        $this->logger->debug('Fetching paginated artists', [
            'page' => $page
        ]);

        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.Name', 'ASC');

        $query = $qb->getQuery();
        $paginator = new Paginator($query);
        
        $totalItems = count($paginator);
        $lastPage = ceil($totalItems / self::ARTISTS_PER_PAGE);
        $page = max(1, min($page, $lastPage));

        $paginator
            ->getQuery()
            ->setFirstResult(self::ARTISTS_PER_PAGE * ($page - 1))
            ->setMaxResults(self::ARTISTS_PER_PAGE);

        $items = iterator_to_array($paginator);
        
        $this->logger->info('Retrieved paginated artists', [
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $lastPage,
            'items_returned' => count($items)
        ]);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'items_per_page' => self::ARTISTS_PER_PAGE,
            'total_pages' => $lastPage,
            'current_page' => $page
        ];
    }

    /**
     * Search artists by name with pagination
     */
    public function searchByName(string $name, int $page = 1): array
    {
        $this->logger->debug('Searching artists by name', [
            'name' => $name,
            'page' => $page
        ]);

        $qb = $this->createQueryBuilder('a')
            ->where('LOWER(a.Name) LIKE LOWER(:name)')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('a.Name', 'ASC');

        $query = $qb->getQuery();
        $paginator = new Paginator($query);
        
        $totalItems = count($paginator);
        $lastPage = max(1, ceil($totalItems / self::ARTISTS_PER_PAGE));
        $page = max(1, min($page, $lastPage));

        $paginator
            ->getQuery()
            ->setFirstResult(self::ARTISTS_PER_PAGE * ($page - 1))
            ->setMaxResults(self::ARTISTS_PER_PAGE);

        $items = iterator_to_array($paginator);
        
        $this->logger->info('Search results', [
            'query' => $name,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $lastPage,
            'items_returned' => count($items)
        ]);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'items_per_page' => self::ARTISTS_PER_PAGE,
            'total_pages' => $lastPage,
            'current_page' => $page
        ];
    }
}

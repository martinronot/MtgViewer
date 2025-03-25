<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/artist', name: 'api_artist_')]
#[OA\Tag(name: 'Artist', description: 'Routes for artist management')]
class ApiArtistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/all', name: 'all', methods: ['GET'])]
    #[OA\Get(
        description: 'Get all artists with pagination',
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number (1-based)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns paginated list of artists',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Artist')),
                new OA\Property(property: 'total_items', type: 'integer'),
                new OA\Property(property: 'items_per_page', type: 'integer'),
                new OA\Property(property: 'total_pages', type: 'integer'),
                new OA\Property(property: 'current_page', type: 'integer')
            ]
        )
    )]
    public function getAllArtists(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        $this->logger->debug('Fetching all artists', [
            'page' => $page,
            'ip' => $request->getClientIp()
        ]);

        try {
            /** @var ArtistRepository $repository */
            $repository = $this->entityManager->getRepository(Artist::class);
            $result = $repository->getPaginatedArtists($page);

            $this->logger->info('Successfully retrieved artists', [
                'page' => $page,
                'total_results' => $result['total_items'],
                'results_returned' => count($result['items']),
                'ip' => $request->getClientIp()
            ]);

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve artists', [
                'page' => $page,
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);

            throw $e;
        }
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Get(
        description: 'Search artists by name',
        parameters: [
            new OA\Parameter(
                name: 'query',
                description: 'Search query (minimum 3 characters)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 3)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number (1-based)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns matching artists with pagination',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Artist')),
                new OA\Property(property: 'total_items', type: 'integer'),
                new OA\Property(property: 'items_per_page', type: 'integer'),
                new OA\Property(property: 'total_pages', type: 'integer'),
                new OA\Property(property: 'current_page', type: 'integer')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid search query (less than 3 characters)',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string')
            ]
        )
    )]
    public function searchArtists(Request $request): Response
    {
        $query = $request->query->get('query');
        $page = max(1, $request->query->getInt('page', 1));

        $this->logger->debug('Searching artists', [
            'query' => $query,
            'page' => $page,
            'ip' => $request->getClientIp()
        ]);

        if (empty($query) || strlen($query) < 3) {
            $this->logger->notice('Invalid search query', [
                'query' => $query,
                'length' => strlen($query ?? ''),
                'ip' => $request->getClientIp()
            ]);

            return $this->json([
                'error' => 'Search query must be at least 3 characters long'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var ArtistRepository $repository */
            $repository = $this->entityManager->getRepository(Artist::class);
            $result = $repository->searchByName($query, $page);

            $this->logger->info('Search completed successfully', [
                'query' => $query,
                'page' => $page,
                'total_results' => $result['total_items'],
                'results_returned' => count($result['items']),
                'ip' => $request->getClientIp()
            ]);

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error('Search failed', [
                'query' => $query,
                'page' => $page,
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);

            throw $e;
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Parameter(name: 'id', description: 'ID of the artist', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Get(description: 'Get an artist by ID')]
    #[OA\Response(response: 200, description: 'Show artist')]
    #[OA\Response(response: 404, description: 'Artist not found')]
    public function showArtist(int $id, Request $request): Response
    {
        $this->logger->debug('Fetching artist by ID', [
            'id' => $id,
            'ip' => $request->getClientIp()
        ]);

        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        if (!$artist) {
            $this->logger->notice('Artist not found', [
                'id' => $id,
                'ip' => $request->getClientIp()
            ]);

            return $this->json(['error' => 'Artist not found'], Response::HTTP_NOT_FOUND);
        }

        $this->logger->info('Successfully retrieved artist', [
            'id' => $id,
            'name' => $artist->getName(),
            'ip' => $request->getClientIp()
        ]);

        return $this->json($artist);
    }
}

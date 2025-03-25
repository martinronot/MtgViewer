<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Get(
        description: 'Search cards by name and optionally filter by setCode',
        parameters: [
            new OA\Parameter(
                name: 'query',
                description: 'Search query (minimum 3 characters)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 3)
            ),
            new OA\Parameter(
                name: 'setCode',
                description: 'Filter by set code',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
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
        description: 'Returns matching cards with pagination information',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Card')),
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
    public function search(Request $request): Response
    {
        $query = $request->query->get('query');
        $setCode = $request->query->get('setCode');
        $page = max(1, $request->query->getInt('page', 1));
        
        $this->logger->debug('Received search request', [
            'query' => $query,
            'setCode' => $setCode,
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
            $result = $this->entityManager->getRepository(Card::class)->searchByName($query, $setCode, $page);
            
            $this->logger->info('Search completed successfully', [
                'query' => $query,
                'setCode' => $setCode,
                'page' => $page,
                'total_results' => $result['total_items'],
                'results_returned' => count($result['items']),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error('Search failed', [
                'query' => $query,
                'setCode' => $setCode,
                'page' => $page,
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            throw $e;
        }
    }

    #[Route('/set-codes', name: 'set_codes', methods: ['GET'])]
    #[OA\Get(description: 'Get all available set codes')]
    #[OA\Response(
        response: 200,
        description: 'List of all set codes with their card count',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'setCode', type: 'string'),
                    new OA\Property(property: 'cardCount', type: 'integer')
                ]
            )
        )
    )]
    public function getSetCodes(Request $request): Response
    {
        $this->logger->debug('Fetching set codes', [
            'ip' => $request->getClientIp()
        ]);
        
        try {
            $setCodes = $this->entityManager->getRepository(Card::class)->getAllSetCodes();
            
            $this->logger->info('Successfully retrieved set codes', [
                'count' => count($setCodes),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json($setCodes);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve set codes', [
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            throw $e;
        }
    }

    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Get(
        description: 'Return all cards in the database with pagination',
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number (1-based)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'setCode',
                description: 'Filter by set code',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns paginated list of cards',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Card')),
                new OA\Property(property: 'total_items', type: 'integer'),
                new OA\Property(property: 'items_per_page', type: 'integer'),
                new OA\Property(property: 'total_pages', type: 'integer'),
                new OA\Property(property: 'current_page', type: 'integer')
            ]
        )
    )]
    public function cardAll(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $setCode = $request->query->get('setCode');
        
        $this->logger->debug('Fetching all cards', [
            'page' => $page,
            'setCode' => $setCode,
            'ip' => $request->getClientIp()
        ]);
        
        try {
            $result = $this->entityManager->getRepository(Card::class)->getPaginatedCards($page, $setCode);
            
            $this->logger->info('Successfully retrieved all cards', [
                'page' => $page,
                'setCode' => $setCode,
                'total_results' => $result['total_items'],
                'results_returned' => count($result['items']),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve all cards', [
                'page' => $page,
                'setCode' => $setCode,
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            throw $e;
        }
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Get(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid, Request $request): Response
    {
        $this->logger->debug('Fetching card by UUID', [
            'uuid' => $uuid,
            'ip' => $request->getClientIp()
        ]);
        
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        
        if (!$card) {
            $this->logger->notice('Card not found', [
                'uuid' => $uuid,
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json(['error' => 'Card not found'], 404);
        }
        
        $this->logger->info('Successfully retrieved card', [
            'uuid' => $uuid,
            'name' => $card->getName(),
            'setCode' => $card->getSetCode(),
            'ip' => $request->getClientIp()
        ]);
        
        return $this->json($card);
    }
}

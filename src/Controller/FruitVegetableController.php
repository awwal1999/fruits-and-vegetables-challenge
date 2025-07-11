<?php

namespace App\Controller;

use App\Request\GetItemsRequest;
use App\Request\AddItemRequest;
use App\Service\FruitVegetableService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RequestValidator;

#[Route('/api')]
class FruitVegetableController extends AbstractController
{
    private FruitVegetableService $service;

    public function __construct(FruitVegetableService $service)
    {
        $this->service = $service;
    }

    #[Route('/process', name: 'process_request', methods: ['POST'])]
    public function processRequest(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            $result = $this->service->processRequest($data);

            return $this->json($result, count($result['duplicates']) > 0 ? 207 : 200);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/items', name: 'get_items', methods: ['GET'])]
    public function getItems(Request $request): JsonResponse
    {
        try {
            $getItemsRequest = new GetItemsRequest($request->query->all());
            $result = $this->service->getItems(
                $getItemsRequest->type,
                $getItemsRequest->getFilters(),
                $getItemsRequest->unit
            );
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/items', name: 'add_item', methods: ['POST'])]
    public function addItem(Request $request, RequestValidator $requestValidator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'Invalid JSON'], 400);
            }

            $addItemRequest = new AddItemRequest($data);
            $requestValidator->validate($addItemRequest);

            $result = $this->service->addItem($addItemRequest->toArray());

            if (isset($result['status']) && $result['status'] === 'duplicate') {
                return $this->json(['error' => $result['reason']], 409);
            }

            return $this->json(['message' => 'Item added successfully', 'item' => $result['item'] ?? null]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
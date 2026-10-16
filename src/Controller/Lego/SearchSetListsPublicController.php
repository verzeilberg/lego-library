<?php

namespace App\Controller\Lego;

use App\Repository\FriendshipRepository;
use App\Service\Lego\SearchSetListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchSetListsPublicController extends AbstractController
{
    public function __construct(
        private readonly SearchSetListService $searchSetListService,
        private readonly Security             $security,
        private readonly FriendshipRepository $friendshipRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $query  = trim((string) $request->query->get('q', ''));
        $limit  = max(1, (int) $request->query->get('limit', 10));
        $page   = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        if ($query === '') {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $friendIds = array_map(
            fn($friend) => $friend->getId(),
            $this->friendshipRepository->getFriendUserDataList($user->getUserData()),
        );

        $results = $this->searchSetListService->searchPublic($query, $limit, $offset, $user->getUserData()->getId(), $friendIds);

        return new JsonResponse($results, Response::HTTP_OK);
    }
}

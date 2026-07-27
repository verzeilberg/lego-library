<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Service\Lego\SetListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetSetListChildrenAndSetsController extends AbstractController
{

    public function __construct(
        private readonly SetListRepository    $setListRepository,
        private readonly SetListSetRepository $setListSetRepository,
        private readonly SetListService       $setListService,
        private readonly Security             $security,
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        // Only allow access if the user is the owner or the board is shared with them or it's public
        $userData = $user->getUserData();
        if ($setList->getUserData() !== $userData && !$setList->isSharedWith($userData) && !$setList->isPublic()) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $query = trim((string) $request->query->get('q', ''));

        if ($query !== '') {
            $childLists = $this->setListRepository->findChildrenByQuery($id, $query);
            $setLinks   = $this->setListSetRepository->findBySetListAndQuery($id, $query);
        } else {
            $childLists = $setList->getChildLists();
            $setLinks   = $setList->getSetLinks();
        }

        $result = $this->setListService->getCombinedListWithSets($childLists, $setLinks);

        return $this->json($result, 200, [], ['groups' => ['setList:read']]);
    }
}

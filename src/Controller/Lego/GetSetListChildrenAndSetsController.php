<?php

namespace App\Controller\Lego;

use App\Dto\Request\Lego\SetListsRequest;
use App\Repository\Lego\SetListRepository;
use App\Repository\Lego\SetListSetRepository;
use App\Service\Lego\BoardAccessService;
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
        private readonly BoardAccessService   $boardAccessService,
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

        // Only allow access if the user may see this board (public, friends-only for friends, owner or shared)
        $userData = $user->getUserData();
        if (!$this->boardAccessService->canView($setList, $userData)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $query = trim((string) $request->query->get('q', ''));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $page  = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $limit;

        if ($query !== '') {
            $childLists = $this->setListRepository->findChildrenByQuery($id, $query);
            $setLinks   = $this->setListSetRepository->findBySetListAndQuery($id, $query);
        } else {
            $childLists = $setList->getChildLists();
            $setLinks   = $setList->getSetLinks();
        }

        // Hide sub-boards this user may not see (e.g. a private sub-board inside a public board)
        $childLists = array_filter(
            is_array($childLists) ? $childLists : iterator_to_array($childLists, false),
            fn($child) => $this->boardAccessService->canView($child, $userData),
        );

        $result = $this->setListService->getCombinedListWithSets($childLists, $setLinks);

        return $this->json(array_slice($result, $offset, $limit), 200, [], ['groups' => ['setList:read']]);
    }
}

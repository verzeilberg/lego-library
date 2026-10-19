<?php

namespace App\Controller\Lego;

use App\Repository\Lego\SetListRepository;
use App\Service\BrickLink\BrickLinkWantedListService;
use App\Service\Lego\BoardAccessService;
use App\Service\Lego\MissingItemsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * BrickLink wanted list (XML) of the missing and broken parts of every set
 * in a board, including all (visible) sub-boards.
 */
class GetBoardBrickLinkWantedListController extends AbstractController
{
    public function __construct(
        private readonly SetListRepository          $setListRepository,
        private readonly Security                   $security,
        private readonly BoardAccessService         $boardAccessService,
        private readonly MissingItemsService        $missingItemsService,
        private readonly BrickLinkWantedListService $wantedListService,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $setList = $this->setListRepository->find($id);
        if (!$setList) {
            return new JsonResponse(['message' => 'Set list not found'], Response::HTTP_NOT_FOUND);
        }

        $userData = $user->getUserData();
        if (!$this->boardAccessService->canView($setList, $userData)) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $boardIds = array_keys($this->missingItemsService->collectBoardPaths($setList, $userData));

        try {
            $wantedList = $this->wantedListService->build(
                $this->missingItemsService->findMissingParts($boardIds, includeBroken: true),
                $this->missingItemsService->findMissingMinifigs($boardIds),
            );
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Could not fetch BrickLink ids from Rebrickable: ' . $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse(['name' => $setList->getTitle()] + $wantedList);
    }
}
